# DECISIONS.md — Basamo NCH

> Log keputusan arsitektur & teknologi yang **masih berlaku** (keadaan final).
> Keputusan yang sudah dibatalkan diringkas jadi catatan sejarah satu baris, bukan entri penuh.
> Format: keputusan · alasan · (alternatif ditolak bila perlu).

---

## Arsitektur & Platform

### Arsitektur 3 lapisan, satu proyek Laravel, satu database
1. **Frontend publik** (Blade + Tailwind) — katalog UMKM, detail produk, profil nagari. Tanpa login, SEO, cepat.
2. **Portal warga** (Blade + Livewire/Alpine) — belajar, kuis, leaderboard, Lapak UMKM. UX HP, scalable. BUKAN Filament.
3. **Panel admin** (Filament v5, `/admin`) — super_admin & nagari_admin. CRUD modul, verifikasi UMKM, dashboard.

**Alasan**: Katalog publik wajib custom (SEO/cepat/tanpa login). Warga = beban concurrency tertinggi → frontend ringan. Admin = back-office → Filament maksimal. Satu DB menjaga data terpadu.
**Ditolak**: Semua Filament (UX "dashboard", berat untuk user massal); proyek terpisah (duplikasi & sinkronisasi DB).

### Multi-tenancy via `nagari_id` + scoping manual
Semua tabel (kecuali global: modul global, users, taksonomi) punya `nagari_id` + index. Isolasi nagari_admin diterapkan **manual** lewat `getEloquentQuery` + `getRecordRouteBindingEloquentQuery` (bukan Filament Tenancy). super_admin lihat semua; nagari_admin hanya nagarinya.
**Alasan**: Cukup tanpa kompleksitas tenant-switcher; langsung mengunci kebocoran antar-nagari (termasuk via URL/IDOR). Filament Tenancy = overkill untuk pilot.

### Stack inti
- **MySQL** (tim familiar, support penuh). Test pakai SQLite — migrasi dibuat portabel agar lolos keduanya.

### Skema untuk skala nasional (index, kolom, retensi)
- **PK semua `bigint`** (Laravel default) — aman dari overflow di skala jutaan baris.
- **Index komposit** untuk query panas, menggantikan index satu-kolom redundan (tanpa bloat):
  `users(nagari_id,role,status,total_xp)` (leaderboard filter+sort 1 index), `quiz_attempts(user_id,quiz_id,status)`,
  `notifications(notifiable_type,notifiable_id,read_at)` (unread tiap page-load), `umkm_products(status,approved_at)`,
  `discussions(module_id,parent_id)`.
- **Retensi** tabel event (jadwal di `routes/console.php`): prune notifikasi read >90 hari + `activitylog:clean` harian.
- **Denormalisasi sengaja**: `user_module_progress.pages_completed` = JSON (1 baris/user-modul, bukan 1 baris/halaman).
- **Dipertahankan**: `email_verified_at` (bawaan Laravel), enum `quiz_attempts.status=in_progress` (ruang fitur resume).
- **Ditunda**: seragamkan bahasa kolom (LMS English vs UMKM/nagari Indonesia) = churn besar, bukan kebutuhan teknis.
**Naming**: tabel plural konsisten (`nagaris`, `wilayahs`, `umkm_profiles`).
- **Auth**: Filament bawaan (admin) + auth Laravel + middleware (portal). Satu model `User`. **TANPA Jetstream** (konflik Livewire v4 ↔ Filament v5).
- **RBAC**: Filament Shield + Spatie Permission (lihat "role = sumber kebenaran").

---

## RBAC & Keamanan

### Kolom `role` = sumber kebenaran tunggal
Otoritas diturunkan dari **`users.role`** (string(20)), bukan Spatie role. super_admin bypass via `Gate::before` (cek `isSuperAdmin()`). Policy berbasis role (`isNagariAdmin()`), bukan permission granular. Observer `User::booted` sinkronkan Spatie role saat kolom berubah (Shield tetap konsisten, tapi tak otoritatif). `canAccessPanel()` mensyaratkan `status==='active'`.
**Alasan**: 4 role tetap, dikelola di kode (menu Role disembunyikan). Satu sumber kebenaran → tak ada desync / "admin hantu".

### Akses UMKM = kapabilitas, bukan role
Role = persona stabil: `super_admin`, `nagari_admin`, `warga`. **Tak ada role `umkm_owner`.** Akses UMKM = kapabilitas di atas warga via `users.umkm_access_granted_at` (timestamp nullable), dicek `User::hasUmkmAccess()`. Aksi admin "Beri/Cabut akses UMKM" set/null timestamp.
**Alasan**: Pemilik UMKM tetap warga (tetap belajar). Role tunggal tak bisa dikomposisi; flag kapabilitas independen → aman saat pilar bertambah (SDGs/IoT). RBAC: role = SIAPA, kapabilitas = APA.
**Ditolak**: Spatie permission `umkm.manage` (mesin lebih berat tanpa sub-izin); biarkan role `umkm_owner` (menyusahkan saat kapabilitas bertambah).

### Identitas vs akun: 3 lapisan (`penduduk` / `users` / `role`) (2026-06-23)
Pisahkan **siapa orangnya** dari **akun login** dari **hak akses**:
- **Identitas → `penduduk`**: NIK (kanonik, unik), nama, demografi (FK `agama`/`status_perkawinan`/`pekerjaan`), `desa_id`, `desa_unit_id`. Orang bisa ada **tanpa akun**. SoftDeletes + LogsActivity + `BelongsToDesa`.
- **Akun → `users`**: login + aktivitas LMS/UMKM/XP, tertaut `users.penduduk_id` (**nullable, NON-UNIK**). NIK **di-mirror** ke `users.nik` hanya pada akun warga (kunci login portal); akun admin login via `username`. super_admin sistem tanpa penduduk.
- **Akses → `users.role`** (single, lihat "Kolom role"). **1 akun = 1 role.**

