# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya. Ringkas (≤60 baris).
> Detail task → TASKS.md · Keputusan → DECISIONS.md
> Aturan: baca CLAUDE.md + PROGRESS.md + TASKS.md di awal sesi.

---

## Status

**Fase**: MVP — LMS lengkap; pilar **UMKM (sisi admin)** & **dashboard admin** kini ada di /admin.
**Progres**: ~99%. LMS + provisioning warga + master wilayah di **`main`**. Sedang berjalan:
branch **`feat/umkm`** — akses UMKM, UmkmProfileResource + antrian verifikasi, dashboard
ApexCharts, Lapak portal pemilik, **katalog publik `/umkm` (M4.3 SELESAI)**, **+ audit RBAC/DB
(role→kapabilitas, OTP expiry, harga integer, taksonomi kategori)**. Belum merge.
**Pilar UMKM kini lengkap** (admin + portal pemilik + katalog publik). Next: SDGs/IoT atau PR ke main.
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

## Katalog publik /umkm (2026-06-20) — branch `feat/umkm`
Frontend Lapisan 1 (Blade+Tailwind, tanpa login). Suite **hijau (72 test)**.
- **Route:** `public.umkm.index` (`/umkm`) + `public.umkm.show` (`/umkm/{product:slug}`).
  `UmkmCatalogController`: hanya produk `status=approved` dari profil `status=active`.
- **Index:** filter nagari + kategori + pencarian nama (query string preserved), grid kartu
  produk (foto via `coverUrl()` konversi `card`, harga rupiah/"Hubungi penjual"), paginate 12.
- **Detail:** galeri foto, deskripsi, info usaha, tombol WhatsApp via `UmkmProfile::whatsappUrl()`
  (normalisasi 08xx→628xx + pesan). View counter atomik (`view_count + 1` tanpa bump updated_at).
  Non-approved/usaha nonaktif → 404.
- **Notifikasi:** `UmkmProductVerified` (database) dikirim ke pemilik saat admin setujui/tolak
  di `ProductsRelationManager` → muncul di lonceng portal.
- **Layout:** `public/layouts/app.blade.php` (header + link Masuk Portal + footer).
- **Test:** `PublicUmkmCatalogTest` (4 kasus) + `UmkmProfileResourceTest` (assert notifikasi).
- **Catatan:** jalankan `npm run build` (sudah) — view publik baru pakai kelas Tailwind.

## Audit modul LMS (2026-06-20) — branch `feat/umkm`
Audit kesiapan produksi modul (DB→model→policy→admin→portal). Suite **hijau (81)**.
Test baru: `LmsModuleAuditTest` (9 kasus). Yang diperbaiki:
- **#1 XSS (kritis):** konten materi & deskripsi modul kini `->sanitizeHtml()` di Blade
  (`page.blade:93`, `show.blade:47`). Sebelumnya `{!! mentah !!}` → admin nagari bisa
  inject `<script>` ke browser warga.
- **#2 Prasyarat lintas-nagari (tinggi):** guard di `ModuleForm` (filter opsi + rule
  server-side) — prasyarat wajib global ATAU senagari. Cegah modul terkunci permanen.
- **#3/#4 Penyelesaian (tinggi):** `markPageCompleted` ditulis ulang — rekonsiliasi
  `pages_completed` terhadap halaman yang masih ada (buang ID hantu) + hitung ulang
  status tiap buka (tak lagi early-return). Hapus halaman tak lagi membuat warga macet
  `in_progress`/XP tak cair. Dibungkus transaksi + `lockForUpdate` (anti race).
- **#5 N+1:** `ModuleController::index` ambil `completedModuleIds` sekali →
  `getModuleStatusUsing()` in-memory (sebelumnya ~2 query/modul).
- **#6 Notifikasi publish:** `NewModulePublished` jadi `ShouldQueue` + observer
  `chunkById(500)` (modul global tak lagi blok request / boros memori).
- **#7/#8 File yatim:** `ModulePage` hapus PDF saat record dihapus/diganti; kosongkan
  kolom tak relevan saat ganti tipe. `ModuleObserver::deleting` bersihkan PDF saat
  modul di-force-delete (cascade DB lewati event).
