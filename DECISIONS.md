# DECISIONS.md — Smart Learning Center Basamo NCH

> Log semua keputusan arsitektur dan teknologi beserta alasannya.
> Setiap kali ada keputusan baru atau perubahan keputusan, catat di sini.
> Format: tanggal · keputusan · alasan · alternatif yang ditolak

---

### [2026-06] NagariResource (M1) — manajemen nagari + SoftDeletes
**Keputusan**: `NagariResource` (grup "Pengaturan"), **hanya super_admin** (`NagariPolicy` semua false; super via Gate::before; nagari_admin ditolak). Form: Identitas (nama, kode unik→UPPERCASE) · Wilayah (provinsi/kab/kec + koordinat) · Kontak & Status. Tabel: jumlah warga & modul (withCount), badge status.
- **SoftDeletes** ditambah ke tabel `nagaris` (arsip + restore).
- **Guard anti-orphan**: nagari tak bisa dihapus selama masih punya pengguna/modul (`NagariResource::guardAgainstDependents()` + `$action->halt()` + notifikasi). Force delete cek termasuk yang sudah di-soft-delete (FK `nullOnDelete` akan men-null-kan saat hard delete). Hapus massal dihilangkan agar guard per-record selalu jalan.
**Alasan**: Nagari = akar multi-tenancy berskala nasional; menghapus tanpa guard akan meng-orphan warga/modul. SoftDeletes memberi jaring pengaman + jejak audit.
**Verifikasi**: `tests/Feature/NagariResourceTest.php` (4 lulus): create + normalisasi kode, tolak hapus bila ada warga, arsip nagari kosong, penolakan nagari_admin.

### [2026-06] UserResource (M2) — manajemen pengguna oleh admin
**Keputusan**: Panel `/admin` punya `UserResource` (grup "Pengaturan").
- **super_admin**: CRUD semua pengguna lintas nagari, set semua peran; peran `super_admin` → `nagari_id` null (global).
- **nagari_admin**: hanya pengguna di nagarinya (scope di `getEloquentQuery` + `getRecordRouteBindingEloquentQuery`), hanya boleh buat/kelola `warga`/`umkm_owner`; `nagari_id` dipaksa ke miliknya (field nagari disembunyikan).
- **Pengaman**: password di-hash (cast) & opsional saat edit (`dehydrated(filled)` + `confirmed`, min 8); tak bisa hapus akun sendiri (aksi disembunyikan); tak bisa menurunkan peran/menonaktifkan diri sendiri (`mutateFormDataBeforeSave`). `UserPolicy` berbasis role (super_admin via Gate::before).
- Peran disinkronkan otomatis ke Spatie via observer `User::booted` (lihat keputusan RBAC).
**Alasan**: Super_admin & admin nagari perlu kelola akun lewat UI (bukan tinker/seeder) — fungsi inti operasional. Pola tenant-admin multi-tenant standar.
**Verifikasi**: `tests/Feature/UserResourceTest.php` (4 lulus): create+hash+sync role, scope nagari_admin, larangan peran admin oleh nagari_admin, hapus-diri disembunyikan.
**Ditunda**: impersonate, 2FA admin, audit-log UI, bulk-import — belum diperlukan MVP.

### [2026-06] RBAC: kolom `role` = sumber kebenaran tunggal (audit super_admin)
**Keputusan**: Otoritas diturunkan dari **kolom `users.role`**, bukan Spatie role.
- super_admin bypass via `Gate::before` di `AppServiceProvider` yang cek `isSuperAdmin()` (kolom). Tak lagi bergantung pada `hasRole()` Spatie → tak ada "admin hantu".
- Policy (`ModulePolicy`/`QuizPolicy`/`QuizAttemptPolicy`/`RolePolicy`) ditulis berbasis role (`isNagariAdmin()`), bukan permission granular Spatie. Memperbaiki bug `nagari_admin` 0-permission → panel kosong (H1).
- `User::booted()` menambahkan observer: saat kolom `role` berubah, Spatie role disinkronkan otomatis (`syncRoles`) agar Shield/`hasRole()` tetap konsisten. Spatie tetap ada (Shield) tapi tak lagi otoritatif.
- `canAccessPanel()` kini mensyaratkan `status === 'active'` (B2) — admin nonaktif diblokir.
- `QuizResource::getRecordRouteBindingEloquentQuery()` ditambah scoping nagari (L2) — `nagari_admin` tak bisa buka kuis nagari lain via URL.
- Migration: `modules.created_by` → nullable + `nullOnDelete` (L1).
**Alasan**: Desain proyek = 4 role tetap & dikelola di kode (nav Role disembunyikan). Satu sumber kebenaran menghapus risiko desync kolom vs Spatie role. Diverifikasi via `tests/Feature/SuperAdminAccessTest.php` (6 test).
**Ditolak**: Spatie sebagai sumber tunggal (invasif ke middleware portal yang sudah pakai kolom role); pertahankan dua sumber + sekadar observer (tetap menyisakan ketergantungan ganda).