**Alasan**: warga tergandeng erat ke `users` (LMS/UMKM/XP), tapi identitas adalah konsep tersendiri yang reusable. NIK mirror → `Auth::attempt(['nik'=>…])` tanpa custom provider. Demografi = tabel referensi (bukan enum), seragam dgn `jenis_desa`, bisa dikelola admin. Form UserResource tetap **terpadu** (1 langkah): demografi di-upsert ke penduduk via `PendudukService` + trait `InteractsWithPenduduk`.

### Jalur ekspansi peran/jabatan masa depan — DIRANCANG, BELUM DIBANGUN (2026-06-23)
Sekarang cukup 3 peran (`super_admin`, `desa_admin`, `warga`). Kepala desa/aparat **belum dibuat** (YAGNI). Rancangan dimatangkan agar penambahan nanti **murni aditif** (migrasi/file baru, tak membongkar skema lama):
- **Peran baru** (`kepala_desa`, `aparat_desa`, …) = **baris data Spatie**, bukan kolom → **nol migrasi**. `users.role` varchar muat string apa pun. Saat itu: buka daftar di `UserForm`/`UsersTable`, tambahkan ke `canAccessPanel`, dan **disarankan pindah gating policy dari `isDesaAdmin()`-hardcoded ke permission Shield** (saat ini 0 permission di DB; Shield terpasang tapi belum dipakai) — sekali refactor, peran ke-N berikutnya nol kode.
- **1 orang ↔ banyak akun**: `users.penduduk_id` sengaja **non-unik**. Kepala desa yang mau ikut LMS = buat **akun warga terpisah** menunjuk `penduduk` yang sama (relasi `Penduduk::users()` hasMany). Skema sudah mendukung; tak ada perubahan.
- **Data jabatan & masa menjabat** = tabel baru **`jabatan`** (referensi) + **`penugasan_jabatan`** (`penduduk_id`, `jabatan_id`, `desa_id`, no/tgl/file SK, `mulai`–`selesai`, status). Menggantung di **penduduk** (orang), bukan akun → tak mengubah `penduduk`/`users`. Mendukung riwayat & pergantian pejabat.
**Ditolak**: bikin tabel `jabatan`/`penugasan_jabatan` sekarang (belum dipakai → tabel kosong); `penduduk.jabatan_id` FK tunggal (tak bisa simpan periode/SK/riwayat); many-to-many role (tak perlu, 1 akun = 1 role).

### Aturan data warga & data fondasi (2026-06-23)
- **Create warga = data lengkap wajib** (nama, NIK, tempat/tgl lahir, jenis kelamin, agama, status kawin, pekerjaan, **alamat sub-unit**); **opsional**: email & No. HP. Wajib hanya saat `create` (edit longgar agar record lama tetap bisa disunting).
- **Alamat wajib tapi graceful**: bila desa belum punya sub-unit wilayah, form menampilkan peringatan terarah ("tambahkan dulu di menu Wilayah"), bukan error/exception. Konsekuensi sengaja: admin harus konfigurasi wilayah desa sebelum membuat warga.
- **No. HP generik (bukan khusus WA)**, dinormalkan ke format internasional **`62xxxx`** saat simpan via `App\Support\PhoneNumber::normalize` (dipakai ulang oleh `UmkmProfile::normalizedWhatsapp` — DRY). Input fleksibel (`0…`/`+62…`/`62…`).
- **Jenis kelamin = `L`/`P`** (enum `JenisKelamin`, label "Laki-laki"/"Perempuan") — standar Dukcapil, terbaca di DB. **Ditolak** kode `0/1` (ambigu; ISO 5218 justru 1=laki/2=perempuan). EYD untuk semua label.
- **Data fondasi** (referensi tetap bawaan app): lookup FK kecil — `agama`(6)/`status_perkawinan`(4)/`pekerjaan`(99, EYD & sesuai Permendagri Dukcapil) + `jenis_desa`/`jenis_sub_unit` — **di-seed inline di migrasi** (dijamin ada saat `migrate`, aman jadi target FK). Wilayah (`ref_wilayah` + `wilayah_boundaries`/peta, Sumbar dulu, perluas nasional aditif) via `CoreSeeder` (data besar/ETL). Pemisahan ini disengaja, bukan diseragamkan ke satu seeder.

### Login gabungan: satu halaman untuk semua peran
Satu halaman login (`GET/POST /login`, nama route **`login`** = konvensi Laravel) untuk **warga, admin, & super admin** — semua pakai guard `web` + model `User` yang sama. Field tunggal `login`; deteksi jenis identitas: `@`→email, 16 digit→**NIK** (warga), selain itu→**username** (admin, kode nagari ±10 digit → tak pernah bentrok NIK). Setelah auth sukses + cek status aktif, **redirect per peran**: super/desa admin → `/admin`; warga → portal. Halaman login **bawaan Filament dimatikan** (`->login()` dihapus dari `AdminPanelProvider`, kelas `App\Filament\Auth\Login` dihapus); tamu yang membuka `/admin` di-fallback oleh auth handler ke `route('login')`. **Alasan**: satu pintu masuk = UX sederhana & konsisten, tanpa dua sistem login paralel. **Ditolak**: toggle/tab peran (deteksi otomatis lebih mulus); guard/provider terpisah (tak perlu, model & guard sama). Tervalidasi `UnifiedLoginTest` (warga/admin/super redirect, tamu→/admin, nonaktif, logout).

