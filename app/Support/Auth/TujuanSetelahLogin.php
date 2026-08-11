<?php

namespace App\Support\Auth;

use App\Enums\GerbangLogin;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Tujuan sesudah login = fungsi dari KONTEKS dan IDENTITAS, bukan salah satunya.
 *
 * Konteks (gerbang halaman, pelatihan yang sedang dilihat) menentukan AREA,
 * identitas menentukan HOST. Pemisahan itu yang membuat "boleh login dari halaman
 * mana pun" tidak berarti "boleh berada di area privat nagari orang lain": siapa
 * pun boleh menekan Masuk di situs nagari tetangga, lalu dipulangkan ke rumahnya
 * sendiri.
 *
 * Dipisahkan dari AuthController karena inilah satu-satunya bagian login yang
 * berupa matriks. Menaruhnya di controller akan menumbuhkan rantai `if` sepanjang
 * layar di tengah alur yang sudah padat dengan rem laju, pemeriksaan status akun,
 * dan audit sesi.
 */
class TujuanSetelahLogin
{
    public function __construct(private readonly Request $request) {}

    /**
     * Host tempat login dilakukan SENGAJA tidak menjadi masukan. Sejak warga dan
     * operator selalu dipulangkan ke nagarinya, tujuan tidak lagi bergantung pada
     * dari mana orang menekan Masuk, dan itulah yang membuat "boleh login dari
     * halaman mana pun" bisa dinyatakan dalam satu kalimat.
     */
    public function untuk(User $user, KonteksLogin $konteks): TujuanLogin
    {
        [$path, $pesan] = $this->area($user, $konteks);

        return new TujuanLogin($this->hostKanonik($user).$path, $pesan);
    }

    /**
     * Host yang berhak ditempati akun ini, TANPA memandang dari mana ia login.
     *
     * - Peran lintas nagari bekerja di ruang global, jadi selalu domain induk.
     *   Alamat nagari tertentu akan menyiratkan konteks tenant yang tidak sedang
     *   diterapkan.
     * - Warga dan operator SELALU dipulangkan ke subdomain nagarinya, termasuk
     *   ketika mereka login dari domain induk. Satu akun berarti satu alamat
     *   rumah, dan itu yang membuat "boleh login dari mana saja" tidak berubah
     *   menjadi "punya dua alamat yang sama-sama sah".
     *
     * Nagari yang tidak ada seharusnya mustahil di titik ini: login sudah menolak
     * warga maupun operator tanpa relasi nagari. Kalau toh terjadi, domain induk
     * jauh lebih baik daripada merakit hostname dari slug yang kosong.
     */
    private function hostKanonik(User $user): string
    {
        $induk = rtrim((string) config('app.url'), '/');

        if ($user->isLintasNagari()) {
            return $induk;
        }

        $rumah = $user->nagari;

        return $rumah !== null ? $this->hostNagari($rumah) : $induk;
    }

    /**
     * Alamat situs sebuah nagari, mengikuti skema dan porta permintaan berjalan
     * supaya tetap benar saat `php artisan serve` memakai porta selain 80/443.
     */
    private function hostNagari(Nagari $nagari): string
    {
        $port = $this->request->getPort();
        $portTambahan = in_array($port, [80, 443], true) ? '' : ':'.$port;

        return $this->request->getScheme().'://'.$nagari->slug.'.'
            .config('app.public_base_domain').$portTambahan;
    }

    /**
     * Area tujuan beserta pesan opsional.
     *
     * @return array{0: string, 1: ?string}
     */
    private function area(User $user, KonteksLogin $konteks): array
    {
        $panel = route('filament.panel.pages.dashboard', absolute: false);
        $portal = route('portal.home', absolute: false);

        // Back-office selalu ke dasbor panel, apa pun gerbangnya. Panel sudah
        // menampilkan menu sesuai perannya, jadi tidak ada yang perlu dituju
        // lebih spesifik dari itu.
        if (! $this->adalahWarga($user)) {
            return [$panel, null];
        }

        return match ($konteks->gerbang) {
            GerbangLogin::Belajar => $this->tujuanBelajar($user, $konteks, $portal),

            // Pemilik lapak mendarat di dasbor panel, yang bagi akun self-service
            // memang berisi ringkasan lapaknya sendiri. Warga tanpa akses tetap
            // boleh masuk lewat Lapau Nagari, hanya saja rumahnya bukan di sana.
            GerbangLogin::Umkm => $user->usesUmkmSelfService()
                ? [$panel, null]
                : [$portal, 'Akun Anda belum diberi akses pengelolaan lapak. Hubungi Operator Nagari bila usaha Anda ingin tampil di Lapau Nagari.'],

            // Halaman netral: pertahankan perilaku lama persis. Warga berakses UMKM
            // tetap mendarat di panel, sebab itulah beranda yang selama ini ia
            // kenal, dan mengubahnya bukan bagian dari permintaan gerbang.
            null => [$user->hasUmkmAccess() ? $panel : $portal, null],
        };
    }

    /**
     * Gerbang belajar, dengan tautan langsung ke pelatihan yang sedang dilihat.
     *
     * Id-nya diperiksa lewat penjaga yang SAMA dengan halaman tujuannya
     * (`accessibleToWarga`, dipakai PelatihanController::show). Memakai penjaga
     * yang berbeda akan melempar orang ke halaman yang justru membalas 404 tepat
     * sesudah login berhasil, yang jauh lebih membingungkan daripada mendarat di
     * beranda portal.
     *
     * @return array{0: string, 1: ?string}
     */
    private function tujuanBelajar(User $user, KonteksLogin $konteks, string $portal): array
    {
        if ($konteks->pelatihanId === null) {
            return [$portal, null];
        }

        $boleh = Pelatihan::query()
            ->accessibleToWarga($user)
            ->whereKey($konteks->pelatihanId)
            ->exists();

        if (! $boleh) {
            return [$portal, 'Pelatihan yang Anda buka belum tersedia untuk akun Anda, jadi kami antar ke beranda portal.'];
        }

        return [route('portal.pelatihan.show', $konteks->pelatihanId, absolute: false), null];
    }

    /**
     * Warga murni, yaitu akun yang beranda sesungguhnya ada di portal.
     *
     * Memakai primaryRole, bukan hasRole('warga'), karena akun boleh multi-peran:
     * operator yang kebetulan juga berperan warga tetap harus diperlakukan sebagai
     * pengelola (lihat User::ROLE_PRIORITY).
     */
    private function adalahWarga(User $user): bool
    {
        return $user->primaryRole() === 'warga';
    }
}