### [2026-06] Login admin: username ATAU email
**Keputusan**: Panel `/admin` bisa login dengan **username maupun email** di satu field. Kolom `username` (nullable, unique) ditambah ke `users`; migration backfill username dari bagian lokal email (dijamin unik) agar akun lama tetap bisa login. Custom Login page `App\Filament\Auth\Login` (extends `Filament\Auth\Pages\Login`) override `getEmailFormComponent()` → field `login`, dan `getCredentialsFromFormData()` deteksi email via `FILTER_VALIDATE_EMAIL` lalu pilih kolom `email`/`username`. Didaftarkan via `->login(\App\Filament\Auth\Login::class)`.
**Alasan**: Warga/admin nagari lebih hafal username daripada email; tetap mendukung email untuk yang terbiasa. Tanpa ubah guard/provider — cukup ubah kredensial yang dikirim ke `attempt()`.
**Catatan lokasi**: Login page diletakkan di `app/Filament/Auth/` (BUKAN `app/Filament/Pages/`) supaya tidak ikut ter-`discoverPages` & terdaftar sebagai page biasa (bisa bentrok rute).
**Ditolak**: Username-saja (buang email) — kurang fleksibel utk akun yang sudah pakai email; ubah `config/auth` provider — tak perlu, override di Login page lebih lokal.

---

## Arsitektur & Framework

### [2026-06] Arsitektur 3 lapisan dalam satu proyek Laravel (REVISI FINAL)
**Keputusan**: Satu proyek Laravel + satu database, dengan TIGA lapisan presentasi berbeda:
1. **Frontend Publik** (Blade + Tailwind biasa) — halaman utama berisi katalog UMKM, detail produk, profil nagari, berita. Tanpa login, dioptimasi SEO & kecepatan. Controller Laravel standar.
2. **Portal Warga LMS** (Blade + Livewire/Alpine secukupnya) — warga belajar, kuis, leaderboard. Pemilik UMKM input produk di sini. UX ramah HP, scalable untuk ratusan ribu user. BUKAN panel Filament.
3. **Panel Admin** (Filament v5) — Super Admin & Admin Nagari. Back-office: CRUD modul/SDGs, verifikasi UMKM, dashboard. Di sinilah Filament dipakai.
**Alasan**: Katalog UMKM tampil di halaman utama publik → wajib custom (SEO, cepat, tanpa login). Warga = mayoritas user dengan beban concurrency tertinggi pada skala nasional ribuan desa → custom frontend lebih ringan & scalable daripada panel Filament yang membawa overhead. Admin = back-office murni → Filament memberi nilai maksimal. Satu database menjaga semua data tetap terpadu.
**Ditolak**: (A) Semua Filament termasuk portal warga — UX terasa "dashboard admin", overhead berat untuk user massal di koneksi lambat. (B) Dua/lebih proyek terpisah — duplikasi kode & sinkronisasi DB rumit. (C) Filament untuk halaman publik — Filament tidak dirancang untuk halaman publik tanpa login & SEO.