### Profil admin & user menu Filament: read-only + modal, tanpa avatar/tema
Profil bawaan Filament (`->profile()`) diganti halaman kustom **`App\Filament\Pages\Profil`** bergaya seperti profil warga: tampilan **read-only** + aksi header **"Ubah Profil"** (nama super-only, email, No. HP) & **"Ubah Keamanan"** (username super-only, sandi; **wajib `current_password`**). Nama & username admin desa **fix** (diturunkan/dikelola super admin — konsisten form Desa). Login pertama tetap modal pemblokir `ForcePasswordChange` (kini **hanya field sandi**, kontak dilengkapi lewat Profil). Panel: `->darkMode(false)` (tanpa switcher tema, palet tunggal NCH). **Topbar tetap** menampilkan avatar (trigger) + badge peran (render hook `USER_MENU_BEFORE`). Hanya **dropdown** yang diringkas jadi **Profil + Keluar** — caranya: item menu didaftarkan dengan key **`'profile'`** (bukan 'profil') sehingga **menimpa item akun default Filament** yang menampilkan header nama di dropdown (tanpa perlu override view vendor). Kolom Kode Wilayah (tabel Desa) `->copyable()->copyableState(digit-only)` — tampil "13.71.01.1001", tersalin "13710110001". Tervalidasi `AdminPasswordChangeTest`.

### Provisioning warga: NIK + OTP, dengan expiry
Akun warga dibuat admin (self-register dihapus). Login = **NIK (username 16 digit) + OTP** (sandi awal), wajib ganti saat login pertama. `users.otp_expires_at` (7 hari); login tolak OTP kedaluwarsa → admin reset. `initial_otp` plaintext (untuk relay admin) + expiry dihapus saat ganti sandi.
**Alasan**: Kredensial sementara tanpa expiry = "sandi abadi" → jendela paparan bila DB bocor. Hash OTP ditolak (admin perlu plaintext untuk relay) — kompromi: plaintext berbatas waktu + sekali pakai.

### Audit kesiapan produksi (2026-06-20) — aturan keras yang ditegakkan
Audit lapis-per-lapis (DB→model→policy→admin→portal→publik), tiap temuan + test regresi:
- **XSS**: konten RichEditor di Blade portal wajib `->sanitizeHtml()` (Filament tak menyanitasi `{!! !!}` buatan sendiri).
- **Prasyarat modul** wajib **global atau senagari** (guard form + rule) — cegah modul terkunci permanen lintas-nagari.
- **Penyelesaian modul** direkonsiliasi terhadap halaman yang masih ada (buang ID hantu, hitung ulang) + transaksi/lock.
- **Kuis**: `submit()` **re-validasi kelayakan di server** (batas percobaan / sudah lulus) — gating GET saja bisa dilewati via Livewire. Opsi jawaban disaring ke milik soal.
- **Akun nonaktif** diblokir login portal **dan** dikeluarkan tengah sesi (sebelumnya hanya admin yang ter-gate status).
- **Ganti sandi biasa** wajib `current_password` + kebijakan `Password::min(8)->letters()->numbers()`.
- **XP**: award dibungkus `DB::transaction`; leaderboard hanya warga aktif; peringkat **kompetisi** konsisten (badge = daftar).
- **Eskalasi hak**: nagari_admin dipaksa `role=warga` (create) & tak bisa ubah peran (edit) — guard server-side, tak bergantung enforcement Select. Bulk-delete pengguna cegah self-lockout.
- **UMKM publik**: null-safe profil (404 bukan 500); profil 1-per-warga (unique); cabut akses → lapak nonaktif.

---

## LMS

### Kuis hanya pilihan ganda, nilai 0–100
MC-only (auto-grade); essay & antrian review dihapus total. Nilai = **angka 0–100** (bukan %), semua soal setara: `(benar ÷ jumlah soal) × 100`. Jawaban benar boleh >1 → soal pilihan jamak dengan **partial credit** (`benar/total_benar − salah/total_salah`, min 0). Wajib ≥1 opsi benar & ≥1 salah. Judul kuis dihapus → diturunkan "Kuis: {judul modul}". Kolom mati di-drop (`quiz_questions.type/points`, `quiz_answers.answer_text/feedback/score_given`, `quiz_attempts.reviewed_*`, enum `pending_review`).
**Alasan**: Menyederhanakan alur (tanpa beban penilaian admin); "nilai itu angka, bukan persentase"; skema bersih karena pra-rilis.

### Konten & media modul
- **RichEditor bawaan Filament** (Tiptap tak support v5). Video YouTube/GDrive via field URL → embed di portal.
- **Cover modul** via Spatie Media Library (koleksi `cover`, konversi `card` webp 800×450, nonQueued) + cover default. Modul tak punya `thumbnail`.
- **Slug modul stabil** (`doNotGenerateSlugsOnUpdate`) — URL tetap valid saat judul diedit.
- **Urutan** modul/materi/soal otomatis (`sort_order` = max+1) + drag-reorder; field urutan manual dihapus.
- **Upload PDF materi 10 MB**: Filament `maxSize(10240)` + PHP `upload_max_filesize=10M`/`post_max_size=12M`; produksi nginx `client_max_body_size 12M`.

