<?php

namespace Database\Seeders;

use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Discussion;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\Module;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RefWilayah;
use App\Models\StatusPerkawinan;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Services\LmsPointService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo agar /admin & portal terlihat hidup: 2 desa (sebutan wilayah
 * berbeda), wilayah, warga (login NIK), modul global + lokal dengan materi &
 * kuis (termasuk soal pilihan jamak), lalu progres + XP untuk leaderboard.
 *
 * Login demo: warga pakai NIK (lihat output) + sandi "password".
 */
class DemoSeeder extends Seeder
{
    private LmsPointService $points;

    public function run(): void
    {
        // Sabuk pengaman: data dummy tidak boleh masuk server produksi
        // (produksi hanya CoreSeeder — lihat README "Deploy produksi").
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder dilewati: jangan seed data dummy di produksi. Gunakan `db:seed --class=CoreSeeder --force`.');

            return;
        }

        $this->points = app(LmsPointService::class);

        // Role RBAC + super admin (esensial, idempotent) — sumber tunggal di CoreSeeder.
        $this->call(CoreSeeder::class);
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();

        $globalModules = $this->seedGlobalModules($superAdmin);

        $desaData = [
            ['nch001', 'Nagari', 'Contoh Harapan', 'Jorong', '13.06', ['Koto Tuo', 'Padang Lua', 'Sungai Tanang']], // Kab. Agam
            ['nch002', 'Nagari', 'Sungai Lansek', 'Korong', '13.05', ['Kampuang Dalam', 'Toboh Gadang', 'Sikabu']], // Kab. Padang Pariaman
        ];

        foreach ($desaData as [$slug, $jenis, $nama, $sebutan, $kabKode, $unitNames]) {
            $desa = $this->seedDesa($jenis, $nama, $sebutan, $kabKode);
            $units = $this->seedDesaUnits($desa, $unitNames);
            $this->seedDesaAdmin($desa, $slug);

            $localModule = $this->seedLocalModule($desa, $superAdmin);
            $modules = [...$globalModules, $localModule];

            $warga = $this->seedWarga($desa, $units);
            $this->seedProgress($warga, $modules);
            $this->seedDiscussions($warga, $globalModules[0]);
            $this->seedUmkm($desa, $warga, $superAdmin, $slug);
        }