### [2026-06] Multi-tenancy via nagari_id di semua tabel
**Keputusan**: Semua tabel menyimpan `nagari_id` sebagai foreign key. Satu database shared, bukan database terpisah per nagari.
**Alasan**: Filament Tenancy menangani scope otomatis di panel admin. Custom query di portal & frontend publik scope manual via `nagari_id`. Lebih mudah agregasi data lintas nagari di dashboard Super Admin. Cocok untuk skala nasional dengan indeks `nagari_id` yang tepat.
**Ditolak**: Database per nagari — overkill untuk skala saat ini, sulit agregasi lintas nagari.

### [2026-06] Auth pakai Filament bawaan — TANPA Jetstream (REVISI)
**Keputusan**: Tidak memakai Jetstream sama sekali. Auth Filament untuk panel admin (canAccessPanel cek role). Portal warga pakai auth Laravel + middleware. Satu model `User`.
**Alasan**: Filament v5 sudah punya auth lengkap (login, register, profile, password reset) per panel. Jetstream menyebabkan konflik versi Livewire (Jetstream butuh `^3.x`, Filament v5 butuh `^4.1`). Filament Shield + Spatie Permission menangani RBAC.
**Ditolak**: Jetstream — konflik dependency Livewire dengan Filament v5 dan menambah kompleksitas yang tidak perlu. (Keputusan awal yang memilih Jetstream dibatalkan setelah ditemukan konflik saat instalasi.)

---

## Frontend & UI

### [2026-06] Heroicons sebagai satu-satunya icon set
**Keputusan**: Hanya Heroicons. Tidak boleh install icon set lain.
**Alasan**: Already built-in di Filament. Konsistensi visual terjaga. Cukup ~1.300 icon untuk semua kebutuhan.
**Ditolak**: Phosphor Icons, Material Icons — memecah konsistensi visual.

### [2026-06] Filament Notifications untuk semua toast & alert
**Keputusan**: Pakai Filament Notifications built-in saja.
**Alasan**: Sudah terintegrasi penuh dengan Livewire dan Filament Actions. Mendukung database notification untuk in-app notification center.
**Ditolak**: Toastr, SweetAlert2, Notyf — akan menciptakan dua sistem notifikasi yang bertabrakan.

### [2026-06] RichEditor bawaan Filament untuk konten modul LMS (REVISI)
**Keputusan**: Pakai RichEditor bawaan Filament untuk konten modul. Video YouTube/Google Drive di-handle via field URL terpisah lalu di-embed di frontend portal.
**Alasan**: Plugin Tiptap (`awcodes` maupun `canyongbs`) tidak support Filament v5 — hanya sampai v4. RichEditor bawaan Filament sudah cukup: bold, italic, heading, list, link, dan embed gambar. Tidak perlu dependency tambahan.
**Ditolak**: `awcodes/filament-tiptap-editor` dan `canyongbs/filament-tiptap-editor` — keduanya tidak kompatibel dengan Filament v5. (Keputusan awal yang memilih Tiptap dibatalkan setelah ditemukan inkompatibilitas saat instalasi.)

### [2026-06] ApexCharts via leandrocfe/filament-apex-charts
**Keputusan**: Satu library chart untuk semua visualisasi dashboard.
**Alasan**: Native integration dengan Filament Widget system. Support semua tipe chart yang dibutuhkan: bar, line, donut, radial, gauge.
**Ditolak**: Chart.js — tidak ada integrasi native Filament. D3.js — terlalu kompleks untuk kebutuhan dashboard saat ini.

---

## Database & Storage

### [2026-06] MySQL sebagai database utama
**Keputusan**: MySQL (versi terbaru).
**Alasan**: Tim sudah familiar. Laravel support penuh. Cukup untuk skala saat ini.
**Ditolak**: PostgreSQL — lebih baik untuk data kompleks tapi tim belum familiar. MongoDB — tidak cocok untuk relasi multi-tenant.

### [2026-06] Local disk untuk MVP, Cloudflare R2 untuk produksi
**Keputusan**: Local disk saat development & demo. Migrasi ke Cloudflare R2 sebelum go-live.
**Alasan**: R2 gratis hingga 10GB/bulan tanpa biaya egress. Cocok untuk proyek pengabdian akademik. Laravel Flysystem mendukung R2 via S3-compatible driver.
**Ditolak**: AWS S3 — ada biaya egress. IDCloudHost Object Storage — perlu dievaluasi lebih lanjut.

