<?php

namespace App\Livewire;

use App\Enums\StatusPercobaan;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\EvaluasiPertanyaan;
use App\Models\Module;
use App\Models\User;
use App\Services\SlcProgressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, EvaluasiPertanyaan> $pertanyaans
 */
class EvaluasiPlayer extends Component
{
    public Evaluasi $evaluasi;

    public Module $module;

    public array $answers = [];

    public bool $submitted = false;

    public string $resultStatus = '';

    public ?int $resultScore = null;

    public array $evaluasiErrors = [];

    public bool $isPreview = false;

    public function mount(Evaluasi $evaluasi, bool $isPreview = false): void
    {
        $this->evaluasi = $evaluasi;
        $this->module = $evaluasi->module()->firstOrFail();
        $this->isPreview = $isPreview;

        // Pra-inisialisasi struktur jawaban. Soal multi-jawaban WAJIB bertipe
        // array agar Livewire memperlakukan tiap checkbox sebagai anggota array.
        // Tanpa ini property tetap skalar, sehingga mencentang satu checkbox
        // mengubahnya jadi boolean true dan membuat seluruh checkbox ikut tercentang.
        foreach ($this->pertanyaans as $pertanyaan) {
            $isMulti = $pertanyaan->opsis->where('is_correct', true)->count() > 1;
            $this->answers[$pertanyaan->id] = $isMulti ? [] : null;
        }
    }

    #[Computed]
    public function pertanyaans(): Collection
    {
        return $this->evaluasi->pertanyaans()->with('opsis')->get();
    }

    /**
     * Apakah user masih boleh mengerjakan evaluasi ini (sumber kebenaran server)?
     * Livewire tak lewat controller lagi setelah mount pertama (aksi submit() langsung
     * lewat endpoint update Livewire) — jadi seluruh syarat akses yang tadinya dicek
     * EvaluasiController::show() WAJIB diulang di sini juga, bukan cuma lulus/batas
     * percobaan. Tanpa ini, tab yang sudah terbuka bisa tetap submit walau modulnya
     * baru saja di-unpublish/dipindah nagari/diberi prasyarat baru oleh admin.
     */
    private function canAttempt(): bool
    {
        $user = auth()->user();
        $userId = $user->id;

        $currentEvaluasi = Evaluasi::query()
            ->ready()
            ->whereKey($this->evaluasi->getKey())
            ->where('module_id', $this->module->getKey())
            ->first();

        if (! $currentEvaluasi) {
            return false;
        }

        $this->evaluasi = $currentEvaluasi;
        unset($this->pertanyaans);

        // Gerbang akses modul OTORITATIF (dihitung ulang dari DB terkini): published +
        // pelaksanaan aktif + menyasar nagari user + berstatus terbuka.
        // Menggantikan cek nagari lama (modul tak lagi berkolom nagari_id) sekaligus
        // menutup celah: tab terbuka tak bisa submit bila modul baru dikunci/dipindah.
        if (! $this->module->isAccessibleToWarga($user)) {
            return false;
        }

        $progressService = app(SlcProgressService::class);

        if (! $progressService->isModuleAccessible($user, $this->module)) {
            return false;
        }

        if ($this->evaluasi->isPretest()) {
            if ($this->evaluasi->sudahDikerjakan($user)) {
                return false;
            }
        } else {
            if (! $progressService->isModuleCompleted($user, $this->module)) {
                return false;
            }

            $alreadyPassed = EvaluasiPercobaan::where('user_id', $userId)
                ->where('evaluasi_id', $this->evaluasi->id)
                ->where('status', StatusPercobaan::Passed)
                ->exists();

            if ($alreadyPassed) {
                return false;
            }
        }

        if ($this->evaluasi->maks_percobaan > 0) {
            $finishedAttempts = EvaluasiPercobaan::where('user_id', $userId)
                ->where('evaluasi_id', $this->evaluasi->id)
                ->whereIn('status', [StatusPercobaan::Passed, StatusPercobaan::Failed])
                ->count();

            if ($finishedAttempts >= $this->evaluasi->maks_percobaan) {
                return false;
            }
        }

        return true;
    }