### XP + Leaderboard XP (per nagari)
XP berbasis pencapaian, **sekali per modul** (idempotent via `xp_logs` UNIQUE(user,source,source_id)): selesai materi modul **+50**, lulus kuis **+100** (+**25** bonus bila nilai sempurna 100 — keputusan user 2026-07-02; dihitung saat lulus pertama), partisipasi diskusi (posting pertama) **+20** → maks 195/modul. Besaran GLOBAL di kode (bukan per-modul — jaga keadilan leaderboard; opsi konfigurasi admin DITOLAK user). XP modul sengaja FLAT (opsi proporsional-materi ditolak). Akumulatif di `users.total_xp`. Leaderboard **per nagari** (warga aktif), urut total_xp, peringkat kompetisi; dashboard Top 5.
**Alasan**: XP terikat penyelesaian/kelulusan/partisipasi berbatas → sulit "digoreng" (vs poin per-klik). Mitigasi bias: per nagari, dari pencapaian nyata, kuis flat, diskusi sekali/modul.
**Sejarah**: sempat ditiadakan total (kekhawatiran bias kompetitif) lalu dihidupkan lagi dengan desain berbatas ini.

### Forum diskusi + moderasi admin
Diskusi per modul, ter-scope nagari (warga hanya lihat/balas sesama nagari, via nagari penulis). Posting di-throttle (anti-spam). **Moderasi** via Filament `DiscussionResource` (read-only + aksi): super_admin semua nagari, nagari_admin hanya nagarinya; pin/lepas (top-level), hapus/pulihkan (soft-delete), force-delete. `LogsActivity`.

---

## UMKM

### Promosi saja → WhatsApp (tanpa transaksi)
Tombol "Hubungi via WhatsApp" (`UmkmProfile::whatsappUrl()`, normalisasi 08→628). Tanpa cart/checkout/payment gateway.
**Alasan**: Mengurangi kompleksitas; UMKM lokal familiar WA; tanpa payment gateway di fase awal.

### Integritas & taksonomi
- `umkm_products.harga` **integer rupiah** (`unsignedBigInteger`), bukan decimal — rupiah tak berpecahan.
- `umkm_profiles.user_id` **UNIQUE** → 1 warga = 1 lapak.
- Kategori = tabel **`umkm_categories`** (taksonomi global super_admin, ikon/slug/urutan), bukan kolom string.
- Produk: status pending→approved/rejected; edit produk → reset pending (verifikasi ulang). Maks 5 foto/produk. Katalog publik hanya approved + profil aktif.
**Ditolak**: Konversi semua enum native→string menyeluruh (ROI tipis MySQL, churn lebar); `user_id` HasMany (banyak usaha/warga) = perluasan produk, ditunda.

---

## Frontend, UI & Storage

### UI portal: komponen Blade sendiri + canvas-confetti
Konsistensi via `components/portal/` sendiri (breadcrumb/button/card/badge/progress/stat/toast + avatar/status-badge/content-badge/empty), bukan UI-kit eksternal. Satu dependency baru: **canvas-confetti** (~6 KB, perayaan lulus kuis/modul). Toast = Alpine+Livewire `dispatch('toast')`.
**Ditolak**: Flux/WireUI/Mary/daisyUI (bawa design-system, bentrok); Toastr/SweetAlert2 (dilarang/gaya beda).

### Loading form: satu handler global + komponen tombol
Semua form POST biasa (non-Livewire) pakai satu pola bersama: handler **event-delegation** di `app.js` (`document` `submit`) menonaktifkan tombol `button[type=submit][data-loading]` & menukar `[data-loading-label]`↔`[data-loading-spinner]` saat dikirim (skip form `wire:submit` — Livewire punya `wire:loading` sendiri; form navigasi penuh → status reset otomatis). Markup dua-state dirender otomatis oleh `x-portal.button` saat `type="submit"` (prop `loadingText`, opt-out `:loading="false"`); tombol auth kustom (login/ganti-sandi, halaman tanpa Alpine) pakai atribut generik yang sama. **Alasan**: umpan-balik konsisten + cegah klik-ganda tanpa skrip per-halaman.

### Foto profil warga: editor crop + kompresi klien (`cropperjs`)
Unggah foto profil = pilih → atur (**Cropper.js** v1.6, crop 1:1 + zoom + putar, modal Alpine) → **kompres di klien** (canvas → JPEG **160×160** = 2× tampilan avatar terbesar 80px [profil/podium] untuk retina, loop turunkan kualitas; bila mentok, perkecil dimensi & ulangi → target <50 KB, praktik ~10–15 KB) → submit blob via `DataTransfer` ke input file tersembunyi. Server (`ProfileController::update`) validasi `required|image|mimes:jpeg,png,webp|max:50` sebagai pengaman terakhir; koleksi Media Library `avatar` (singleFile) + konversi `thumb` **160²** webp untuk tampilan. **Alasan**: kompresi klien hemat kuota unggah (target low-bandwidth) & menjamin ukuran kecil apa pun sumbernya; crop memberi kontrol framing. **Ditolak**: kompresi server-side murni (boros bandwidth unggah, tak ada framing); `browser-image-compression` (Cropper canvas + `toBlob` sudah cukup, hindari dep tambahan). Fitur "hapus foto → inisial" dihapus (permintaan produk).

### Render konten materi: `@tailwindcss/typography` (prose) di-brand token NCH
Output RichEditor admin (heading/list/kutipan/tautan/tabel) dirender via plugin **`@tailwindcss/typography`** (devDep, di-load `@plugin` di `app.css`). Var `--tw-prose-*` di-override ke token NCH (`@layer components .prose`) → on-brand tanpa modifier `prose-*` bertebaran. Dipakai blok 'teks' & deskripsi modul. **Alasan**: tanpa plugin, kelas `prose` tak berefek → teks kaya tampil datar (tanpa bullet/heading). **Ditolak**: CSS tipografi buatan sendiri (kalah lengkap & rawan luput kasus).