### [2026-06] Spatie Media Library untuk manajemen file
**Keputusan**: `spatie/laravel-medialibrary` + Filament plugin.
**Alasan**: 32M+ download, dokumentasi lengkap, terintegrasi native dengan Filament form fields. Mendukung konversi & optimasi gambar otomatis via Intervention Image.
**Ditolak**: Upload manual custom — tidak ada manfaat reinventing the wheel.

---

## Ekspor & Laporan

### [2026-06] DomPDF untuk ekspor PDF
**Keputusan**: `barryvdh/laravel-dompdf` untuk laporan SDGs dan LMS.
**Alasan**: Paling populer di ekosistem Laravel, dokumentasi sangat lengkap, mudah dipakai dengan Blade template.
**Ditolak**: Browsershot (Puppeteer) — perlu Node.js, kompleksitas lebih tinggi. Snappy — perlu wkhtmltopdf binary.

### [2026-06] Maatwebsite Excel untuk ekspor spreadsheet
**Keputusan**: `maatwebsite/excel` untuk data UMKM & warga.
**Alasan**: Standar de-facto di Laravel untuk Excel, mendukung export/import, queue, dll.
**Ditolak**: PhpSpreadsheet langsung — Maatwebsite sudah wrapper-nya dengan API yang lebih bersih.

---

## Fitur & Produk

### [2026-06] Leaderboard per nagari, bukan lintas nagari
**Keputusan**: Leaderboard hanya menampilkan peringkat warga dalam satu nagari.
**Alasan**: Warga desa kecil tidak perlu bersaing dengan ribuan warga dari nagari lain. Relevansi lokal lebih memotivasi.
**Ditolak**: Leaderboard nasional — intimidatif, kurang relevan untuk konteks nagari.

### [2026-06] UMKM tanpa fitur transaksi, arahkan ke WhatsApp
**Keputusan**: Platform hanya untuk promosi. Tombol "Hubungi via WhatsApp" untuk transaksi.
**Alasan**: Mengurangi kompleksitas sistem secara signifikan. UMKM lokal sudah familiar dengan WhatsApp. Menghindari kebutuhan payment gateway di fase awal.
**Ditolak**: Fitur cart & checkout — kompleksitas tinggi, membutuhkan payment gateway, bukan fokus utama program NCH.

### [2026-06] Onboarding nagari baru manual oleh Super Admin
**Keputusan**: Super Admin yang mendaftarkan nagari baru secara manual.
**Alasan**: Pilot awal hanya 3 nagari. Kontrol kualitas lebih terjaga. Self-service onboarding akan dibuat di Fase 3 saat skala nasional.
**Ditolak**: Self-service langsung — risiko data tidak valid, perlu validasi lokasi nagari.

### [2026-06] Kuis hanya pilihan ganda + modul tanpa thumbnail
**Keputusan**: Soal kuis ditetapkan **hanya pilihan ganda** (auto-grade). Tipe essay dihapus total beserta antrian penilaiannya (QuizAttemptResource "Review Essay", AnswersRelationManager, LmsEssayGradingService, halaman ReviewQuizAttempt). Modul tidak lagi punya thumbnail. Kolom DB tak terpakai di-drop via migration `drop_essay_and_thumbnail_columns`: `modules.thumbnail`, `quiz_questions.type`, `quiz_answers.answer_text/feedback`, `quiz_attempts.reviewed_at/reviewed_by`, dan status `pending_review` dihapus dari enum `quiz_attempts.status`.
**Alasan**: Menyederhanakan alur LMS — kuis langsung dinilai otomatis tanpa beban kerja Admin Nagari. Thumbnail tidak esensial untuk MVP dan menambah kerumitan upload/storage. Menghapus kode mati menjaga konsistensi & mengurangi bug.
**Ditolak**: Mempertahankan essay & antrian review — menambah beban admin & kompleksitas; biarkan kolom DB sebagai cruft — memilih skema bersih karena masih pra-rilis (data hanya uji).
**Catatan**: Penyesuaian ikut dilakukan di portal warga (QuizPlayer MC-only, hapus tampilan thumbnail di detail modul) dan dokumentasi (DATABASE.md, PRD.md).

