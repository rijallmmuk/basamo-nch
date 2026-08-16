<?php

use App\Http\Controllers\Admin\AdminPreviewController;
use App\Http\Controllers\Admin\NagariBoundaryController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DiscussionController;
use App\Http\Controllers\Portal\EvaluasiController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\MateriController;
use App\Http\Controllers\Portal\ModuleController;
use App\Http\Controllers\Portal\ModuleFileController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PasswordController;
use App\Http\Controllers\Portal\PelatihanController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SertifikatController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\IotCatalogController;
use App\Http\Controllers\Public\KontakController;
use App\Http\Controllers\Public\NagariHomeController;
use App\Http\Controllers\Public\PublicMapController;
use App\Http\Controllers\Public\PelatihanPublikController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\SertifikatVerifikasiController;
use App\Http\Controllers\Public\SlcCatalogController;
use App\Http\Controllers\Public\TerasCatalogController;
use App\Http\Controllers\Public\UmkmCatalogController;
use App\Models\Nagari;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

// Harus dinamis karena setiap subdomain merupakan situs tersendiri dan membutuhkan
// sitemap dengan hostname yang sama. Berkas statis public/robots.txt sengaja tidak dipakai.
Route::get('robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

// ── Subdomain per nagari: {slug}.basamonch.com → wajah publik nagari itu ───
// Terdaftar PALING ATAS: route tanpa domain cocok di host mana pun, jadi grup
// ber-domain harus menang duluan. Route lain (detail produk, dll) tetap bekerja
// di subdomain karena route() memakai host request saat ini.
// `www` DIKECUALIKAN: tanpa ini `www.basamonch.com` diperlakukan sebagai slug
// nagari, tidak ketemu, lalu 404 — situs utama mati bagi siapa pun yang mengetik
// www. Dengan pengecualian ini grup domain tidak cocok, permintaan jatuh ke route
// apex di bawah, dan www menyajikan situs induk seperti seharusnya.
Route::domain('{nagari:slug}.'.config('app.public_base_domain'))
    // Lookahead memakai `www\.` (bukan `www$`): pola ini disisipkan ke regex host
    // utuh, sehingga `$` akan menunjuk akhir seluruh host dan tak pernah cocok.
    // Titiknya yang menandai batas akhir parameter. Slug seperti `wwwx` tetap sah.
    ->where(['nagari' => '(?!www\.)[A-Za-z0-9][A-Za-z0-9-]*'])
    ->group(function () {
    Route::get('/', [NagariHomeController::class, 'index'])->name('public.nagari.home');
    Route::get('teras-nagari', [NagariHomeController::class, 'teras'])->name('public.nagari.teras');
    Route::get('medan-nan-balinduang', [SlcCatalogController::class, 'nagari'])->name('public.nagari.slc');
    // Halaman muka pelatihan: etalase terbuka. Isi materi, berkas, dan evaluasi
    // tetap di balik login; lihat PelatihanPublikController.
    Route::get('medan-nan-balinduang/{pelatihan}', [PelatihanPublikController::class, 'nagari'])->name('public.nagari.pelatihan');
    Route::get('lapau-nagari', [UmkmCatalogController::class, 'directory'])->name('public.nagari.umkm');
    Route::get('medan-nan-bapaneh', [NagariHomeController::class, 'bapaneh'])->name('public.nagari.bapaneh');

    Route::get('lapau-nagari/{umkmProfile:slug}', [UmkmCatalogController::class, 'etalase'])->name('public.nagari.umkm.etalase');
    Route::get('lapau-nagari/produk/{product:slug}', [UmkmCatalogController::class, 'show'])->name('public.nagari.produk');

    // Ejaan lama "medang" dipertahankan sebagai pengalihan agar bookmark tidak putus.
    Route::get('medang-nan-balinduang', fn (Nagari $nagari) => redirect()->route('public.nagari.slc', $nagari, 301));
    Route::get('medang-nan-balinduang/{pelatihan}', fn (Nagari $nagari, string $pelatihan) => redirect()->route('public.nagari.pelatihan', [$nagari, $pelatihan], 301));
});

// Fallback lokal tanpa subdomain (misal /n/{nagari:slug})
Route::prefix('n/{nagari:slug}')->group(function () {
    Route::get('/', [NagariHomeController::class, 'index'])->name('public.nagari.home.fallback');
    Route::get('teras-nagari', [NagariHomeController::class, 'teras'])->name('public.nagari.teras.fallback');
    Route::get('medan-nan-balinduang', [SlcCatalogController::class, 'nagari'])->name('public.nagari.slc.fallback');
    Route::get('medan-nan-balinduang/{pelatihan}', [PelatihanPublikController::class, 'nagari'])->name('public.nagari.pelatihan.fallback');
    Route::get('lapau-nagari', [UmkmCatalogController::class, 'directory'])->name('public.nagari.umkm.fallback');
    Route::get('medan-nan-bapaneh', [NagariHomeController::class, 'bapaneh'])->name('public.nagari.bapaneh.fallback');

    Route::get('lapau-nagari/{umkmProfile:slug}', [UmkmCatalogController::class, 'etalase'])->name('public.nagari.umkm.etalase.fallback');
    Route::get('lapau-nagari/produk/{product:slug}', [UmkmCatalogController::class, 'show'])->name('public.nagari.produk.fallback');

    Route::get('medang-nan-balinduang', fn (Nagari $nagari) => redirect()->route('public.nagari.slc.fallback', $nagari, 301));
    Route::get('medang-nan-balinduang/{pelatihan}', fn (Nagari $nagari, string $pelatihan) => redirect()->route('public.nagari.pelatihan.fallback', [$nagari, $pelatihan], 301));
});

// ── Route Publik Induk (Global) ──────────────────────────────────────────
// Route ini khusus untuk halaman beranda utama aplikasi (di luar nagari)
Route::get('/', [PublicHomeController::class, 'index'])->name('public.home');
Route::get('teras-nagari', [TerasCatalogController::class, 'index'])->name('public.teras');
Route::get('medan-nan-balinduang', [SlcCatalogController::class, 'global'])->name('public.slc');
Route::get('medan-nan-balinduang/{pelatihan}', [PelatihanPublikController::class, 'global'])->name('public.pelatihan');
Route::get('lapau-nagari', [UmkmCatalogController::class, 'globalDirectory'])->name('public.umkm');
Route::get('lapau-nagari/produk/{product:slug}', [UmkmCatalogController::class, 'globalShow'])->name('public.produk');
Route::get('lapau-nagari/{umkmProfile:slug}', [UmkmCatalogController::class, 'globalEtalase'])->name('public.umkm.etalase');
Route::get('medan-nan-bapaneh', [PublicHomeController::class, 'bapaneh'])->name('public.bapaneh');
Route::get('iot', [IotCatalogController::class, 'index'])->name('public.iot');

// Alamat katalog lama tetap hidup untuk bookmark dan hasil mesin pencari.
Route::redirect('belajar', '/medan-nan-balinduang', 301);
Route::get('belajar/{pelatihan}', fn (string $pelatihan) => redirect()->route('public.pelatihan', $pelatihan, 301));
Route::redirect('umkm', '/lapau-nagari', 301);

// Ejaan lama "medang", padanan dari pengalihan di grup nagari dan grup fallback.
Route::get('medang-nan-balinduang', fn () => redirect()->route('public.slc', [], 301));
Route::get('medang-nan-balinduang/{pelatihan}', fn (string $pelatihan) => redirect()->route('public.pelatihan', $pelatihan, 301));
// Verifikasi keaslian sertifikat. Terbuka untuk umum: yang memeriksa biasanya pemberi
// kerja atau panitia, bukan pemilik akun.
//
// Alamatnya sengaja pendek karena dicetak di sertifikat dan sering diketik ulang dari
// kertas. Bentuk ini adalah janji jangka panjang: ia beredar di berkas yang tidak dapat
// ditarik kembali, jadi jangan diubah lagi setelah ada sertifikat yang terbit.
Route::get('verify/{nomor}', SertifikatVerifikasiController::class)
    ->name('public.sertifikat.verifikasi');
Route::post('kontak', [KontakController::class, 'store'])->name('public.kontak.store');
Route::get('peta-data', [PublicMapController::class, 'data'])->name('public.peta.data');



// Endpoint peta batas nagari untuk panel (/panel) — ter-scope ke nagari admin (operator).
// Nama route tetap `admin.nagari.boundary*` (identifier internal, dipakai via route()).
Route::middleware('auth')
    ->get('panel/nagari/peta-batas', [NagariBoundaryController::class, 'show'])
    ->name('admin.nagari.boundary');

// Peta batas nagari tertentu (halaman Ringkasan Nagari) — diautorisasi per-record
// (superadmin lintas-nagari; operator hanya nagarinya) via Policy view.
Route::middleware('auth')
    ->get('panel/nagari/{nagari}/peta-batas', [NagariBoundaryController::class, 'showForNagari'])
    ->withTrashed()
    ->name('admin.nagari.boundary.show');

// Buka satu notifikasi → tandai ITU dibaca lalu arahkan ke tujuannya (lonceng portal warga).
Route::middleware('auth')
    ->get('notifikasi/{id}/buka', [NotificationController::class, 'open'])
    ->name('notifikasi.open');

// ── Login gabungan (semua peran) ──────────────────────────────────────
// Satu halaman login untuk warga (NIK) dan pengelola (username).
// Nama route `login` = konvensi Laravel → Filament & middleware auth otomatis
// mengarahkan tamu ke sini.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected (akun portal warga saja). `no-store` cegah dashboard ter-cache
    // di riwayat browser (Back setelah logout tak menampilkan halaman basi).
    Route::middleware(['portal', AuthenticateSession::class, 'no-store'])->group(function () {
        // Penyimpan sandi baru untuk modal wajib-ganti di beranda. Tanpa halaman GET:
        // modalnya menumpang beranda, dan ganti sandi biasa ada di Profil.
        Route::post('ganti-sandi', [PasswordController::class, 'update'])->name('password.update');

        Route::get('/', [HomeController::class, 'index'])->name('home');

        // Notifikasi ditampilkan lewat modal di header (bukan halaman terpisah);
        // endpoint ini hanya menandai semua notifikasi sudah dibaca saat modal dibuka.
        Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

        // Profil warga: foto, kontak (email/HP), & ganti sandi. Data kependudukan
        // read-only (hanya admin yang boleh mengubah — cegah salah ubah).
        Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profil/kontak', [ProfileController::class, 'updateContact'])->name('profile.contact');
        Route::post('profil/sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('pelatihan', [PelatihanController::class, 'index'])->name('pelatihan.index');
        Route::get('pelatihan/{pelatihan}', [PelatihanController::class, 'show'])->name('pelatihan.show');
        Route::get('pelatihan/{pelatihan}/sertifikat', [SertifikatController::class, 'unduh'])->name('pelatihan.sertifikat');
        // Webinar: penanda hadir menggantikan progres modul sebagai bukti mengikuti.
        Route::post('pelatihan/{pelatihan}/hadir', [PelatihanController::class, 'tandaiHadir'])->name('pelatihan.hadir');

        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('{module:slug}', [ModuleController::class, 'show'])->name('show');
            Route::get('{module:slug}/materi/{materi}', [MateriController::class, 'show'])->name('materi.show');

            /* Berkas materi DIKECUALIKAN dari `no-store`. Middleware itu menimpa
               Cache-Control tiap balasan portal, sehingga dokumen yang sama diunduh
               ulang dari nol tiap kunjungan; satu halaman berisi beberapa PDF menjadi
               berat sekali. Alasan asli `no-store` adalah riwayat Back setelah keluar
               yang menampilkan HALAMAN basi, dan itu tetap berlaku untuk seluruh
               halaman di sekitarnya. Kebijakan cache berkas diatur controllernya:
               `private` selama sepuluh menit, tak boleh singgah di cache bersama. */
            Route::withoutMiddleware('no-store')
                ->get('{module:slug}/materi/{materi}/files/{block}', ModuleFileController::class)
                ->whereNumber('block')
                ->name('materi.files.show');
            // Tandai materi selesai (eksplisit, POST) — bukan otomatis saat dibuka.
            Route::post('{module:slug}/materi/{materi}/selesai', [MateriController::class, 'complete'])->name('materi.complete');
            // Pre-test = gerbang sebelum materi; Evaluasi Kegiatan = penutup modul.
            Route::get('{module:slug}/pretest', [EvaluasiController::class, 'pretest'])->name('pretest');
            Route::get('{module:slug}/evaluasi', [EvaluasiController::class, 'show'])->name('evaluasi');

            // Forum diskusi per modul
            Route::get('{module:slug}/discuss', [DiscussionController::class, 'index'])->name('discuss');
            Route::get('{module:slug}/discuss/{discussion}', [DiscussionController::class, 'show'])->name('discuss.show');
            // Posting dibatasi laju untuk meredam spam (anti-flood).
            Route::middleware('throttle:15,1')->group(function () {
                Route::post('{module:slug}/discuss', [DiscussionController::class, 'store'])->name('discuss.store');
                Route::post('{module:slug}/discuss/{discussion}/reply', [DiscussionController::class, 'reply'])->name('discuss.reply');
            });
        });

    });
});

// ── Pratinjau Admin (Backoffice / Panel) ──────────────────────────
Route::middleware(['web', 'auth'])->prefix('panel/preview')->name('admin.preview.')->group(function () {
    Route::get('modules/{module:slug}', [AdminPreviewController::class, 'moduleShow'])->name('modules.show');
    Route::get('modules/{module:slug}/materi/{materi?}', [AdminPreviewController::class, 'moduleMateri'])->name('modules.materi.show');
    Route::get('modules/{module:slug}/pre-test', [AdminPreviewController::class, 'pretestShow'])->name('modules.pretest.show');
    Route::get('modules/{module:slug}/evaluasi', [AdminPreviewController::class, 'evaluasiShow'])->name('modules.evaluasi.show');

    // Tanpa parameter rute: temanya dibawa lewat query karena pelatihannya bisa saja
    // belum tersimpan saat pengajar ingin melihat wujud sertifikatnya.
    Route::get('sertifikat', [AdminPreviewController::class, 'contohSertifikat'])->name('sertifikat');
});