> Catatan kebijakan: sejak 2026-06-26 user mengizinkan pasang dependency yang diperlukan tanpa persetujuan per-kasus (tetap dicatat di sini).

### Standar lain
- **Heroicons** satu-satunya icon set (built-in Filament).
- **Filament Notifications** untuk toast admin + database notification (pusat notifikasi in-app portal; notif fan-out modul/kuis `ShouldQueue` + `chunkById`).
- **ApexCharts** (`leandrocfe/filament-apex-charts`) untuk semua chart dashboard (ter-scope nagari).

### Storage: satu knob `MEDIA_DISK` untuk semua unggahan
- **Spatie Media Library** (cover modul, foto produk) + **PDF materi** (`ModulePage.file_path`) semua memakai disk **`MEDIA_DISK`** (`config('media-library.disk_name')`, default `public` = lokal+symlink). `FILESYSTEM_DISK` (default `local`) terpisah untuk disk privat app.
- **Produksi R2**: set `FILESYSTEM_DISK=s3` **dan** `MEDIA_DISK=s3` + kredensial (S3-compatible, gratis ≤10GB, tanpa egress).
**Alasan**: Sebelumnya PDF hard-coded disk `public` di 5 tempat → tak ikut R2. Satu env menyatukan semua unggahan agar migrasi storage = ubah env.
**Catatan**: PDF di disk publik bisa diakses tanpa login bila URL bocor (keputusan MVP; materi edukatif non-sensitif).

### Foto profil & logo via Media Library (bukan kolom string)
Foto profil warga (`User` koleksi `avatar`, singleFile + konversi `thumb` 256² webp) & logo nagari/kabupaten (`Nagari` koleksi `logo` + `logo_kabupaten`, terima SVG, serve original) pakai Spatie Media Library — konsisten dgn cover modul & foto produk, ikut `MEDIA_DISK`, auto-cleanup. Kolom `users.avatar` (string) dibuang.
**Alasan**: Satu mekanisme media (konversi, disk portabel, hapus otomatis) > kolom path manual. Tanpa dependency baru (GD sudah ada).
**Ditunda**: tabel `kabupatens` ternormalisasi (kini logo kabupaten di-attach per-nagari) — bila perlu hemat duplikasi/kelola terpusat.

### Referensi wilayah resmi (`ref_wilayah`) + logo kab terpusat + peta Leaflet
Data Kepmendagri (kode/nama prov→kab→kec→desa + geo) diekstrak **Sumbar dulu** ke `database/data/sumbar_wilayah.{csv,json}`, diimpor `WilayahSumbarSeeder` (di CoreSeeder) ke tabel datar `ref_wilayah` (level diturunkan dari kode). `desas.wilayah_kode` FK opsional; DesaForm super_admin pakai dropdown bertingkat (jenis/sub-unit **tetap manual**). Logo kab/kota dipindah ke `public/images/wilayah/` & diturunkan via `Desa::kabupatenLogoUrl()` (media `logo_kabupaten` per-desa **dibuang** — menjawab "Ditunda" di atas). Peta publik `/peta` pakai **Leaflet via CDN** (bukan paket npm) + polygon `ref_wilayah.path` (format tak konsisten → dinormalisasi di JS), choropleth jumlah desa terdaftar.
**Alasan**: standardisasi nama/kode resmi, kurangi typo, logo tak perlu diunggah ulang, peta tanpa build step. **Ditunda**: provinsi lain (tambah berkas + perluas seeder); UI peta tingkat desa.
**Ditolak**: simpan dump nasional 26 MB di repo (cukup subset Sumbar); auto-isi `jenis` dari kode (user mau pilih manual).

---

## Admin (Resources)

### NagariResource — super_admin only + anti-orphan
Hanya super_admin (`NagariPolicy` semua false; super via Gate::before). SoftDeletes + guard: nagari tak bisa dihapus selama punya pengguna/modul (`guardAgainstDependents` + `$action->halt()`); tanpa bulk-delete agar guard per-record selalu jalan.

### UserResource — manajemen pengguna
super_admin: CRUD lintas nagari, semua peran (super_admin → nagari_id null). nagari_admin: hanya **warga** di nagarinya (scope query + record-binding + `UserPolicy`). Password hash (cast) + opsional saat edit + confirmed. Pengaman self: tak bisa hapus/demote/nonaktifkan diri sendiri.
**Ditunda**: impersonate, 2FA, bulk-import.

### Ekspor laporan (rencana, belum dibangun)
DomPDF (`barryvdh/laravel-dompdf`) untuk laporan LMS/SDGs; Maatwebsite Excel untuk data warga/UMKM.