### [2026-06] Scoping nagari admin via manual query (belum Filament Tenancy)
**Keputusan**: Isolasi data nagari_admin diterapkan manual lewat `getEloquentQuery`/`getRecordRouteBindingEloquentQuery` + default form (helper `User::isNagariAdmin()`), bukan Filament Tenancy penuh. super_admin lihat & kelola semua (termasuk modul global); nagari_admin hanya modul/kuis nagarinya.
**Alasan**: Cukup untuk kebutuhan saat ini tanpa kompleksitas tenant-switcher Filament. Mudah dipahami & langsung mengunci kebocoran data antar-nagari.
**Ditolak**: Filament Tenancy penuh sekarang — overkill untuk skala pilot; bisa diadopsi nanti bila perlu multi-tenant UX.

### [2026-06] Leaderboard dummy dulu, sistem poin menunggu kesepakatan
**Keputusan**: Halaman leaderboard portal dibuat dengan DATA CONTOH (front-end) lebih dulu. `LmsPointService` & update `total_points` nyata ditunda sampai skema penilaian poin disepakati bersama.
**Alasan**: Tampilan/navigasi bisa dimantapkan lebih dulu; aturan poin (bobot per aktivitas, reset, anti-curang) perlu diskusi agar tidak salah desain.
**Ditolak**: Implementasi poin sekarang dengan asumsi sepihak — berisiko harus dirombak setelah kesepakatan.

### [2026-06] Menu Role disembunyikan + urutan modul/materi/soal otomatis
**Keputusan**: (1) Menu "Role" (Filament Shield) disembunyikan dari navigasi via `registerNavigation(false)` — 4 role tetap & dikelola di kode/seeder. (2) Urutan modul, materi, dan soal di-assign otomatis (`order` = max+1 saat dibuat); modul & relasi materi/soal dapat di-drag untuk reorder; field angka urutan modul dihapus dari form.
**Alasan**: Mengurangi kesalahan (salah klik permission), dan menyederhanakan input admin — urutan mengikuti urutan pembuatan, tetap bisa diatur ulang via drag.
**Ditolak**: Field urutan manual — kurang intuitif; menu Role tampil — berisiko & jarang dipakai.

### [2026-06] Nilai kuis: angka 0–100, semua soal setara (tanpa bobot poin & tanpa tanda %)
**Keputusan**: Nilai kuis adalah **angka skala 0–100** (bukan persentase) — tanda `%` dihapus dari admin & portal. Bobot poin per soal dihapus (kolom `quiz_questions.points` & `quiz_answers.score_given` di-drop via migration `drop_points_from_quiz`). Semua soal setara: `nilai = (jumlah benar ÷ jumlah soal) × 100`. `passing_score` tetap 0–100.
**Alasan**: "Nilai itu angka, bukan persentase" (feedback user). Bobot poin per soal membingungkan (kesan max = Σpoin) padahal nilai dinormalisasi; tanpa poin, nilai selalu 0–100 dan ambang lulus 1–100 selalu mungkin dicapai → human-error "passing > maks" mustahil terjadi. Input admin lebih sederhana.
**Ditolak**: Model aditif (nilai = Σpoin, maks = Σpoin) — rapuh terhadap perubahan jumlah soal & rawan salah set ambang lulus.

### [2026-06] Batas upload PDF materi = 10 MB (samakan semua lapisan)
**Keputusan**: Maks PDF materi modul **10 MB**. Disamakan di: Filament `maxSize(10240)`, dan **PHP** `upload_max_filesize=10M` + `post_max_size=12M` (Livewire default 12 MB sudah cukup). Produksi (nginx) wajib `client_max_body_size 12M`.
**Alasan**: Sebelumnya Filament 10 MB tapi PHP hanya 2M/8M → limit efektif ~2 MB & upload >2 MB gagal membingungkan. 10 MB cukup untuk materi sekaligus ramah kuota warga (akses HP).
**Cara terapkan**:
- Dev (`artisan serve` pakai CLI ini): `sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 10M/; s/^post_max_size = .*/post_max_size = 12M/' /etc/php/8.3/cli/php.ini`
- Produksi: ubah hal sama di `/etc/php/8.3/fpm/php.ini` lalu `sudo systemctl restart php8.3-fpm`; nginx `client_max_body_size 12M;`.

