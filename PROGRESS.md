# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya. Ringkas (≤60 baris).
> Detail task → TASKS.md · Keputusan → DECISIONS.md
> Aturan: baca CLAUDE.md + PROGRESS.md + TASKS.md di awal sesi.

---

## Status

**Fase**: MVP — LMS lengkap; pilar **UMKM (sisi admin)** & **dashboard admin** kini ada di /admin.
**Progres**: ~98%. LMS + provisioning warga + master wilayah di **`main`**. Sedang berjalan:
branch **`feat/umkm`** — akses UMKM, UmkmProfileResource + antrian verifikasi, dashboard
ApexCharts, Lapak portal pemilik, **+ audit RBAC/DB (role→kapabilitas, OTP expiry, harga
integer, taksonomi kategori)**. Belum merge. Sisa pilar UMKM: **katalog publik `/umkm`** (M4.3).
**Login demo** (jalankan `php artisan migrate:fresh --seed`):
- super_admin: email `admin@basamo.nch` (username `superadmin`) / `password`
- nagari_admin: `admin.nagari@basamo.nch` (NCH-001) & `admin.nch-002@basamo.nch` (NCH-002) / `password`
- warga (portal): login **NIK** mis. `3201000000000101` / `password` (16 warga, 2 nagari)
- Catatan: warga demo `must_change_password=false` agar bisa langsung login showcase.

---

## ⏭️ BERIKUTNYA (saat user ketik "lanjut")
> Konfirmasi arah dulu ke user, lalu kerjakan.

**⚠️ Git tertunda:** branch `feat/login-username-rbac-audit` = 7 commit (UI kit · login username/email · audit RBAC · UserResource · NagariResource · docs). 4 commit awal sudah ter-push; **3 commit terakhir belum di-push**. **PR ke main belum dibuka** (URL & body sudah disiapkan; `gh` terpasang di `~/.local/bin` tapi belum login). Tindakan: `git push` lalu buka PR.

> Kandidat fitur (urut saran):
1. **Katalog publik `/umkm`** (M4.3) — tanpa login, filter nagari/kategori, kartu produk
   (foto, info, tombol WA), counter view. Frontend publik Lapisan 1 (Blade+Tailwind, SEO).
   Hanya produk `status=approved`. Detail produk → galeri foto + tombol WhatsApp.
2. **Pilar SDGs (M3)** atau **IoT (M5.3)** — lalu lengkapi chart SDGs radial + panel IoT.
3. **Testing Pest** — coverage portal/admin masih minim.

---

## Yang sudah jadi (LMS)

**Admin (Filament /admin):** CRUD Modul (auto-order via `sort_order`, drag, slug stabil, **cover via Media Library + estimasi durasi**, scoping nagari), CRUD Kuis (MC-only, nilai 0–100 tanpa %, 1 modul=1 kuis, **judul opsional**, **jawaban benar boleh >1 → partial credit**, max_attempts 0=tak terbatas), Materi (teks/PDF disk public maks 10MB/video YouTube+GDrive). Menu Role disembunyikan.

**Portal warga:** Shell = sidebar (desktop) + bottom-nav (mobile) + top header (lonceng notifikasi + dropdown user). Dashboard (hero progres, kartu Modul Selesai/XP/Peringkat, Lanjutkan Belajar, Peringkat XP Top 5). Daftar/detail modul, baca materi, QuizPlayer (confetti+toast saat lulus), Diskusi per modul, Notifikasi in-app, Leaderboard XP per nagari.

**XP** (idempotent via `xp_logs`): modul selesai +50, lulus kuis +100, diskusi (posting pertama/modul) +20 → `users.total_points`. `LmsPointService`.

**UI kit** (`components/portal/`): avatar, status-badge, content-badge, empty, breadcrumb, button, card, badge, progress, stat, toast. Dependency: `canvas-confetti`.
Adopsi: `card` (panel) dipakai di home/leaderboard/modules show+page; `stat` di dashboard (hapus duplikasi); `button` di form diskusi. Sengaja DILEWATI (tak memetakan bersih ke 4 varian/risiko regresi): CTA modul berkondisi completed/in_progress (modules index+show), reader-nav page (varian emerald/border), kartu padded dalam loop & form (discuss reply/notif).

---

