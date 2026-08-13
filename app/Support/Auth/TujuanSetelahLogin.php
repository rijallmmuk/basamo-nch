<?php

namespace App\Support\Auth;

use App\Enums\GerbangLogin;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Tujuan sesudah login: konteks halaman menentukan AREA, identitas menentukan HOST.
 *
 * Host tempat orang menekan Masuk bukan masukan sama sekali. Itulah yang membuat
 * login dapat dilakukan dari halaman publik mana pun tanpa memberi akses ke area
 * privat nagari lain.
 */
class TujuanSetelahLogin
{
    public function __construct(private readonly Request $request) {}

    public function untuk(User $user, KonteksLogin $konteks): TujuanLogin
    {
        [$path, $pesan] = $this->area($user, $konteks);

        return new TujuanLogin($this->hostKanonik($user).$path, $pesan);
    }

    /**
     * Alamat dasbor akun ini, dipakai header situs publik.
     *
     * Rute portal dan panel tidak terikat domain, jadi `route()` di dalam view
     * mengikuti host yang sedang dibuka. Tautan akun harus dirakit dari sini.
     */
    public function dasbor(User $user): string
    {
        [$path] = $this->area($user, new KonteksLogin);

        return $this->hostKanonik($user).$path;
    }

    /**
     * Alamat logout di host akun ini sendiri.
     *
     * `portal.logout` bukan route `public.*`, jadi POST dari situs nagari lain
     * ditolak 403 oleh EnsureNagariSiteMatchesUser. Sesi berlaku lintas subdomain,
     * sehingga POST ke host sendiri tetap membawa cookie dan token CSRF-nya.
     */
    public function keluar(User $user): string
    {
        return $this->hostKanonik($user).route('portal.logout', absolute: false);
    }

    /**
     * Host yang berhak ditempati akun ini, terlepas dari tempat ia login.
     *
     * Peran lintas nagari selalu ke domain induk; warga dan operator selalu ke
     * subdomain nagarinya, termasuk saat login dari domain induk. Warga dan
     * operator tanpa nagari sudah ditolak saat login, jadi cabang terakhir cuma
     * jaring pengaman.
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

    /** Alamat situs nagari, mengikuti skema dan porta permintaan berjalan. */
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

        // Back-office selalu ke dasbor panel, apa pun gerbangnya.
        if (! $this->adalahWarga($user)) {
            return [$panel, null];
        }

        return match ($konteks->gerbang) {
            GerbangLogin::Belajar => $this->tujuanBelajar($user, $konteks, $portal),

            GerbangLogin::Umkm => $user->usesUmkmSelfService()
                ? [$panel, null]
                : [$portal, 'Akun Anda belum diberi akses pengelolaan lapak. Hubungi Operator Nagari bila usaha Anda ingin tampil di Lapau Nagari.'],

            // Halaman netral: warga berakses UMKM tetap mendarat di panel.
            null => [$user->hasUmkmAccess() ? $panel : $portal, null],
        };
    }

    /**
     * Gerbang belajar, dengan tautan langsung ke pelatihan yang sedang dilihat.
     *
     * Kelayakan id diperiksa dengan penjaga yang sama dengan halaman tujuannya
     * (`accessibleToWarga`), supaya tidak mendarat di 404 sesudah login berhasil.
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