### [2026-06] Slug modul stabil (tidak berubah saat judul diedit)
**Keputusan**: `Module::getSlugOptions()->doNotGenerateSlugsOnUpdate()`. Slug dibuat sekali saat create; edit judul tidak mengubah slug.
**Alasan**: URL stabil + menghapus inkonsistensi field slug readOnly (tampil lama tapi tersimpan baru). User OK dengan kedua perilaku; dipilih yang lebih bersih & tanpa downside.

### [2026-06] Leaderboard DITIADAKAN (membatalkan rencana leaderboard & sistem poin)
**Keputusan**: Fitur leaderboard dihapus seluruhnya (controller, halaman, slot bottom nav). Sistem poin (`LmsPointService`, `total_points`) ditunda tanpa target — tidak dikerjakan. Bottom nav kembali 2 item (Beranda, Modul). Membatalkan keputusan "Leaderboard dummy dulu" di atas.
**Alasan**: Peringkat kompetitif **bias & kontraproduktif** untuk konteks warga nagari: (1) bias akses/kesempatan (HP, kuota, waktu, literasi) — mengukur privilese bukan belajar; (2) mudah digoreng (klik halaman / ulang kuis) → ukur volume bukan pemahaman; (3) demotivasi peserta peringkat bawah; (4) populasi kecil per nagari → rangking berisik; (5) misalignment dgn tujuan komunitas/SDGs (bukan kompetisi). Motivasi cukup dari **progres pribadi** yang sudah tampil di Beranda.
**Ditolak**: Leaderboard dummy/nyata; gamifikasi kompetitif — risiko > manfaat di konteks ini. (Opsi non-kompetitif spt badge pribadi / progres kolektif nagari bisa dipertimbangkan nanti bila perlu.)

### [2026-06] Sistem XP + Leaderboard XP (MEMBATALKAN penghapusan leaderboard di atas)
**Keputusan**: Adakan XP berbasis pencapaian + peringkat XP per nagari. Besaran (masing-masing **sekali per modul**, idempotent): selesai semua materi modul **+50**, lulus kuis modul **+100**, partisipasi diskusi modul (posting pertama) **+20** → maks 170/modul. XP akumulatif di `users.total_points`; ledger `xp_logs` dengan UNIQUE(user, source, source_id) menjamin sekali-saja. Leaderboard per nagari urut total_points; dashboard tampil Top 5 + posisi user + "Lihat Semua".
**Alasan**: XP diikat ke penyelesaian + kelulusan + partisipasi yang berbatas per modul → jauh lebih sulit "digoreng" daripada poin per-klik, dan memotivasi. User memutuskan trade-off bias (akses/waktu) dapat diterima dengan desain ini.
**Mitigasi bias**: per nagari saja; XP dari pencapaian nyata (bukan volume klik); kuis flat (tak ada insentif ngulang); diskusi sekali per modul (anti-spam).
**Implementasi**: `LmsPointService` (award*), hook di LmsProgressService (modul), QuizPlayer (kuis), DiscussionController (diskusi). Idempotent diverifikasi.

### [2026-06] Polish UI: komponen Blade sendiri + canvas-confetti (1 dependency)
**Keputusan**: Konsistensi UI portal dicapai lewat **komponen Blade sendiri** (`components/portal/`: breadcrumb, button, card, badge, progress, stat, toast + avatar/status-badge/content-badge/empty), bukan UI-kit eksternal. Satu-satunya dependency baru: **`canvas-confetti`** (npm) untuk perayaan (lulus kuis / modul selesai). Toast = Alpine+Livewire (`dispatch('toast')`); animasi via Alpine `x-transition` + Tailwind.
**Alasan**: Sudah ada desain Tailwind custom; komponen sendiri menjaga konsistensi penuh tanpa biaya/churn. canvas-confetti: ~6 KB, zero-dep, MIT, standar.
**Ditolak**: Flux (Breadcrumbs & Toast tier berbayar + perlu restyle); WireUI/Mary/daisyUI (bawa design-system sendiri, bentrok); SweetAlert2/Toastr (Toastr dilarang; gaya beda); GSAP/AOS (berlebihan).
**Mekanisme perayaan**: lulus kuis → QuizPlayer dispatch `confetti`+`toast`; modul selesai → flash `celebrate` → script page.blade memicu confetti+toast.