## Audit super_admin (2026-06-18) — SELESAI fondasi
- RBAC dirombak: **kolom `role` = sumber kebenaran tunggal**. super_admin via `Gate::before` (cek kolom); policy berbasis role; observer sinkron Spatie role saat kolom berubah.
- Fix: B2 (admin nonaktif diblokir di `canAccessPanel`), H1 (nagari_admin tak lagi panel kosong), L2 (scoping route-binding QuizResource), L1 (`modules.created_by` nullable+nullOnDelete).
- Test: `tests/Feature/SuperAdminAccessTest.php` (6 lulus). Factory dapat state role: `superAdmin()/nagariAdmin()/warga()/umkmOwner()/inactive()`.
- **UserResource (M2) — SELESAI**: CRUD pengguna di `/admin` (grup Pengaturan). super_admin semua nagari; nagari_admin hanya nagarinya & hanya warga/umkm_owner. Password hash+opsional saat edit, pengaman self-lockout, `UserPolicy`. Test: `tests/Feature/UserResourceTest.php` (4 lulus).
- **NagariResource (M1) — SELESAI**: CRUD nagari (grup Pengaturan, super_admin-only). SoftDeletes + guard anti-orphan (tak bisa hapus bila masih ada warga/modul). Kolom jumlah warga/modul. Test: `tests/Feature/NagariResourceTest.php` (4 lulus).
- **MASIH KURANG:** Dashboard super_admin masih kosong (M5). `FilamentInfoWidget` (promo) sebaiknya dibuang utk produksi. Onboarding flow (buat nagari → buat admin) kini bisa via UI.
- Catatan pre-existing: `tests/Feature/ExampleTest` gagal (uji `/` = 200 tapi app redirect 302) — bukan dari perubahan ini.

## Penyempurnaan LMS (2026-06-19) — branch `feat/lms-modul-kuis-refinement`
Hasil evaluasi fitur LMS bersama user. Tiap poin = 1 commit; test hijau (21 lulus,
kecuali `ExampleTest` pra-eksis 302).
- **B1** drop kolom mati `user_module_progress.points_earned`.
- **B2** rename `users.total_points` → `total_xp` (model, service, controller, view, UsersTable).
- **B5** rename kolom `order` → `sort_order` (modules, module_pages, quiz_questions, quiz_options).
- **B4** judul kuis opsional → auto `"Kuis: {judul modul}"` via `QuizObserver::saving`.
- **A5** `modules.estimated_minutes` + field admin + badge "± N menit" di portal.
- **A2** cover modul via Spatie Media Library (koleksi `cover`, konversi `card` webp 800×450,
  nonQueued) + cover default global `public/images/default-module-cover.svg` + tampil portal.
- **A1** kuis: jawaban benar boleh >1 → **partial credit**. Soal multi (checkbox) implisit bila
  `is_correct` >1. `QuizAnswer` kini 1 baris per opsi terpilih. Test `QuizPlayerGradingTest` (6 kasus).
- **Ditolak/ditunda:** pembahasan jawaban kuis (tak perlu), kategori/level modul (tak perlu),
  search/filter portal (fokus admin dulu).

## Audit admin LMS (2026-06-19) — branch `feat/lms-modul-kuis-refinement`
Lanjutan: audit menyeluruh sisi Filament. Tiap poin = 1 commit; test hijau (22 lulus).
- **#1 Keamanan:** nagari_admin tak bisa menempelkan kuis ke modul nagari lain/global —
  validasi server-side di `QuizForm` (sebelumnya hanya `modifyQueryUsing` = batas opsi tampil).
- **Judul kuis dihapus** (supersede B4): drop `quizzes.title`; label via accessor `Quiz::title`
  → "Kuis: {judul modul}". Bersihkan form/observer/tabel/view/test.
- **#2 Integritas materi:** `required` kondisional di `PagesRelationManager` (teks→konten,
  video→URL, pdf→file). Cegah halaman materi kosong.
- **#3 Guard kuis kosong:** `QuizController` redirect bila kuis 0 soal; CTA modul disembunyikan;
  guard defensif di `QuizPlayer::submit`. Test ditambah.
- **#6/#8 Konsistensi:** emoji (📝🎬📄, 🌐) → Heroicons; helper text video diluruskan.
- **#7/#9 Tabel admin:** `ModulesTable` + cover/materi/kuis/durasi (eager-load media,
  withCount pages, withExists quiz); hapus `withCount` ganda di `QuizzesTable`.
