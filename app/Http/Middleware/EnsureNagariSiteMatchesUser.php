<?php

namespace App\Http\Middleware;

use App\Models\Nagari;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batas situs nagari: area terautentikasi di `{slug}.domain` hanya untuk warga dan
 * operator nagari itu sendiri.
 *
 * Dipasang pada seluruh grup web. Halaman publik nagari (etalase UMKM, katalog,
 * teras) tetap terbuka untuk siapa pun, termasuk warga nagari lain yang sedang
 * login. Yang dibatasi hanya area privat seperti portal dan panel back-office.
 *
 * Pemeriksaan ini WAJIB ada di lapisan middleware, bukan hanya di halaman login.
 * SESSION_DOMAIN mencakup seluruh subdomain, jadi sesi yang dibuat di satu situs
 * nagari ikut terbawa saat pengguna mengetik alamat nagari tetangga. Tanpa
 * pemeriksaan per-permintaan, batas ini hanya berlaku sekali lalu bocor.
 */
class EnsureNagariSiteMatchesUser
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $host = mb_strtolower($request->getHost());
        $baseDomain = mb_strtolower((string) config('app.public_base_domain'));
        $appHost = mb_strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $hostBasePublik = $baseDomain !== '' && $host === $baseDomain;
        $hostPengembangan = app()->environment(['local', 'testing'])
            && in_array($host, ['localhost', '127.0.0.1'], true);
        $hostInduk = $baseDomain === '' || $baseDomain === 'localhost'
            || $hostBasePublik
            || ($appHost !== '' && $host === $appHost)
            || $hostPengembangan;
        $hostWww = $baseDomain !== '' && $host === 'www.'.$baseDomain;
        $situs = $hostInduk || $hostWww ? null : Nagari::fromHost($host);

        // TrustHosts harus menerima pola *.domain karena subdomain nagari dibuat
        // dinamis. Pola itu sendiri tidak dapat membedakan slug nagari sah dari
        // hostname rekaan. Tutup hostname yang bukan domain induk, www, ataupun
        // nagari terdaftar sebelum login maupun route privat sempat dijalankan.
        if (! $hostInduk && ! $hostWww && $situs === null) {
            abort(404);
        }

        $user = $request->user();

        // Tamu diurus middleware auth di belakang ini.
        if ($user === null) {
            return $next($request);
        }

        // Halaman publik nagari tetap terbuka, termasuk bagi warga nagari lain yang
        // sedang login. Pengecualiannya memakai AWALAN nama route `public.`/`seo.`,
        // bukan daftar route satu per satu, supaya route terautentikasi yang
        // ditambahkan kemudian ikut terjaga dengan sendirinya.
        $routeName = (string) $request->route()?->getName();

        if (str_starts_with($routeName, 'public.') || str_starts_with($routeName, 'seo.')) {
            return $next($request);
        }

        // Peran lintas nagari bekerja pada ruang global, bukan di dalam tenant
        // yang namanya sedang tampil pada hostname. Login memang sudah
        // memulangkan mereka ke domain induk, tetapi cookie sesi sengaja berlaku
        // lintas subdomain. Karena itu pengguna masih dapat mengetik hostname
        // nagari lain secara manual setelah login. Jangan biarkan alamat tersebut
        // menyiratkan konteks tenant yang sebenarnya tidak sedang diterapkan.
        //
        // GET/HEAD aman dikanonisasi sambil mempertahankan path dan query string.
        // Permintaan yang dapat mengubah data ditolak, bukan diteruskan dengan
        // redirect lintas host: aksi harus dimulai ulang dari domain induk agar
        // tidak ada POST/Livewire lama yang dieksekusi dalam host yang keliru.
        if ($situs !== null && $user->isLintasNagari()) {
            if (! $request->isMethodSafe()) {
                abort(403, 'Panel lintas nagari hanya dapat digunakan melalui domain utama.');
            }

            $domainInduk = rtrim((string) config('app.url'), '/');

            return redirect()->to($domainInduk.$request->getRequestUri());
        }

        // `www` boleh menjadi alias situs publik, tetapi ruang privat lintas
        // nagari hanya memiliki satu hostname kanonik: APP_URL. Ini menghindari
        // dua alamat panel dengan konteks yang tampak berbeda.
        if ($hostWww) {
            if (! $request->isMethodSafe()) {
                abort(403, 'Area privat hanya dapat digunakan melalui alamat situs yang resmi.');
            }

            return redirect()->to(rtrim((string) config('app.url'), '/').$request->getRequestUri());
        }

        // Operator adalah pengelola satu tenant. Bila ia masuk dari domain utama,
        // bawa ke subdomain nagarinya; jangan membiarkan panel operator mempunyai
        // alamat global yang dapat disalahartikan sebagai akses lintas nagari.
        if ($hostBasePublik && $user->isOperator() && ! $user->isLintasNagari()) {
            $nagari = $user->nagari;

            if ($nagari === null) {
                abort(403, 'Akun operator belum terhubung ke nagari.');
            }

            if (! $request->isMethodSafe()) {
                abort(403, 'Panel operator hanya dapat digunakan melalui subdomain nagarinya.');
            }

            $port = $request->getPort();
            $portTambahan = in_array($port, [80, 443], true) ? '' : ':'.$port;
            $alamatNagari = $request->getScheme().'://'.$nagari->slug.'.'.$baseDomain.$portTambahan;

            return redirect()->to($alamatNagari.$request->getRequestUri());
        }

        // Domain induk atau situs nagari milik pengguna: lanjutkan.
        if ($situs === null || $user->bolehMasukSitusNagari($situs)) {
            return $next($request);
        }

        // Pesan sengaja menyebut nagari SITUS, bukan nagari asal pengguna, dan tidak
        // membocorkan alamat rumahnya. Selain lebih ringkas, keluaran penolakan jadi
        // seragam untuk semua orang sehingga tak bisa dipakai menerka keanggotaan.
        abort(403, "Anda tidak terdaftar di Nagari {$situs->nama}.");
    }
}