### [2026-06] Akses UMKM = kapabilitas, bukan role (MEMBATALKAN role `umkm_owner`)
**Keputusan**: `umkm_owner` **dihapus** sebagai role. Role = persona stabil: `super_admin`, `nagari_admin`, `warga`. Akses UMKM jadi **kapabilitas** di atas `warga`, ditandai kolom `users.umkm_access_granted_at` (timestamp nullable). Aksi admin "Beri/Cabut akses UMKM" set/null timestamp (tak ubah peran). Cek via `User::hasUmkmAccess()`. Kolom `role` diubah dari enum native → `string(20)`.
**Alasan**: Pemilik UMKM tetap warga (tetap belajar/kuis/leaderboard); UMKM cuma menu tambahan → secara semantik *kapabilitas*, bukan persona. Role tunggal per kolom tak bisa dikomposisi (warga DAN umkm), dan dengan 4 pilar berisiko **ledakan kombinatorial** role. Flag kapabilitas independen komposabel → aman untuk pilar berikutnya (SDGs/IoT). Best practice RBAC: role = SIAPA, permission/capability = APA.
**Ditolak**: (a) Spatie permission `umkm.manage` — lebih scalable tapi mesin lebih berat tanpa untung selama belum ada sub-izin dalam satu pilar; dipakai nanti bila perlu granularitas. (b) Biarkan role `umkm_owner` — tak salah untuk app kecil, tapi mulai menyusahkan saat kapabilitas pilar bertambah.

### [2026-06] OTP awal punya masa berlaku (security)
**Keputusan**: `users.otp_expires_at` (7 hari, `User::OTP_TTL_DAYS`). Login portal menolak OTP kedaluwarsa → minta admin reset. `initial_otp` (plaintext untuk relay admin) + expiry dihapus saat warga ganti sandi.
**Alasan**: Kredensial sementara tanpa expiry = "sandi abadi"; bila DB bocor, akun yang belum login langsung jebol. Expiry membatasi jendela paparan.
**Ditolak**: Hash `initial_otp` — admin tak bisa lagi relay kode ke warga (UX provisioning NIK+OTP butuh plaintext). Kompromi: simpan plaintext tapi berbatas waktu + dihapus setelah dipakai.

### [2026-06] Integritas & taksonomi UMKM
**Keputusan**: (a) `umkm_products.harga` integer rupiah (`unsignedBigInteger`), bukan `decimal(12,2)` — Rupiah tak punya pecahan. (b) `umkm_profiles.user_id` UNIQUE → 1 warga = 1 lapak (sesuai relasi HasOne). (c) Kategori UMKM jadi tabel `umkm_categories` (taksonomi global, dikelola super_admin, punya ikon/slug/urutan) menggantikan kolom string.
**Alasan**: Standar industri uang = integer satuan terkecil; constraint DB menegakkan aturan yang sebelumnya hanya di kode; taksonomi sebagai data (bukan const PHP) → dikelola admin, punya ikon, jadi fondasi filter katalog publik.
**Ditolak**: Konversi semua enum native → string menyeluruh — ROI tipis untuk MySQL + set nilai stabil, churn lebar (tiap form/tabel/test); ditunda jadi pass terfokus bila target DB berubah / set nilai bertambah. `umkm_profiles.user_id` HasMany (1 warga banyak usaha) = perluasan produk, bukan perbaikan; ditunda.

```
### [YYYY-MM] Judul keputusan
**Keputusan**: Apa yang diputuskan.
**Alasan**: Mengapa keputusan ini dibuat.
**Ditolak**: Alternatif yang dipertimbangkan dan alasan penolakannya.
```