### Navigasi per-peran + drill-in Warga (super admin → desa) (2026-06-24)
**Master per-peran**: admin desa = **Warga** (hero); super admin = **Desa + LMS**. · **Sidebar**: super admin → Desa (tingkat atas) · LMS (Modul/Kuis/Diskusi) · UMKM (Kategori, super-only) · Sistem (Log Aktivitas); admin desa → Warga · UMKM (Profil/Verifikasi) · Pengaturan (sub-unit/Pengaturan Desa). · **LMS = super-only**: `shouldRegisterNavigation()` super-only (admin desa **disembunyikan**, akses kode tetap → reversibel, bukan dihapus). Resource per-desa (Warga/Profil UMKM/Produk/Sub-unit) disembunyikan dari sidebar super admin (diakses lewat drill-in). Kategori UMKM = `canAccess` super-only (taksonomi global, cegah bocor multi-tenancy). · **Drill-in Warga (kunci)**: aksi "Kelola Warga" per-baris di tabel Desa → set **`DesaContext`** (desa konteks di session) → redirect ke `UserResource` index. Halaman Warga **100% identik** dgn panel admin desa karena resource yang SAMA dipakai ulang, di-scope via **`User::managedDesaId()`** (desa_admin → desanya; super admin → desa konteks). Semua jalur warga (resource scope, form `desa_id` dipaksa/sembunyi, label sub-unit, tabel, **impor & template Excel**) ikut `managedDesaId`. · **Keamanan**: super admin tanpa konteks **tak bisa** akses halaman Warga (`UserResource::canAccess` guard); masuk daftar Desa membersihkan konteks (cegah stale); tombol "Kembali ke Desa" + subjudul desa. · **Refactor**: provisioning warga dipindah ke **`WargaProvisioningService`** (create/update/penduduk/OTP) — dipakai `CreateUser`/`EditUser`. · **Ditunda**: drill-in UMKM/Wilayah/Modul per-desa (sengaja hanya Warga dulu). · Tervalidasi `SidebarNavigationTest`, `DesaWargaContextTest`, `WargaProvisioningServiceTest`.

### Peta desa (Pengaturan Desa) via endpoint ter-cache (2026-06-24)
**Keputusan**: peta batas wilayah 1 desa di bawah halaman Pengaturan Desa pakai **endpoint admin ter-scope** `GET admin/desa/peta-batas` (`DesaBoundaryController`, middleware `auth`, hanya desa_admin & desanya sendiri → tanpa IDOR), meniru `PublicMapController`: `Cache::remember` geometri statis + `ST_AsGeoJSON(COALESCE(geom_simplified,geom), 5)` (presisi ~1 m) + header `Cache-Control: private, max-age=300`. Blade `fetch` di klien (Leaflet via CDN). · **Alasan**: payload Livewire tetap ringan (tak embed GeoJSON tiap render), geometri cacheable. · **Identitas Desa** di Pengaturan Desa = read-only (header profil: nama + badge kode wilayah + breadcrumb prov/kab/kec); editable hanya penyebutan desa, sebutan sub-unit, logo.

### Impor warga via Excel — Admin Desa (2026-06-24)
**Paket**: `maatwebsite/excel` v3.1 (+ transitif `phpoffice/phpspreadsheet` 1.30) — install bersih di Laravel 13; tak menambah advisory (3 advisory yang ada milik guzzle, pra-eksis). · **Keputusan**: aksi `ActionGroup` "Impor" di header `ListUsers`, **hanya untuk desa_admin dulu** (1 desa per impor, ter-scope). Dua aksi: **Unduh Template** (`WargaTemplateBuilder` → XLSX 3 sheet: *Data Warga* berisi header+dropdown, *Petunjuk* penjelasan tiap kolom+opsi, *Referensi* sumber dropdown) & **Impor dari Excel** (`WargaImport` → `WargaImportService` per-baris). · **Keamanan/best-practice**: desa dipaksa server-side ke desa admin (kolom Desa diabaikan); sub-unit wajib milik desa itu; NIK 16-digit, unik DB + unik dalam file; tulis akun+penduduk dalam transaksi (anti-yatim); baris gagal dilaporkan per-nomor tanpa menggagalkan keseluruhan; OTP TIDAK diset (terbit terpisah via "Reset OTP"). · **Enum/opsi template = persis form create** (agama/status kawin/pekerjaan `aktif=true` urut `urutan`; JK Laki-laki/Perempuan; status Aktif/Nonaktif). · **Sub-unit per desa**: judul kolom = sebutan desa (Jorong/Korong/…) & dropdown = daftar `desa_units` desa itu; importer membaca nilai via key ter-slug sebutan (mis. `jorong`), fallback `wilayah`. · Logika inti di Service (bukan Controller/Resource); tervalidasi 11 test (`WargaImportTest`).

---

## Data wilayah & peta

### Geometri batas → tabel spasial terpisah `wilayah_boundaries`
**Keputusan**: simpan batas wilayah sebagai tipe `GEOMETRY` (SRID 4326) di tabel terpisah (`geom` penuh + `geom_simplified`), bukan teks JSON di `ref_wilayah.path` (kolom itu dihapus). · **Alasan**: `ref_wilayah` sering di-query (cascading select/join) harus tetap ringan; tipe spasial → `ST_AsGeoJSON` merakit GeoJSON langsung di DB (urutan `[lng,lat]` benar, hapus parser manual) + `ST_Contains` utk point-in-polygon (auto-deteksi desa dari koordinat UMKM) + spatial index. · **Ditolak**: simpan JSON `path` (rapuh, tak bisa query spasial); panggil API eksternal saat render (statis, latensi, mati bila sumber down — API hanya utk impor).

### Simplifikasi Douglas–Peucker saat impor + drill-down lazy-load
**Keputusan**: `geom_simplified` dihitung DP per-level di importer (`WilayahBoundaryImporter`); peta muat kab/kota dulu, desa di-fetch per-kab saat diklik. · **Alasan**: MariaDB 10.11 **tak punya `ST_Simplify`** (fungsi MySQL) → simplifikasi tak bisa query-time; 1.159 poligon desa terlalu berat bila dimuat sekaligus. · Sumber data: cahyadsn/wilayah_boundaries (MIT) di `database/data/boundaries/`; impor via `wilayah:import-boundaries`/`WilayahBoundarySeeder`. 106 desa tak punya geometri di sumber (diterima).