        $this->command?->info('Demo siap. Login warga: NIK (lihat tabel users) + sandi "password".');
    }

    private function seedDesa(string $jenis, string $nama, string $sebutan, string $kabKode): Desa
    {
        // Tautkan ke wilayah resmi: ambil satu desa/kelurahan nyata di kabupaten ini.
        $ref = RefWilayah::level(RefWilayah::LEVEL_DESA)->where('kode', 'like', $kabKode.'.%')->orderBy('kode')->first();
        $kab = RefWilayah::find($kabKode);
        $kec = $ref ? RefWilayah::find(substr($ref->kode, 0, (int) strrpos($ref->kode, '.'))) : null;

        $desa = Desa::firstOrCreate(
            ['nama' => $nama],
            [
                'jenis_desa_id' => JenisDesa::where('nama', $jenis)->value('id'),
                'wilayah_kode' => $ref?->kode,
                'provinsi' => 'Sumatera Barat',
                'kabupaten' => $kab?->nama,
                'kecamatan' => $kec?->nama,
                'kontak' => '08123456789',
                'status' => 'active',
            ],
        );

        $desa->update(['jenis_sub_unit_id' => JenisSubUnit::where('nama', $sebutan)->value('id')]);

        return $desa;
    }

    /** @return array<int, DesaUnit> */
    private function seedDesaUnits(Desa $desa, array $names): array
    {
        return collect($names)
            ->map(fn (string $nama) => DesaUnit::firstOrCreate(['desa_id' => $desa->id, 'nama' => $nama]))
            ->all();
    }

    private function seedDesaAdmin(Desa $desa, string $slug): User
    {
        // Pakai admin desa yang sudah ada (mis. dari UserSeeder) bila tersedia.
        $existing = User::where('desa_id', $desa->id)->where('role', 'desa_admin')->first();
        if ($existing) {
            return $existing;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin.'.$slug.'@basamo.nch'],
            [
                'name' => 'Admin '.$desa->nama,
                // Username = kode nagari (selaras alur produksi); fallback bila kode kosong.
                'username' => $desa->defaultAdminUsername() ?? 'admin_'.$slug,
                'password' => Hash::make('password'), // demo: bisa langsung login (bukan alur OTP)
                'desa_id' => $desa->id,
                'role' => 'desa_admin',
                'status' => 'active',
            ],
        );
        $admin->assignRole('desa_admin');

        return $admin;
    }

    /** @return array<int, Module> */
    private function seedGlobalModules(User $author): array
    {
        $blueprint = [
            ['Mengenal Smartphone & Internet', 20, 'Dasar penggunaan smartphone dan koneksi internet untuk pemula.'],
            ['Media Sosial yang Bijak', 25, 'Memakai media sosial secara sehat, sopan, dan aman.'],
            ['Keamanan Digital & Anti-Hoaks', 30, 'Mengenali penipuan, melindungi data pribadi, dan memeriksa hoaks.'],
            ['Belanja Online & E-commerce', 20, 'Berbelanja online dengan aman dan bijak.'],
            ['Pemanfaatan AI untuk Sehari-hari', 25, 'Mengenal kecerdasan buatan dan manfaatnya.'],
        ];

        return collect($blueprint)
            ->map(fn (array $b, int $i) => $this->makeModule($b[0], $b[1], $b[2], null, $author, $i + 1))
            ->all();
    }

    private function seedLocalModule(Desa $desa, User $author): Module
    {
        return $this->makeModule(
            'Potensi & Produk Unggulan '.$desa->nama,
            15,
            'Modul lokal khusus warga '.$desa->nama.' tentang potensi desa.',
            $desa->id,
            $author,
            10,
        );
    }

    private function makeModule(string $title, int $minutes, string $desc, ?int $desaId, User $author, int $order): Module
    {
        $module = Module::firstOrCreate(
            ['judul' => $title, 'desa_id' => $desaId],
            [
                'deskripsi' => '<p>'.$desc.'</p>',
                'estimasi_menit' => $minutes,
                'status' => 'published',
                'created_by' => $author->id,
                'urutan' => $order,
            ],
        );

        if ($module->pages()->doesntExist()) {
            $module->pages()->createMany([
                [
                    'judul' => 'Pengantar',
                    'blocks' => [
                        ['type' => 'teks', 'data' => ['konten' => '<p>Selamat datang di modul <strong>'.$title.'</strong>. Mari kita mulai belajar bersama.</p>']],
                    ],
                ],
                [
                    // Halaman campuran: teks penjelasan + video pendukung dalam satu halaman.
                    'judul' => 'Materi Utama',
                    'blocks' => [
                        ['type' => 'teks', 'data' => ['konten' => '<p>'.$desc.' Pelajari poin-poin penting berikut dengan saksama.</p><ul><li>Poin pertama</li><li>Poin kedua</li><li>Poin ketiga</li></ul>']],
                        ['type' => 'video', 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'caption' => 'Video pendukung materi.']],
                    ],
                ],
            ]);
        }

        $this->makeQuiz($module);

        return $module;
    }

    private function makeQuiz(Module $module): Quiz
    {
        $quiz = Quiz::firstOrCreate(
            ['module_id' => $module->id],
            ['nilai_lulus' => 70, 'maks_percobaan' => 3],
        );

        if ($quiz->questions()->doesntExist()) {
            // Soal pilihan tunggal.
            $q1 = $quiz->questions()->create(['pertanyaan' => 'Apa langkah pertama yang aman saat menggunakan layanan digital?']);
            $q1->options()->createMany([
                ['teks_opsi' => 'Membagikan kata sandi ke teman', 'is_correct' => false, 'urutan' => 1],
                ['teks_opsi' => 'Menjaga kerahasiaan kata sandi', 'is_correct' => true, 'urutan' => 2],
                ['teks_opsi' => 'Memakai sandi yang sama di mana-mana', 'is_correct' => false, 'urutan' => 3],
            ]);

            // Soal pilihan jamak (>1 benar).
            $q2 = $quiz->questions()->create(['pertanyaan' => 'Manakah ciri-ciri informasi hoaks? (boleh pilih lebih dari satu)']);
            $q2->options()->createMany([
                ['teks_opsi' => 'Judul provokatif & bombastis', 'is_correct' => true, 'urutan' => 1],
                ['teks_opsi' => 'Sumber jelas dan kredibel', 'is_correct' => false, 'urutan' => 2],
                ['teks_opsi' => 'Meminta disebarkan segera', 'is_correct' => true, 'urutan' => 3],
                ['teks_opsi' => 'Mencantumkan tautan resmi', 'is_correct' => false, 'urutan' => 4],
            ]);
        }

        return $quiz;
    }

    /** @return array<int, User> */
    private function seedWarga(Desa $desa, array $units): array
    {
        $names = ['Budi Santoso', 'Siti Aminah', 'Andi Pratama', 'Dewi Lestari', 'Rudi Hartono', 'Nurul Hidayah', 'Fajar Nugraha', 'Maya Sari'];
        $seq = $desa->id;

        $agamaId = Agama::value('id');
        $statusIds = StatusPerkawinan::pluck('id')->all();
        $pekerjaanIds = Pekerjaan::pluck('id')->all();

        return collect($names)->map(function (string $nama, int $i) use ($desa, $units, $seq, $agamaId, $statusIds, $pekerjaanIds) {
            // NIK demo 16 digit: 32 (SumBar) + 2 digit desa + 12 digit urut.
            $nik = sprintf('32%02d%012d', $seq, ($seq * 100) + $i + 1);
            $unit = $units[$i % count($units)];

            // Identitas kependudukan (lapisan 1) — akun warga ditautkan via penduduk_id.
            $penduduk = Penduduk::firstOrCreate(
                ['nik' => $nik],
                [
                    'nama' => $nama,
                    'desa_id' => $desa->id,
                    'desa_unit_id' => $unit->id,
                    'jenis_kelamin' => $i % 2 === 0 ? 'L' : 'P',
                    'agama_id' => $agamaId,
                    'status_perkawinan_id' => $statusIds[$i % count($statusIds)],
                    'pekerjaan_id' => $pekerjaanIds[array_rand($pekerjaanIds)],
                ],
            );

            $warga = User::firstOrCreate(
                ['nik' => $nik],
                [
                    'name' => $nama,
                    'penduduk_id' => $penduduk->id,
                    'desa_id' => $desa->id,
                    'desa_unit_id' => $unit->id,
                    'phone' => '0812'.sprintf('%08d', random_int(0, 99999999)),
                    'password' => Hash::make('password'),
                    'role' => 'warga',
                    'status' => 'active',
                ],
            );
            $warga->assignRole('warga');

            return $warga;
        })->all();
    }

    /**
     * Beri progres acak: tiap warga menyelesaikan sebagian modul + lulus kuisnya,
     * sehingga total_xp & leaderboard bervariasi.
     *
     * @param  array<int, User>  $wargaList
     * @param  array<int, Module>  $modules
     */
    private function seedProgress(array $wargaList, array $modules): void
    {
        foreach ($wargaList as $idx => $warga) {
            $completeCount = ($idx % count($modules)) + 1; // 1..N

            foreach (array_slice($modules, 0, $completeCount) as $module) {
                $pageIds = $module->pages()->pluck('id')->all();

                UserModuleProgress::firstOrCreate(
                    ['user_id' => $warga->id, 'module_id' => $module->id],
                    ['status' => 'completed', 'halaman_selesai' => $pageIds, 'completed_at' => now()->subDays(random_int(1, 20))],
                );

                $this->points->awardModuleCompletion($warga, $module);

                // Sebagian lulus kuis.
                if ($module->quiz && random_int(0, 1) === 1) {
                    QuizAttempt::firstOrCreate(
                        ['user_id' => $warga->id, 'quiz_id' => $module->quiz->id, 'status' => 'passed'],
                        ['nilai' => random_int(70, 100), 'submitted_at' => now()->subDays(random_int(1, 15))],
                    );
                    $this->points->awardQuizPass($warga, $module->quiz);
                }
            }
        }
    }

    /**
     * Naikkan sebagian warga → Pemilik UMKM, beri profil usaha + produk dengan
     * status beragam (pending/approved/rejected) agar antrian verifikasi &
     * dashboard punya data nyata.
     *
     * @param  array<int, User>  $wargaList
     */
    private function seedUmkm(Desa $desa, array $wargaList, User $verifier, string $slug): void
    {
        // Cetak biru usaha: [nama, kategori, [produk...]].
        $blueprints = [
            ['Keripik Sanjai Amai', 'Kuliner', ['Keripik Balado Pedas', 'Keripik Singkong Original', 'Sanjai Lado Mudo']],
            ['Tenun Songket Lestari', 'Kerajinan', ['Songket Benang Emas', 'Selendang Tenun', 'Kain Sarung Tenun']],
            ['Kopi Robusta Bukik', 'Kuliner', ['Kopi Bubuk 250g', 'Biji Kopi Sangrai']],
        ];

        foreach ($blueprints as $i => [$namaUsaha, $kategori, $produk]) {
            if (! isset($wargaList[$i])) {
                break;
            }

            $owner = $wargaList[$i];
            $owner->update(['umkm_access_granted_at' => now()]);

            $categoryId = UmkmCategory::where('slug', strtolower($kategori))->value('id');

            $profile = UmkmProfile::firstOrCreate(
                ['desa_id' => $desa->id, 'user_id' => $owner->id],
                [
                    'nama_usaha' => $namaUsaha.' ('.$slug.')',
                    'alamat' => 'Pasar '.$desa->nama,
                    'whatsapp' => '0812'.sprintf('%08d', random_int(0, 99999999)),
                    'status' => 'active',
                ],
            );

            foreach ($produk as $j => $nama) {
                // Variasi status: produk pertama disetujui, kedua menunggu, sisanya acak.
                $status = match (true) {
                    $j === 0 => 'approved',
                    $j === 1 => 'pending',
                    default => ['approved', 'pending', 'rejected'][random_int(0, 2)],
                };

                UmkmProduct::firstOrCreate(
                    ['umkm_profile_id' => $profile->id, 'nama_produk' => $nama],
                    [
                        'umkm_category_id' => $categoryId,
                        'deskripsi' => $nama.' produksi '.$namaUsaha.'.',
                        'harga' => random_int(10, 150) * 1000,
                        'status' => $status,
                        'alasan_penolakan' => $status === 'rejected' ? 'Foto produk kurang jelas, mohon unggah ulang.' : null,
                        'approved_by' => $status === 'approved' ? $verifier->id : null,
                        'approved_at' => $status === 'approved' ? now()->subDays(random_int(1, 10)) : null,
                        'jumlah_dilihat' => random_int(0, 350),
                    ],
                );
            }
        }
    }

    /**
     * @param  array<int, User>  $wargaList
     */
    private function seedDiscussions(array $wargaList, Module $module): void
    {
        $penanya = $wargaList[0];

        $thread = Discussion::firstOrCreate(
            ['module_id' => $module->id, 'user_id' => $penanya->id, 'parent_id' => null, 'isi' => 'Bagaimana cara membuat kata sandi yang kuat tapi mudah diingat?'],
        );
        $this->points->awardDiscussionParticipation($penanya, $module);

        if (isset($wargaList[1])) {
            Discussion::firstOrCreate(
                ['module_id' => $module->id, 'user_id' => $wargaList[1]->id, 'parent_id' => $thread->id, 'isi' => 'Gabungkan beberapa kata + angka, hindari tanggal lahir.'],
            );
            $this->points->awardDiscussionParticipation($wargaList[1], $module);
        }
    }
}
