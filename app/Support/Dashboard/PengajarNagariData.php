<?php

namespace App\Support\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\JenisEvaluasi;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\Discussion;
use App\Models\Evaluasi;
use App\Models\EvaluasiPercobaan;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\User;
use App\Models\UserModuleProgress;
use Illuminate\Support\Collection;

/**
 * Rekap belajar warga PER NAGARI untuk pengajar.
 *
 * Widget lain menjawab "modul mana yang jalan"; yang ini menjawab "nagari mana yang
 * tertinggal", pertanyaan yang tidak bisa dijawab angka per modul karena satu modul
 * bisa menyasar banyak nagari sekaligus.
 *
 * Seluruh angkanya dikumpulkan dengan segelintir query beragregat yang dikelompokkan
 * menurut `users.nagari_id`, bukan perulangan per nagari, supaya jumlah query tidak
 * ikut bertambah saat pelatihan menyasar puluhan nagari.
 */
class PengajarNagariData
{
    /**
     * @return Collection<int, array{
     *     nagari: Nagari,
     *     warga: int,
     *     modul: int,
     *     mulai: int,
     *     selesai: int,
     *     rataNilai: float|null,
     *     pengerjaan: int,
     *     lulus: int,
     *     topik: int,
     *     balasan: int,
     * }>
     */
    public static function rekap(User $user): Collection
    {
        $pelatihans = Pelatihan::query()
            ->manageableBy($user)
            ->with('nagaris:id')
            ->get(['id', 'semua_nagari']);

        if ($pelatihans->isEmpty()) {
            return collect();
        }

        $modulPerPelatihan = Module::query()
            ->manageableBy($user)
            ->ready()
            ->get(['modules.id', 'modules.pelatihan_id'])
            ->groupBy('pelatihan_id');

        $moduleIds = $modulPerPelatihan->flatten()->pluck('id')->all();

        if ($moduleIds === []) {
            return collect();
        }

        $nagaris = self::nagariSasaran($pelatihans);

        if ($nagaris->isEmpty()) {
            return collect();
        }

        $nagariIds = $nagaris->modelKeys();
        $wargaAktifIds = User::query()
            ->select('users.id')
            ->role('warga')
            ->where('users.status', ActiveStatus::Active)
            ->whereIn('users.nagari_id', $nagariIds);

        // Berapa modul yang benar-benar ditujukan ke tiap nagari. Nagari yang hanya
        // disasar satu pelatihan tidak boleh diukur dengan modul pelatihan lain.
        $modulPerNagari = [];
        foreach ($pelatihans as $pelatihan) {
            $jumlah = $modulPerPelatihan->get($pelatihan->getKey())?->count() ?? 0;
            $sasaran = $pelatihan->semua_nagari
                ? $nagariIds
                : $pelatihan->nagaris->pluck('id')->all();

            foreach ($sasaran as $nagariId) {
                $modulPerNagari[$nagariId] = ($modulPerNagari[$nagariId] ?? 0) + $jumlah;
            }
        }

        $warga = User::query()
            ->role('warga')
            ->where('status', ActiveStatus::Active)
            ->whereIn('nagari_id', $nagariIds)
            ->selectRaw('nagari_id, COUNT(*) as jumlah')
            ->groupBy('nagari_id')
            ->pluck('jumlah', 'nagari_id');

        $mulai = UserModuleProgress::query()
            ->join('users', 'users.id', '=', 'user_module_progress.user_id')
            ->whereIn('user_module_progress.module_id', $moduleIds)
            ->whereIn('users.nagari_id', $nagariIds)
            ->whereIn('users.id', $wargaAktifIds)
            ->selectRaw('users.nagari_id, COUNT(DISTINCT user_module_progress.user_id) as jumlah')
            ->groupBy('users.nagari_id')
            ->pluck('jumlah', 'nagari_id');

        $selesai = UserModuleProgress::query()
            ->join('users', 'users.id', '=', 'user_module_progress.user_id')
            ->whereIn('user_module_progress.module_id', $moduleIds)
            ->whereIn('users.nagari_id', $nagariIds)
            ->whereIn('users.id', $wargaAktifIds)
            ->where('user_module_progress.status', ModuleProgressStatus::Completed->value)
            ->selectRaw('users.nagari_id, COUNT(*) as jumlah')
            ->groupBy('users.nagari_id')
            ->pluck('jumlah', 'nagari_id');

        $nilai = self::nilaiPerNagari($moduleIds, $nagariIds, $wargaAktifIds);
        $diskusi = self::diskusiPerNagari($moduleIds, $nagariIds, $wargaAktifIds);

        return $nagaris
            ->map(function (Nagari $nagari) use ($warga, $modulPerNagari, $mulai, $selesai, $nilai, $diskusi): array {
                $id = $nagari->getKey();
                $angkaNilai = $nilai->get($id);
                $angkaDiskusi = $diskusi->get($id);

                return [
                    'nagari' => $nagari,
                    'warga' => (int) ($warga[$id] ?? 0),
                    'modul' => (int) ($modulPerNagari[$id] ?? 0),
                    'mulai' => (int) ($mulai[$id] ?? 0),
                    'selesai' => (int) ($selesai[$id] ?? 0),
                    'rataNilai' => $angkaNilai?->rata !== null ? (float) $angkaNilai->rata : null,
                    'pengerjaan' => (int) ($angkaNilai->pengerjaan ?? 0),
                    'lulus' => (int) ($angkaNilai->lulus ?? 0),
                    'topik' => (int) ($angkaDiskusi->topik ?? 0),
                    'balasan' => (int) ($angkaDiskusi->balasan ?? 0),
                ];
            })
            ->keyBy(fn (array $baris): int => $baris['nagari']->getKey());
    }

