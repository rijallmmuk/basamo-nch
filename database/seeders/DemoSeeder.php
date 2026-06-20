<?php

namespace Database\Seeders;

use App\Models\Discussion;
use App\Models\Module;
use App\Models\Nagari;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Models\UserModuleProgress;
use App\Models\Wilayah;
use App\Services\LmsPointService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo agar /admin & portal terlihat hidup: 2 nagari (sebutan wilayah
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
        $this->points = app(LmsPointService::class);

        // Role RBAC + super admin (esensial, idempotent) — sumber tunggal di CoreSeeder.
        $this->call(CoreSeeder::class);
        $superAdmin = User::where('role', 'super_admin')->firstOrFail();

        $globalModules = $this->seedGlobalModules($superAdmin);

        $nagariData = [
            ['NCH-001', 'Nagari', 'Contoh Harapan', 'Jorong', 'Kabupaten Agam', ['Koto Tuo', 'Padang Lua', 'Sungai Tanang']],
            ['NCH-002', 'Nagari', 'Sungai Lansek', 'Korong', 'Kabupaten Padang Pariaman', ['Kampuang Dalam', 'Toboh Gadang', 'Sikabu']],
        ];

        foreach ($nagariData as [$kode, $jenis, $nama, $sebutan, $kabupaten, $unitNames]) {
            $nagari = $this->seedNagari($kode, $jenis, $nama, $sebutan, $kabupaten);
            $wilayah = $this->seedWilayah($nagari, $unitNames);
            $this->seedNagariAdmin($nagari);

            $localModule = $this->seedLocalModule($nagari, $superAdmin);
            $modules = [...$globalModules, $localModule];

            $warga = $this->seedWarga($nagari, $wilayah);
            $this->seedProgress($warga, $modules);
            $this->seedDiscussions($warga, $globalModules[0]);
            $this->seedUmkm($nagari, $warga, $superAdmin);
        }

        $this->command?->info('Demo siap. Login warga: NIK (lihat tabel users) + sandi "password".');
    }

    private function seedNagari(string $kode, string $jenis, string $nama, string $sebutan, string $kabupaten): Nagari
    {
        $nagari = Nagari::firstOrCreate(
            ['kode' => $kode],
            ['nama' => $nama, 'jenis' => $jenis, 'provinsi' => 'Sumatera Barat', 'kabupaten' => $kabupaten, 'kecamatan' => 'Kecamatan Demo', 'kontak' => '08123456789', 'status' => 'active'],
        );

        $nagari->update(['wilayah_label' => $sebutan]);

        return $nagari;
    }

    /** @return array<int, Wilayah> */
    private function seedWilayah(Nagari $nagari, array $names): array
    {
        return collect($names)
            ->map(fn (string $nama) => Wilayah::firstOrCreate(['nagari_id' => $nagari->id, 'nama' => $nama]))
            ->all();
    }

    private function seedNagariAdmin(Nagari $nagari): User
    {
        // Pakai admin nagari yang sudah ada (mis. dari UserSeeder) bila tersedia.
        $existing = User::where('nagari_id', $nagari->id)->where('role', 'nagari_admin')->first();
        if ($existing) {
            return $existing;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin.'.strtolower($nagari->kode).'@basamo.nch'],
            [
                'name' => 'Admin '.$nagari->nama,
                'username' => 'admin_'.strtolower(str_replace('-', '', $nagari->kode)),
                'password' => Hash::make('password'),
                'nagari_id' => $nagari->id,
                'role' => 'nagari_admin',
                'status' => 'active',
            ],
        );
        $admin->assignRole('nagari_admin');

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

    private function seedLocalModule(Nagari $nagari, User $author): Module
    {
        return $this->makeModule(
            'Potensi & Produk Unggulan '.$nagari->nama,
            15,
            'Modul lokal khusus warga '.$nagari->nama.' tentang potensi nagari.',
            $nagari->id,
            $author,
            10,
        );
    }

    private function makeModule(string $title, int $minutes, string $desc, ?int $nagariId, User $author, int $order): Module
    {
        $module = Module::firstOrCreate(
            ['title' => $title, 'nagari_id' => $nagariId],
            [
                'description' => '<p>'.$desc.'</p>',
                'estimated_minutes' => $minutes,
                'status' => 'published',
                'created_by' => $author->id,
                'sort_order' => $order,
            ],
        );

        if ($module->pages()->doesntExist()) {
            $module->pages()->createMany([
                ['title' => 'Pengantar', 'type' => 'text', 'content' => '<p>Selamat datang di modul <strong>'.$title.'</strong>. Mari kita mulai belajar bersama.</p>'],
                ['title' => 'Materi Utama', 'type' => 'text', 'content' => '<p>'.$desc.' Pelajari poin-poin penting berikut dengan saksama.</p><ul><li>Poin pertama</li><li>Poin kedua</li><li>Poin ketiga</li></ul>'],
                ['title' => 'Video Pembelajaran', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ]);
        }

        $this->makeQuiz($module);

        return $module;
    }

    private function makeQuiz(Module $module): Quiz
    {
        $quiz = Quiz::firstOrCreate(
            ['module_id' => $module->id],
            ['passing_score' => 70, 'max_attempts' => 3],
        );

        if ($quiz->questions()->doesntExist()) {
            // Soal pilihan tunggal.
            $q1 = $quiz->questions()->create(['question' => 'Apa langkah pertama yang aman saat menggunakan layanan digital?']);
            $q1->options()->createMany([
                ['option_text' => 'Membagikan kata sandi ke teman', 'is_correct' => false, 'sort_order' => 1],
                ['option_text' => 'Menjaga kerahasiaan kata sandi', 'is_correct' => true, 'sort_order' => 2],
                ['option_text' => 'Memakai sandi yang sama di mana-mana', 'is_correct' => false, 'sort_order' => 3],
            ]);

            // Soal pilihan jamak (>1 benar).
            $q2 = $quiz->questions()->create(['question' => 'Manakah ciri-ciri informasi hoaks? (boleh pilih lebih dari satu)']);
            $q2->options()->createMany([
                ['option_text' => 'Judul provokatif & bombastis', 'is_correct' => true, 'sort_order' => 1],
                ['option_text' => 'Sumber jelas dan kredibel', 'is_correct' => false, 'sort_order' => 2],
                ['option_text' => 'Meminta disebarkan segera', 'is_correct' => true, 'sort_order' => 3],
                ['option_text' => 'Mencantumkan tautan resmi', 'is_correct' => false, 'sort_order' => 4],
            ]);
        }

        return $quiz;
    }

    /** @return array<int, User> */
    private function seedWarga(Nagari $nagari, array $wilayah): array
    {
        $names = ['Budi Santoso', 'Siti Aminah', 'Andi Pratama', 'Dewi Lestari', 'Rudi Hartono', 'Nurul Hidayah', 'Fajar Nugraha', 'Maya Sari'];
        $seq = $nagari->id;

        return collect($names)->map(function (string $nama, int $i) use ($nagari, $wilayah, $seq) {
            // NIK demo 16 digit: 32 (SumBar) + 2 digit nagari + 12 digit urut.
            $nik = sprintf('32%02d%012d', $seq, ($seq * 100) + $i + 1);

            $warga = User::firstOrCreate(
                ['username' => $nik],
                [
                    'name' => $nama,
                    'nagari_id' => $nagari->id,
                    'wilayah_id' => $wilayah[$i % count($wilayah)]->id,
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
                    ['status' => 'completed', 'pages_completed' => $pageIds, 'completed_at' => now()->subDays(random_int(1, 20))],
                );

                $this->points->awardModuleCompletion($warga, $module);

                // Sebagian lulus kuis.
                if ($module->quiz && random_int(0, 1) === 1) {
                    QuizAttempt::firstOrCreate(
                        ['user_id' => $warga->id, 'quiz_id' => $module->quiz->id, 'status' => 'passed'],
                        ['score' => random_int(70, 100), 'submitted_at' => now()->subDays(random_int(1, 15))],
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
    private function seedUmkm(Nagari $nagari, array $wargaList, User $verifier): void
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
                ['nagari_id' => $nagari->id, 'user_id' => $owner->id],
                [
                    'nama_usaha' => $namaUsaha.' ('.$nagari->kode.')',
                    'umkm_category_id' => $categoryId,
                    'deskripsi' => 'Usaha '.strtolower($kategori).' khas '.$nagari->nama.'.',
                    'alamat' => 'Pasar '.$nagari->nama,
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
                        'deskripsi' => $nama.' produksi '.$namaUsaha.'.',
                        'harga' => random_int(10, 150) * 1000,
                        'status' => $status,
                        'rejection_reason' => $status === 'rejected' ? 'Foto produk kurang jelas, mohon unggah ulang.' : null,
                        'approved_by' => $status === 'approved' ? $verifier->id : null,
                        'approved_at' => $status === 'approved' ? now()->subDays(random_int(1, 10)) : null,
                        'view_count' => random_int(0, 350),
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
            ['module_id' => $module->id, 'user_id' => $penanya->id, 'parent_id' => null, 'body' => 'Bagaimana cara membuat kata sandi yang kuat tapi mudah diingat?'],
        );
        $this->points->awardDiscussionParticipation($penanya, $module);

        if (isset($wargaList[1])) {
            Discussion::firstOrCreate(
                ['module_id' => $module->id, 'user_id' => $wargaList[1]->id, 'parent_id' => $thread->id, 'body' => 'Gabungkan beberapa kata + angka, hindari tanggal lahir.'],
            );
            $this->points->awardDiscussionParticipation($wargaList[1], $module);
        }
    }
}