    public function submit(): void
    {
        if ($this->isPreview) {
            $this->evaluasiErrors = ['Mode pratinjau tidak menyimpan jawaban atau hasil.'];

            return;
        }

        // Sudah dikumpulkan di sesi komponen ini — cegah submit ganda.
        if ($this->submitted) {
            return;
        }

        // Re-validasi kelayakan di server. Gating di controller hanya berlaku saat GET;
        // tanpa ini, submit() bisa dipanggil berulang via Livewire untuk melewati
        // batas percobaan atau mengulang setelah sudah lulus.
        if (! $this->canAttempt()) {
            $this->evaluasiErrors = ['Anda tidak dapat mengerjakan evaluasi ini lagi.'];

            return;
        }

        if ($this->pertanyaans->isEmpty()) {
            return;
        }

        // Validasi semua soal sudah dijawab
        $this->evaluasiErrors = [];

        foreach ($this->pertanyaans as $pertanyaan) {
            if (empty($this->answers[$pertanyaan->id])) {
                $this->evaluasiErrors[] = "Soal #{$pertanyaan->urutan} belum dijawab.";
            }
        }

        if (! empty($this->evaluasiErrors)) {
            return;
        }

        $scoreSum = 0.0;
        $totalQuestions = 0;

        foreach ($this->pertanyaans as $pertanyaan) {
            $totalQuestions++;

            $correctIds = $pertanyaan->opsis->where('is_correct', true)->pluck('id');
            $incorrectIds = $pertanyaan->opsis->where('is_correct', false)->pluck('id');

            // Soal bisa punya >1 jawaban benar → nilai partial credit per soal.
            // Saring ke opsi milik soal ini saja: cegah ID asing dari klien memicu
            // error FK saat simpan atau mengotori data.
            $selectedIds = collect((array) ($this->answers[$pertanyaan->id] ?? []))
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->unique()
                ->intersect($pertanyaan->opsis->pluck('id'))
                ->values();

            $correctSelected = $selectedIds->intersect($correctIds)->count();
            $wrongSelected = $selectedIds->intersect($incorrectIds)->count();

            // frac = max(0, (benar terpilih / total benar) − (salah terpilih / total salah))
            $penalty = $incorrectIds->count() > 0 ? $wrongSelected / $incorrectIds->count() : 0;
            $fraction = max(0, ($correctSelected / max($correctIds->count(), 1)) - $penalty);

            $scoreSum += $fraction;
        }

        // Nilai dinormalisasi 0–100 dari total fraksi soal benar.
        $percentage = $totalQuestions > 0 ? (int) round(($scoreSum / $totalQuestions) * 100) : 0;
        $passed = $percentage >= $this->evaluasi->nilai_lulus;
        $isPretest = $this->evaluasi->isPretest();

        // Auto-grade sinkron + atomik + anti-race: kunci attempt user+quiz, re-cek
        // kelayakan di bawah kunci, lalu simpan skor attempt.
        // Mencegah submit paralel (multi-tab) melewati batas percobaan.
        $attempt = DB::transaction(function () use ($percentage, $passed, $isPretest): ?EvaluasiPercobaan {
            // Kunci baris User dulu — SELALU ada, beda dari baris EvaluasiPercobaan di bawah
            // yang absen pada percobaan PERTAMA (lockForUpdate() atas 0 baris tak
            // mengunci apa pun, jadi tanpa ini 2 submit pertama yang paralel bisa
            // sama-sama lolos canAttempt() dan sama-sama membuat attempt).
            User::whereKey(auth()->id())->lockForUpdate()->first();

            EvaluasiPercobaan::where('user_id', auth()->id())
                ->where('evaluasi_id', $this->evaluasi->id)
                ->lockForUpdate()
                ->get();

            if (! $this->canAttempt()) {
                return null;
            }

            return EvaluasiPercobaan::create([
                'user_id' => auth()->id(),
                'evaluasi_id' => $this->evaluasi->id,
                'nilai' => $percentage,
                'status' => $isPretest
                    ? StatusPercobaan::Selesai
                    : ($passed ? StatusPercobaan::Passed : StatusPercobaan::Failed),
                'submitted_at' => now(),
            ]);
        });

        // Kelayakan gugur saat dikunci (mis. submit paralel mendahului) → batalkan.
        if ($attempt === null) {
            $this->evaluasiErrors = ['Anda tidak dapat mengerjakan evaluasi ini lagi.'];

            return;
        }

        $this->resultStatus = $isPretest ? 'completed' : ($passed ? 'passed' : 'failed');
        $this->resultScore = $percentage;
        $this->submitted = true;

        if ($isPretest || $passed) {
            $this->dispatch('confetti');
            $this->dispatch(
                'toast',
                type: 'success',
                title: $this->evaluasi->isPretest() ? 'Pre-test selesai!' : 'Evaluasi selesai!',
                message: $this->evaluasi->isPretest()
                    ? 'Jawaban Anda sudah tercatat. Materi kini dapat dibuka.'
                    : ($percentage >= 100 ? 'Nilai sempurna. Kerja bagus!' : 'Selamat, Anda lulus evaluasi!'),
            );
        }

        // Hasil evaluasi cukup ditampilkan seketika (toast + layar hasil) — tak perlu
        // notifikasi tersimpan; warga tak butuh diingatkan ulang soal nilainya.
    }

    public function render()
    {
        return view('livewire.evaluasi-player');
    }
}