- **Ditunda (sadar):** kuis multi "semua benar" (#5), modul publish tanpa materi (#4),
  Activity Log operasi kritis, ordering modul per-nagari.

## Hardening produksi (2026-06-19) — branch `feat/lms-modul-kuis-refinement`
Lanjutan menuju siap-produksi. Suite kini **hijau penuh (29 lulus)**.
- **Audit trail (Activity Log):** trait `LogsActivity` di Module, ModulePage, Quiz, Nagari,
  User (password tak pernah dilog). Viewer read-only **"Log Aktivitas"** (grup Pengaturan,
  super_admin-only): waktu, pelaku+role, objek, aksi, ID, ringkasan "field: lama → baru",
  filter objek & aksi. Test: `ActivityLogResourceTest`.
- **#4 Integritas:** modul tak bisa dipublish tanpa ≥1 materi (validasi status di ModuleForm).
  Test: `ModulePublishGuardTest`.
- **#5 Integritas:** soal kuis wajib punya minimal satu opsi salah (tak boleh semua benar).
- **Test pra-eksis diperbaiki:** `ExampleTest` kini smoke test benar (root → `portal.login`).
- **`.env.example`:** identitas Basamo NCH, locale id, panduan storage R2, batas upload PDF.
- **Ordering modul per-nagari:** ditinjau → **dibiarkan** (reorder sudah ter-scope aman, urutan
  portal deterministik). Interleaving global vs lokal = keputusan produk, bukan bug. Future enhancement.

## Provisioning akun warga (2026-06-19) — branch `feat/lms-modul-kuis-refinement`
Pindah fase ke manajemen warga. Suite **36 test hijau**.
- **Model akun warga:** dibuat Admin Nagari (self-register DIHAPUS). Login = **NIK (username)
  16 digit + OTP** (sandi awal). Login pertama **wajib ganti sandi**. Email **opsional** + No. WhatsApp.
- **DB:** `users` + `phone`, `must_change_password`, `initial_otp`; `email` → nullable.
- **Admin:** `UserForm` role-aware (warga: NIK/email opsional/phone, tanpa sandi manual). `CreateUser`
  generate OTP + tampilkan ke admin. Tabel: kolom "OTP awal" + aksi **"Reset OTP"**.
- **Portal:** login terima NIK/email + **rate-limit** (5/menit, tutup celah brute-force).
  `EnsurePortalUser` paksa ke `portal.password.edit` bila `must_change_password`. `PasswordController`
  + view mandiri (set sandi → clear OTP).
- **Keamanan:** password/OTP tak pernah dilog/diserialisasi.
- **Ditunda (sadar):** akses UMKM (aksi admin "naikkan" warga→umkm_owner, saat pilar UMKM);
  alamat terstruktur jorong/dusun (butuh master data wilayah nagari); field warga lain menyusul.

## Master data wilayah nagari (2026-06-19) — branch `feat/master-wilayah-nagari`
Branch baru dari `main` (sudah berisi semua pekerjaan sebelumnya). Suite **40 test hijau**.
- **Model:** 1 tingkat. Tabel `wilayah` (nagari_id, nama, unik per nagari, softDeletes).
  **Sebutan unit diatur per nagari** via `nagaris.wilayah_label` (default Jorong; datalist
  Jorong/Korong/Kampuang/Dusun di NagariForm).
- **Admin:** `WilayahResource` (grup Pengaturan) — nagari_admin kelola wilayah nagarinya,
  super_admin semua (pilih nagari + filter). `WilayahPolicy`, LogsActivity, withCount warga.
- **Alamat warga:** `users.wilayah_id` (nullOnDelete) + Select di UserForm (opsi ter-scope ke
  nagari warga, label ikut sebutan nagari, reset saat nagari berubah, validasi anti cross-nagari).
  Kolom Wilayah di tabel pengguna.
- **Test:** `WilayahResourceTest` (scope nagari, unik per nagari, alamat warga anti cross-nagari).
- **Ditunda:** RW/RT (lebih dalam) bila perlu nanti; field warga lain menyusul.

## Pilar UMKM + Dashboard admin (2026-06-19) — branch `feat/umkm`
Lanjutan dari skema/model UMKM (commit `44e713e`). Suite **hijau (56 test)**.
- **Akses UMKM:** aksi tabel "Beri akses UMKM" (warga→umkm_owner) & "Cabut akses
  UMKM" (umkm_owner→warga) di UsersTable + konfirmasi/notifikasi. `UmkmProfilePolicy`
  (nagari_admin; super_admin via Gate::before). Test `UmkmAccessTest`.
- **UmkmProfileResource** (grup nav **"UMKM"**): CRUD profil usaha, scope nagari
  (nagari_admin nagarinya, super_admin semua), pemilik = akun `umkm_owner`, **nagari
  diwarisi dari pemilik** (CreateUmkmProfile::mutateFormDataBeforeCreate), filter
  kategori/status. `ProductsRelationManager` = **antrian verifikasi** (aksi Setujui/
  Tolak + alasan wajib → status + approved_by/at). Test `UmkmProfileResourceTest`.
- **Factory** UmkmProfile/UmkmProduct (+HasFactory). **DemoSeeder**: sebagian warga
  → umkm_owner + profil + produk status beragam (pending/approved/rejected).
- **Dashboard admin** (M5.1/5.2) — semua **ter-scope role**: `PlatformStatsWidget`
  (kartu Nagari[super]/Warga/UMKM/Produk menunggu), `LmsProgresChart` (bar: modul
  selesai/nagari), `UmkmKategoriChart` (donut), `AktivitasBelajarChart` (area 30 hari:
  modul+kuis). ApexCharts pakai data demo. `FilamentInfoWidget` (promo) dibuang.
  Test `DashboardWidgetsTest`.
- **Belum:** Lapak UMKM sisi portal (M2.6 / M4.2: form profil & produk pemilik,
  upload foto, katalog publik `/umkm`). Chart SDGs & panel IoT menunggu pilarnya.

## Lapak UMKM sisi portal (2026-06-19) — branch `feat/umkm`
Sisi pemilik UMKM (M2.6/M4.2). Suite **hijau (64 test)**.
- **Akses:** middleware `umkm.owner` (alias di bootstrap) — area "Produk Saya" khusus
  role `umkm_owner`; warga biasa dialihkan ke beranda. Menu "Produk Saya" di sidebar +
  bottom-nav portal hanya tampil untuk pemilik.
- **Produk Saya** (`/portal/umkm`): ringkasan profil usaha + daftar produk (kartu +
  badge status pending/approved/rejected, alasan tolak tampil). Form profil usaha
  (nagari ikut pemilik). CRUD produk + upload foto (Media Library koleksi `photos`,
  **maks 5**, hapus foto via checkbox saat edit). **Produk baru/diubah → status pending**
  (verifikasi ulang oleh Admin Nagari di `ProductsRelationManager`).
- **Arsitektur:** `UmkmService` (logic profil/produk/foto), `UmkmProductPolicy` (pemilik
  hanya kelola produknya), base `Controller` kini pakai `AuthorizesRequests`.
- **Catatan:** kelas Tailwind baru (`file:`, `group-has-[:checked]:`) → jalankan
  `npm run dev`/`npm run build` agar ter-compile.
- **Belum:** katalog publik `/umkm` (M4.3, frontend Lapisan 1, tanpa login).

## Audit RBAC & DB + hardening UMKM (2026-06-19) — branch `feat/umkm`
Tinjauan best-practice bersama user → 4 fase, tiap fase = commit. Suite **hijau (68)**.
- **Fase 1 — Keamanan OTP:** `users.otp_expires_at` (7 hari). Login tolak OTP
  kedaluwarsa; `initial_otp`+expiry dihapus saat ganti sandi. Tutup celah OTP plaintext abadi.
- **Fase 2 — Integritas:** `umkm_products.harga` → integer rupiah (bukan decimal);
  `umkm_profiles.user_id` UNIQUE (1 warga = 1 lapak).
- **Fase 3 — RBAC (besar):** **`umkm_owner` bukan role lagi.** Role = persona
  (super_admin/nagari_admin/warga); akses UMKM = kapabilitas `users.umkm_access_granted_at`
  (`User::hasUmkmAccess()`). `role` enum→string(20). Aksi beri/cabut set/null timestamp.
  Semua query/middleware/policy/form/seeder/factory/test disesuaikan. (Detail: DECISIONS.)
- **Fase 4 — Taksonomi:** kategori UMKM → tabel `umkm_categories` (ikon/slug/urutan,
  dikelola super_admin via `UmkmCategoryResource`); `umkm_profiles.umkm_category_id` FK.
  **DITUNDA sadar:** konversi enum native→string menyeluruh (ROI tipis, churn lebar).
- Migrasi data dibuat **portabel** (subquery korelasi) agar lolos di MySQL (dev) & SQLite (test).

## Keputusan teknis aktif (detail di DECISIONS.md)
- Kuis MC-only; nilai angka 0–100 (bukan %); tanpa bobot poin per soal.
- Scoping nagari admin manual (bukan Filament Tenancy). super_admin kelola global.
- Leaderboard berbasis XP pencapaian (sempat dihapus, lalu dihidupkan lagi).
- UI: komponen Blade sendiri (bukan Flux/WireUI) + canvas-confetti.
- PDF materi: disk `public` + symlink; PHP `upload_max_filesize=10M`/`post_max_size=12M` (set di server; lihat DECISIONS).
- Filament v5: `Schema $schema`; shield define_via_gate; super_admin via Gate::before.
- RBAC: role = persona (super_admin/nagari_admin/warga). Akses UMKM = kapabilitas (`users.umkm_access_granted_at`, `hasUmkmAccess()`), bukan role. Kategori UMKM = tabel `umkm_categories`.