---

## Fitur ditunda / batas lingkup
- **Onboarding nagari** manual oleh super_admin (self-service di Fase 3).
- **Squash migrasi** jadi baseline bersih = langkah pra-deploy **terakhir** (jangan saat masih ada perubahan skema). 42 migrasi terbukti jalan di MySQL+SQLite.
- SDGs & IoT = pilar lain (programmer lain).
- **Panel `/admin` Bahasa Indonesia** (locale `id`, fallback `en`) · terjemahan Filament bawaan + `lang/id/*` inti Laravel · tanpa paket i18n.
- **Custom Filament theme** (`resources/css/filament/admin/theme.css` via `viteTheme`) untuk kartu sambutan banner gradien + utilitas Tailwind penuh di panel · dibuat via `make:filament-theme` (menaikkan minor `tailwindcss`/`@tailwindcss/vite` 4.0→4.3.x, bukan paket baru). Pembeda peran: super_admin aksen Indigo + banner indigo, admin desa Teal.

```
### [YYYY-MM] Judul keputusan
**Keputusan**: ... · **Alasan**: ... · **Ditolak**: ...
```

## Pengajuan akses UMKM mandiri (2026-07-02)

Dua pintu menuju akses UMKM: (1) **warga mengajukan sendiri** dari portal (`umkm/ajukan`) dengan profil usaha + SATU produk lengkap — semua field wajib termasuk **foto ≥1, deskripsi, dan harga** (keputusan user); (2) admin desa memberi akses langsung (alur lama, tetap ada). Pengajuan **ditinjau admin desa** (antrean "Pengajuan UMKM", badge menunggu); **disetujui = satu tinjauan**: akses aktif + lapak tayang + produk bawaan ikut approved (tanpa antre verifikasi kedua); **ditolak = alasan wajib**, warga melihat alasannya dan boleh memperbaiki + mengajukan ulang. Representasi data: TANPA tabel baru — pengajuan = `UmkmProfile` status `inactive` + kolom `status_pengajuan` (menunggu/ditolak/null) + `alasan_penolakan_pengajuan` + `diajukan_at`, produk `pending`; profil nonaktif otomatis tak bocor ke katalog publik. Pemberian akses manual saat ada pengajuan berjalan dialihkan ke jalur persetujuan pengajuan (cegah state menggantung).

## Panduan pengisian produk per kategori (2026-07-03)

Agar listing produk tidak "asal isi": tiap kategori UMKM punya **panduan deskripsi produk** (kolom `umkm_categories.panduan_produk`, satu poin per baris berakhiran ": ") yang tampil di form produk warga sebagai daftar panduan + tombol **"Gunakan sebagai kerangka isian"** (mengisi textarea dengan baris siap-lengkapi). DIPILIH pendekatan teks-kerangka, BUKAN field terstruktur per kategori (ditolak — kompleksitas skema dinamis tak sepadan utk MVP). Sumber panduan = data (bukan hardcode): 7 kategori bawaan ter-seed hasil riset praktik marketplace; super admin menyunting/menambah via menu Kategori UMKM — kategori baru otomatis bisa diberi panduan. Penegakan ringan: deskripsi produk **minimal 30 karakter** (pengajuan & form produk), pesan error mengarahkan ke panduan.

## Bantuan deskripsi produk: UMUM saja, detail via WhatsApp (2026-07-03, revisi keputusan di atas)

KEPUTUSAN USER: platform hanya membantu deskripsi **secara umum** — detail lanjutan (legalitas, minimal pesanan, penyimpanan, perawatan, dsb) biar ditanyakan pembeli langsung lewat WhatsApp penjual. Revisi: (1) panduan tiap kategori DIPANGKAS dari 5–6 poin teknis jadi **3 poin umum** (apa produknya/varian, ukuran/isi, keunggulan), tanpa akhiran titik dua; (2) tombol kerangka isian "Label: " DIGANTI **contoh deskripsi jadi** per kategori (kolom baru `umkm_categories.contoh_deskripsi`, ter-seed 7, editable super admin) — sekali klik textarea terisi contoh kalimat utuh, penjual tinggal mengganti kata-katanya (lebih ramah untuk yang tak terbiasa menulis, hasil lebih enak dibaca di katalog); (3) kotak panduan diberi kalimat penenang. Alasan: kerangka 6 baris terasa seperti PR yang wajib dilengkapi — bertentangan dengan arah bantu-umum-saja. min:30 tetap. Alur pengajuan sendiri TIDAK diubah (sudah ringan: 8 isian + pratinjau).

**Penajaman (2026-07-03, benchmark Shopee/Tokopedia):** prinsip final = **tidak meminta detail, tapi juga tidak mencegah detail** — ini persis pola kolom deskripsi marketplace besar (teks bebas panjang + tips/contoh; detail terstruktur di sana hidup di field varian/stok/berat karena mereka memproses transaksi — belum relevan bagi kita, dan bila kelak transaksi in-app dibangun, field terstruktur tinggal DITAMBAH di samping deskripsi tanpa merombak yang ada). Dua penyesuaian: (a) kalimat penenang diubah dari "Tak perlu terlalu rinci…" (terkesan mencegah) menjadi *"Boleh singkat, boleh rinci — deskripsi lengkap membuat lapak makin meyakinkan. Detail lainnya bisa ditanyakan pembeli lewat WhatsApp."*; (b) batas deskripsi produk dinaikkan **max:2000 → max:5000** (setara Tokopedia; Shopee ±3000) di pengajuan & form produk. Katalog publik sudah merender deskripsi dengan `whitespace-pre-line` — deskripsi rinci multi-baris tampil rapi.