- **#11 Prasyarat dihapus:** soft-delete prasyarat tak lagi mengunci warga.
- **#9 (by design, tak diubah):** "selesai" = membuka tiap halaman (tanpa dwell/scroll).
  Keputusan produk — perlu arahan bila mau gating lebih ketat.

## Audit kuis + diskusi LMS (2026-06-20) — branch `feat/umkm`
Lanjutan audit kesiapan produksi (DB→model→policy→admin→portal). Suite **hijau (85)**.
Test: +4 di `QuizPlayerGradingTest` (12 total). Yang diperbaiki:
- **K1 (tinggi):** `QuizPlayer::submit()` kini re-validasi kelayakan di server
  (`canAttempt()` + guard `submitted`). Sebelumnya gating hanya saat GET di controller —
  `submit()` bisa dipanggil berulang via Livewire untuk **melewati `max_attempts`** /
  mengulang setelah lulus. Sekarang ditolak server-side.
- **K2 (sedang):** `submit()` menyaring `selected_option_id` ke opsi milik soal
  (intersect) — cegah ID asing dari klien memicu error FK / baris jawaban sampah.
- **K3 (sedang):** `NewQuizPublished` jadi `ShouldQueue` + `QuizObserver` `chunkById(500)`
  (paralel modul #6).
- **D4 (hardening):** rute POST diskusi (store/reply) diberi `throttle:15,1` (anti-spam).
- **Aman terverifikasi:** diskusi XSS-safe (`{{ }}`), scoping nagari solid
  (`guardModule`+`threadVisibleToUser`), nesting dibatasi top-level; kuis scoring
  partial-credit benar, guard admin (≥1 benar & ≥1 salah), XP idempotent.
- **Didokumentasikan, tak diubah:** K4 notif "kuis baru" prematur (kuis dibuat sebelum
  ada soal — perbaikan = redesign notif), K5 enum mati `quiz_attempts.status=pending_review`
  (sisa essay; ubah enum = churn besar), D5 `is_pinned`/soft-delete diskusi tanpa UI
  moderasi admin (fitur belum dibangun).

## Audit auth warga NIK+OTP (2026-06-20) — branch `feat/umkm`
Audit kesiapan produksi alur login/provisioning warga. Suite **hijau (92)**.
Test: `AuthWargaAuditTest` (7 kasus, semua bug dikonfirmasi merah dulu). Diperbaiki:
- **A1 (tinggi):** warga `status=inactive` **dulu tetap bisa login & pakai portal**
  (login & `EnsurePortalUser` tak cek status; bandingkan `canAccessPanel` admin yang cek).
  Kini ditolak di login **dan** dikeluarkan di tengah sesi via middleware.
- **A5 (sedang):** ganti sandi biasa kini wajib `current_password` (rule Laravel).
  Paksa-ganti login pertama tetap tanpa (sudah autentik via OTP). View change-password
  tampilkan field sandi lama secara kondisional.
- **A6 (sedang):** kebijakan sandi warga `Password::min(8)->letters()->numbers()`
  (sebelumnya `min:8` saja).
- **Bonus (robustness):** `HomeController` `total_xp ?? 0` pada query peringkat —
  **bukan** bug produksi (`auth()->user()` selalu dimuat dari DB, kolom NOT NULL default 0);
  hanya artefak test (factory tak set total_xp). Factory kini set `total_xp=0` cermin DB.
- **Aman terverifikasi:** provisioning (NIK `digits:16` unik, OTP 6-digit `random_int`,
  expiry 7h konsisten, Reset OTP via `issueOtp`), session regenerate saat login,
  logout invalidate+regenerateToken, CSRF di semua form, autocomplete benar,
  `password` hashed cast, `initial_otp` Hidden + tak di-log, gating admin↔portal via role.
- **Didokumentasikan, tak diubah:** A2 rate-limiter tak `hit` di cabang role/otp-mismatch
  (butuh kredensial valid; menghindari penalti admin yang salah form), A3 throttle key
  per-identitas+IP (kompromi wajar), A4 `initial_otp` plaintext + expiry 7h (keputusan sadar).
- **Koreksi audit kuis:** K5 (enum mati `pending_review`) **TIDAK ADA** — enum live sudah
  `enum('in_progress','passed','failed')`; saya keliru baca file migrasi *create*, bukan DB live.

## Moderasi diskusi (2026-06-20) — branch `feat/umkm`
Menutup celah **D5** dari audit diskusi. Suite **hijau (99)**. Test: `DiscussionModerationTest` (7).
- **`DiscussionResource`** (grup nav LMS, read-only — tanpa create/edit isi): tabel thread+balasan
  per modul, kolom tipe/modul/penulis/nagari/isi/balasan/disematkan/dibuat/dihapus. Filter
  tipe/modul/nagari(super)/disematkan/trashed.
- **Cakupan:** super_admin semua nagari; nagari_admin **hanya diskusi warga nagarinya** (scope
  query `whereHas user nagari_id` + `DiscussionPolicy` per-record). super_admin via Gate::before.
- **Aksi:** Sematkan/Lepas (`is_pinned`, hanya pertanyaan top-level), Hapus (soft), Pulihkan,
  Force-delete. `Discussion` kini `LogsActivity` (log pin + hapus/pulihkan, useLogName `diskusi`).
- **Integrasi portal:** soft-delete otomatis menyembunyikan dari portal (SoftDeletes scope di
  relasi `discussions()`/`replies`); pin menaikkan thread (portal `orderByDesc('is_pinned')`).
- **Catatan minor (sadar):** soft-delete thread tak cascade ke balasannya (balasan jadi tak
  terjangkau di portal karena thread 404; tetap tampil di tabel admin untuk dimoderasi terpisah).

## Audit XP & leaderboard (2026-06-20) — branch `feat/umkm`
Audit kesiapan produksi (DB→model→service→controller→view). Suite **hijau (104)**.
Test: `LmsXpLeaderboardTest` (5). Diperbaiki:
- **L1 (sedang):** warga `status!=active` dulu ikut leaderboard + hitungan peringkat/total.
  Kini `LeaderboardController` & `HomeController` (Top 5) filter `status='active'`.
- **L2 (sedang):** peringkat daftar (posisional `firstItem+index`) tak konsisten dengan
  badge "Posisimu" (kompetisi) saat **seri** (sering, XP kasar). Kini daftar pakai
  **peringkat kompetisi** (`competitionRanks` — 1 query tambahan, tangani seri lintas-halaman),
  konsisten dengan `rankOf`.
- **L3 (robustness):** `rankOf` pakai `total_xp ?? 0`.
- **L4 (robustness):** `LmsPointService::award` dibungkus `DB::transaction` (ledger & total_xp
  tak drift bila gagal di tengah).
- **Aman terverifikasi:** idempotensi kokoh (`xp_logs` UNIQUE(user,source,source_id) +
  `firstOrCreate` tangkap race → tak ada XP ganda walau konkuren; `source` cegah tabrakan
  module_id=quiz_id), warga soft-deleted keluar leaderboard (SoftDeletes), scoping per-nagari,
  admin tak masuk (filter role=warga). Jumlah XP via konstanta (modul 50/kuis 100/diskusi 20).

## Audit pilar UMKM (2026-06-20) — branch `feat/umkm`
Audit kesiapan produksi end-to-end (DB→model→policy→admin→portal→publik). Suite **hijau (107)**.
Test: `UmkmAuditTest` (3) + `UmkmAccessTest` diperluas. Diperbaiki:
- **U1 (sedang):** katalog publik `show()` null-safe profil (`?->status`) — profil soft-deleted
  tak lagi memicu 500 (kini 404).
- **U2 (sedang):** form profil validasi `unique(user_id, ignoreRecord)` — profil ganda untuk
  pemilik sama beri pesan validasi, bukan crash unique DB.
- **U3 (keputusan user):** cabut akses UMKM kini **menonaktifkan profil** pemilik (keluar dari
  katalog publik; data tetap, bisa diaktifkan lagi). Cegah konten publik tak terkelola.
- **U4 (rendah):** validasi `harga` numeric→integer (cegah desimal terpotong).
- **Aman terverifikasi:** ownership produk (`UmkmProductPolicy`: hasUmkmAccess + user_id),
  **tak ada IDOR lintas-nagari** (`getRecordRouteBindingEloquentQuery` ter-scope), nagari diwarisi
  pemilik, edit produk → reset pending, hapus foto ter-scope produk, katalog approved+aktif +
  view-counter atomik pasca-otorisasi, XSS-safe (`{{ }}`), owner options ter-scope, kategori
  global super_admin-only.
- **Didokumentasikan (sadar):** U5 unggah foto >sisa-slot didrop tanpa error (by design),
  U6 produk bisa ditambah ke profil nonaktif (tak tampil publik).

## Audit infrastruktur admin (2026-06-20) — branch `feat/umkm`
Audit User/Nagari/Wilayah Resource + dashboard widgets. Suite **hijau (110)**.
Test: `AdminInfraAuditTest` (3). Diperbaiki (keduanya rendah/defense-in-depth):
- **I1:** bulk delete pengguna kini cegah self-lockout (`guardSelfInBulk` → halt bila akun
  sendiri terpilih). DeleteAction baris tunggal sudah aman; bulk sebelumnya tidak.
- **I2:** guard server-side peran di `CreateUser`/`EditUser` — nagari_admin dipaksa `role=warga`
  (create) & tak bisa ubah peran (edit), tak lagi bergantung pada enforcement opsi Select Filament.
- **Aman terverifikasi (kuat):**
  - **Tak ada eskalasi hak**: nagari_admin tak bisa buat/menaikkan admin (diuji dgn email+sandi
    lengkap → Filament Select enforce `in:options`, plus guard I2). `UserPolicy` batasi
    nagari_admin ke warga senagari.
  - **Tak ada IDOR lintas-nagari**: User/Wilayah/Module/Quiz/UmkmProfile/Discussion semua
    scope `getRecordRouteBindingEloquentQuery`.
  - **Nagari** super_admin-only (NagariPolicy all-false + Gate::before); anti-orphan delete
    ter-wire di DeleteAction tunggal + ForceDelete; tanpa bulk-delete (guard tak bisa dilewati).
  - **Self-edit**: tak bisa self-demote/nonaktifkan/pindah-nagari (EditUser).
  - **Dashboard widgets** semua ter-scope nagari untuk nagari_admin (PlatformStats/LmsProgres/
    AktivitasBelajar/UmkmKategori) — tak ada bocor lintas-nagari.
  - ActivityLog super-only read-only; password hashed cast + opsional saat edit.

## Audit notifikasi in-app & media/upload (2026-06-20) — branch `feat/umkm`
Suite **hijau (113)**. Test: `MediaUploadAuditTest` (3) + probe verifikasi.
- **Notifikasi — aman, tanpa perubahan:** `$user->notifications()` per-user (tak ada bocor
  antar-warga); `data[title/body]` di-render `{{ }}` (XSS-safe); `data[icon]` literal di kode
  (bukan input user); markAsRead saat buka halaman. Notif fan-out (modul/kuis) sudah ShouldQueue.
- **Media — aman terverifikasi:** soft-delete produk **mempertahankan** foto (Spatie tak hapus
  saat soft-delete — diuji); force-delete menghapus foto; batas 5 foto dihormati; validasi mime/size
  (gambar 2MB, PDF 10MB); orphan PDF dibersihkan (audit modul); tanpa `{!! !!}`.
- **Diperbaiki — portabilitas storage R2 (M1/M2, rendah):** unggahan **gambar** sudah ikut
  `MEDIA_DISK` (default `public`), tapi **PDF materi** dulu hard-coded disk `'public'` di 5 tempat
  (upload + render×2 + cleanup×2) → tak akan pindah ke R2. Kini semua lewat
  `config('media-library.disk_name')` (= `MEDIA_DISK`), jadi **satu env** untuk semua unggahan.
  `.env.example`: dokumentasikan `MEDIA_DISK` (set `s3` bareng `FILESYSTEM_DISK=s3` untuk R2).
- **Catatan (sadar):** PDF materi di disk publik = bisa diakses tanpa login bila URL bocor
  (keputusan MVP; materi edukatif non-sensitif).

## Audit migrasi/skema DB — skala nasional (2026-06-20) — branch `feat/umkm`
Audit 19 tabel + index dari DB live. Suite **hijau (113)** di MySQL (dev) & SQLite (test). 3 migrasi:
- **Index komposit** (`optimize_indexes_for_national_scale`): `users(nagari_id,role,status,total_xp)`
  [leaderboard filter+sort 1 index], `quiz_attempts(user_id,quiz_id,status)`,
  `notifications(notifiable_type,notifiable_id,read_at)` [unread tiap page-load],
  `umkm_products(status,approved_at)` [katalog publik], `discussions(module_id,parent_id)`.
  **Buang 6 index single redundan** (prefix kiri komposit/unique) → tanpa bloat.
- **Buang kolom mati** `users.avatar` (tak pernah diisi; avatar = inisial).
- **Rename `wilayah`→`wilayahs`** (konsistensi plural; FK users.wilayah_id ikut otomatis).
- **Retensi** (`routes/console.php`): prune notifikasi read >90 hari + `activitylog:clean` harian
  (onOneServer) — cegah tabel event membengkak.
- **Dipertahankan sadar:** `email_verified_at` (bawaan Laravel), enum `quiz_attempts.status=in_progress`
  (ruang fitur resume), `pages_completed` JSON (denormalisasi tepat untuk skala).
- **Ditunda (keputusan gaya):** seragamkan bahasa kolom (LMS English vs UMKM/nagari Indonesia) —
  churn besar, bukan kebutuhan teknis.

## Foto profil + logo nagari (2026-06-20) — branch `feat/umkm`
Fitur media via Spatie Media Library (tanpa dependency baru — sudah terpasang). Suite **hijau (119)**.
Test: `ProfilePhotoLogoTest` (6).
- **Foto profil warga:** `User implements HasMedia` koleksi `avatar` (singleFile, konversi `thumb`
  crop 256² webp) + `avatarUrl()`. Halaman portal **"Profil Saya"** (`portal.profile.edit/update`):
  unggah/ganti/hapus foto; tautan di dropdown header. Komponen `x-portal.avatar` kini terima
  `:src` (foto bila ada, fallback inisial) — dipakai di header. `ProfileController`.
- **Logo nagari:** `Nagari implements HasMedia` koleksi `logo` (opsional) + `logo_kabupaten`
  (terima SVG) + `logoUrl()`/`kabupatenLogoUrl()` (serve original — SVG aman). Field upload di
  `NagariForm` (super_admin). Helper siap untuk frontend publik.
- **Foto produk UMKM:** sudah multi-foto (maks 5) — tak berubah.
- **Storage:** semua ikut `MEDIA_DISK` (lihat unifikasi storage); kelas Tailwind `file:` baru →
  `npm run build` (sudah).
- **Catatan:** kolom mati `users.avatar` (di-drop saat audit DB) memang tak dipakai — avatar kini
  di tabel `media`, bukan kolom. Logo kabupaten di-attach per-nagari (bukan tabel kabupaten
  terpisah) demi kesederhanaan; normalisasi bisa menyusul bila perlu.

## Audit pengambilan & penampilan data (2026-06-20) — branch `feat/umkm`
Fokus skala nasional: jalur baca/tampil katalog publik (trafik tinggi, tanpa login).
Suite **hijau (130)**. Tanpa dependency baru.
- **#1 view_count anti-inflasi:** increment maks 1×/pengunjung/6 jam via `Cache::add` atomik
  (kunci IP+produk) di `UmkmCatalogController::show` — cegah write amplification & inflasi bot.
- **#2 simplePaginate:** katalog publik tak lagi jalankan `COUNT(*)` terfilter tiap load.
- **#3 pencarian FULLTEXT:** index `umkm_products_search_fulltext` (nama+deskripsi); query
  boolean+wildcard awalan, operator dibersihkan; fallback LIKE untuk term <3 huruf / non-MySQL
  (sqlite test). Migrasi MySQL-guarded.
- **#4 cache dropdown filter:** `nagariList`/`kategoriList` di-`Cache::remember` 1 jam.
- **#6 Antrian Verifikasi Produk (global):** `UmkmProductResource` (list-only, grup UMKM, badge
  jumlah pending) — nagari_admin lintas-usaha di nagarinya, super_admin semua. Logika verifikasi
  dipindah ke `UmkmService::verifyProduct` (dipakai resource + relation manager). Policy
  `viewAny/view` admin. Test `UmkmProductVerificationTest` (4).