    /**
     * Persentase penuntasan nagari: penyelesaian modul yang tercapai dibanding
     * seluruh penyelesaian yang mungkin (warga sasaran x modul yang ditujukan).
     * Dipakai supaya nagari besar dan nagari kecil dapat dibandingkan setara.
     *
     * @param  array{warga: int, modul: int, selesai: int}  $baris
     */
    public static function persenTuntas(array $baris): ?int
    {
        $kemungkinan = $baris['warga'] * $baris['modul'];

        return $kemungkinan > 0 ? (int) round($baris['selesai'] / $kemungkinan * 100) : null;
    }

    /**
     * Nagari yang benar-benar disasar pengajar ini. Pelaksanaan "seluruh nagari"
     * membentangkan daftarnya ke semua nagari aktif; sisanya dari pivot sasaran.
     *
     * @param  Collection<int, Pelatihan>  $pelatihans
     * @return Collection<int, Nagari>
     */
    private static function nagariSasaran(Collection $pelatihans): Collection
    {
        $query = Nagari::query()
            ->where('status', ActiveStatus::Active)
            ->orderBy('nama');

        if (! $pelatihans->contains(fn (Pelatihan $pelatihan): bool => (bool) $pelatihan->semua_nagari)) {
            $ids = $pelatihans->flatMap(fn (Pelatihan $pelatihan) => $pelatihan->nagaris->pluck('id'))
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $query->whereKey($ids);
        }

        return $query->get(['id', 'nama', 'kabupaten', 'slug']);
    }

    /**
     * @param  list<int>  $moduleIds
     * @param  list<int>  $nagariIds
     * @return Collection<int, object>
     */
    private static function nilaiPerNagari(array $moduleIds, array $nagariIds, $wargaAktifIds): Collection
    {
        $evaluasiIds = Evaluasi::query()
            ->ready()
            ->where('jenis', JenisEvaluasi::Kegiatan)
            ->whereIn('module_id', $moduleIds)
            ->pluck('id')
            ->all();

        if ($evaluasiIds === []) {
            return collect();
        }

        return EvaluasiPercobaan::query()
            ->join('users', 'users.id', '=', 'evaluasi_percobaans.user_id')
            ->whereIn('evaluasi_percobaans.evaluasi_id', $evaluasiIds)
            ->whereIn('users.nagari_id', $nagariIds)
            ->whereIn('users.id', $wargaAktifIds)
            ->selectRaw('users.nagari_id, AVG(evaluasi_percobaans.nilai) as rata, COUNT(*) as pengerjaan')
            ->selectRaw('SUM(evaluasi_percobaans.status = ?) as lulus', [StatusPercobaan::Passed->value])
            ->groupBy('users.nagari_id')
            ->get()
            ->keyBy('nagari_id');
    }

    /**
     * @param  list<int>  $moduleIds
     * @param  list<int>  $nagariIds
     * @return Collection<int, object>
     */
    private static function diskusiPerNagari(array $moduleIds, array $nagariIds, $wargaAktifIds): Collection
    {
        return Discussion::query()
            ->join('users', 'users.id', '=', 'discussions.user_id')
            ->whereIn('discussions.module_id', $moduleIds)
            ->whereIn('users.nagari_id', $nagariIds)
            ->whereIn('users.id', $wargaAktifIds)
            ->selectRaw('users.nagari_id')
            ->selectRaw('SUM(discussions.parent_id IS NULL) as topik')
            ->selectRaw('SUM(discussions.parent_id IS NOT NULL) as balasan')
            ->groupBy('users.nagari_id')
            ->get()
            ->keyBy('nagari_id');
    }
}