## Polesan verifikasi UMKM (2026-07-03, arahan user)

(1) **Ikon kategori UMKM DIHAPUS TOTAL** (kolom `icon` di-drop dari `umkm_categories`, migrasi konsolidasi + ALTER live) — pemilih ikon visual yang baru dibuat dicabut lagi atas keputusan user "tidak perlu pakai ikon"; kolom slug juga disembunyikan dari daftar. (2) **Antrean Pengajuan UMKM & Verifikasi Produk = SATU aksi "Tinjau"**: keputusan Setujui/Tolak dipilih DI DALAM modal detail (Tolak = `extraModalFooterActions` bersarang + `cancelParentActions`; pengaju terarsip → tombol Setujui disembunyikan via `modalSubmitAction` + guard server, antrean tetap bisa dibersihkan lewat Tolak). Test aksi bersarang pakai `Filament\Actions\Testing\TestAction` berantai. (3) **Foto di modal tinjau pakai lightbox** (klik memperbesar di overlay `x-teleport` — tidak lagi membuka tab baru); partial `filament/partials/photo-lightbox`. (4) **Semua foto produk dikompres** mengikuti pola foto profil warga: KLIEN dulu (photo-picker: canvas, sisi terpanjang 1920px, JPEG q0.8, PNG transparan → latar putih; gagal baca → kirim asli) + pengaman SERVER (`UmkmService::attachPhotos`: Spatie Image `Fit::Max` 1920 q80 — file tmp tanpa ekstensi harus di-`save()` ke path berekstensi). Validasi longgar jadi 10MB/foto; copy "ukuran bebas, otomatis dikompres". CATATAN PRODUKSI: pastikan `upload_max_filesize ≥ 10M` & `post_max_size ≥ 55M` bila kompresi klien dilewati.

## Kategori UMKM milik PRODUK, bukan profil (2026-07-03)

KEPUTUSAN USER (usul user, direkomendasikan juga oleh analisis): `umkm_category_id` pindah dari `umkm_profiles` ke `umkm_products` — satu lapak boleh menjual produk lintas kategori (warung bisa jual keripik + tas anyaman), persis pola marketplace (Shopee/Tokopedia mengkategorikan PRODUK, bukan toko). Konsekuensi terpasang: form produk & pengajuan punya dropdown "Kategori produk" (panduan + contoh deskripsi kini mengikuti kategori terpilih secara LIVE di kedua form); profil usaha TANPA kategori; filter katalog publik & chart dashboard membaca kategori produk; kolom Kategori di admin (Pengajuan/Verifikasi Produk) dari produk; guard hapus kategori menghitung PRODUK pemakai. Data live dimigrasikan: kategori profil lama disalin ke semua produknya (18 produk, 0 tanpa kategori) lalu kolom profil di-drop.

## Urutan tombol aksi & konfirmasi keputusan tinjau (2026-07-03)

KEPUTUSAN USER: (1) **Urutan tombol aksi DIBALIK se-aplikasi — Batal/aman di kiri, aksi utama di kanan.** Filament: global via `Action::configureUsing` di AppServiceProvider (`modalFooterActions` = [cancel, ...extra, submit]); portal: form UMKM (pengajuan/produk/profil) tombol Batal dipindah ke kiri — modal profil warga & `x-portal.confirm-dialog` memang sudah benar dari awal. (2) **Setiap keputusan di modal Tinjau minta KONFIRMASI dulu**: submit bawaan modal tinjau dimatikan (`modalSubmitAction(false)`, cancel="Tutup"); Setujui & Tolak jadi tombol footer bersarang — Setujui `requiresConfirmation` ("Setujui …? Ya, Setujui"), Tolak tetap modal alasan; keduanya `cancelParentActions`. Guard pengaju-terarsipkan: tombol Setujui `visible(owner !== null)`. Test aksi bersarang = rantai `TestAction`. (3) **Form pengajuan & produk kembali lebar penuh layout** (cap max-w-6xl/3xl dicabut — user tak suka ruang kiri-kanan); form produk kini juga punya **pratinjau katalog hidup** persis form pengajuan (grid [1fr_19rem], foto ikut photo-picker, nama usaha dari profil).

## Semua aksi reversible (2026-07-03)

KEPUTUSAN USER: setiap aksi di aplikasi harus punya jalan kembali. Audit menyeluruh — hampir semua SUDAH reversible: verifikasi produk dua arah (produk approved bisa ditolak & sebaliknya, kapan pun via modal Tinjau), pengajuan (tolak→ajukan ulang; setujui→cabut akses), akses UMKM (beri↔cabut+reaktivasi lapak), restore tersedia di Desa/Wilayah/Warga/Modul/Kuis/Diskusi/Profil UMKM. SATU gap ditutup: **produk yang dihapus warga** kini bisa dipulihkan admin desa (TrashedFilter + Pulihkan/Hapus Permanen di antrean Verifikasi Produk; policy restore/forceDelete = desa_admin; Tinjau disembunyikan utk baris terhapus) — copy hapus di portal diperbaiki (dulu bohong "permanen, tidak dapat dibatalkan"; kini "hubungi Admin {jenis desa} untuk memulihkannya"). Sadar TIDAK reversible (by design): hapus foto saat edit produk (media diganti, bukan diarsip), XP/attempt kuis (jejak domain), Kategori UMKM hard-delete-terjaga (guard terpakai; buat ulang trivial).