- **#7 normalisasi WhatsApp:** `UmkmProfile::normalizedWhatsapp()` tangani 0/00/+62/8xx.
- **DITUNDA #5 (cache halaman katalog):** keputusan infra — disarankan edge-cache Cloudflare di
  `/umkm` (bukan kode app), karena coupling CDN + interaksi dengan view_count di halaman detail.

## Konsolidasi migrasi & seeder (2026-06-20) — branch `feat/umkm`
Rapikan riwayat migrasi: **46 → 22 file** (1 migrasi per tabel, semua alter dilipat
ke create-nya). Diverifikasi via `mysqldump --no-data` sebelum/sesudah: skema
**fungsional identik** (beda hanya kosmetik — nama index `order`→`sort_order`,
`wilayah`→`wilayahs`, urutan kolom `event` di tabel vendor). Suite **hijau (130)**.
- FK users→nagaris/wilayahs ditambah di migrasi tabel terkait (users dibuat duluan).
- `umkm_categories` dipindah sebelum `umkm_profiles` (FK inline). FULLTEXT umkm_products
  MySQL-guarded. Komposit index skala-nasional kini inline di create masing-masing.
- **Seeder:** `NagariSeeder`+`UserSeeder` (redundan dgn DemoSeeder) dihapus → `CoreSeeder`
  (role + super admin, esensial produksi, idempotent) + `DemoSeeder` (panggil CoreSeeder
  lalu isi demo). `DatabaseSeeder`→`DemoSeeder`. Produksi: `db:seed --class=CoreSeeder`.
- `migrate:fresh --seed` terverifikasi: 2 nagari, 16 warga, 6 UMKM, 7 modul, 16 produk.
- **Sudah merge ke `main`** (PR #2). Pasca-merge: `migrate:fresh --seed` bersih + suite
  **hijau penuh (130 test, 370 assertion, 0 gagal)** dikonfirmasi ulang.

## Landing publik + hardening back-button (2026-06-20) — branch `feat/public-landing-portal-nostore`
- **Landing page `/`** (Lapisan 1, tanpa login): `Public\HomeController` + `public.home`
  (hero, statistik ringkas ter-cache 1 jam, 4 pilar — LMS/UMKM aktif, SDGs/IoT "segera").
  Header layout publik kini brand "Basamo NCH" → tautan ke beranda. Sebelumnya `/` redirect
  ke login portal (belum ada halaman depan).
- **Anti back-button:** middleware `PreventCachedHistory` (alias `no-store`) di grup portal
  terproteksi → `Cache-Control: no-store` + `Pragma: no-cache`. Setelah logout, Back tak lagi
  menampilkan dashboard basi dari riwayat browser (akses server sudah aman sebelumnya).
- Test: `PublicHomeTest` (landing tampil, header no-store di portal, publik tanpa no-store);
  `ExampleTest` diselaraskan (root → landing). Suite **hijau (133)**. `npm run build` dijalankan.

## Keputusan teknis aktif (detail di DECISIONS.md)
- Kuis MC-only; nilai angka 0–100 (bukan %); tanpa bobot poin per soal.
- Scoping nagari admin manual (bukan Filament Tenancy). super_admin kelola global.
- Leaderboard berbasis XP pencapaian (sempat dihapus, lalu dihidupkan lagi).
- UI: komponen Blade sendiri (bukan Flux/WireUI) + canvas-confetti.
- PDF materi: disk `public` + symlink; PHP `upload_max_filesize=10M`/`post_max_size=12M` (set di server; lihat DECISIONS).
- Filament v5: `Schema $schema`; shield define_via_gate; super_admin via Gate::before.
- RBAC: role = persona (super_admin/nagari_admin/warga). Akses UMKM = kapabilitas (`users.umkm_access_granted_at`, `hasUmkmAccess()`), bukan role. Kategori UMKM = tabel `umkm_categories`.
