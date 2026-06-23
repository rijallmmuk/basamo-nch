# TASKS.md — Basamo NCH

> File ini adalah sumber kebenaran untuk semua task development.
> Update status setiap kali task selesai atau berubah.
> Format status: `[ ]` belum mulai · `[~]` sedang dikerjakan · `[x]` selesai

---

## ⬅️ AKTIF / NEXT SESSION

- [ ] **➡️ PUSH branch `feat/ref-wilayah-sumbar`** — 5 commit lokal belum di-push (tindakan pertama sesi baru).
- [x] **UserResource → halaman "Warga" murni + penyeragaman tabel admin** (2026-06-23/24): warga-only
      (role=warga; admin via form Desa), No. urut, tanpa checkbox/bulk, klik baris→Edit (Warga→Lihat),
      aksi inline kecuali Warga (⋮), kolom toggle demografi, sebutan sub-unit per desa, ikon akses UMKM.
- [~] **Design system "Nagari Creative Hub"** (2026-06-23) — token NCH (deep blue/gold/Plus Jakarta
      Sans) di app.css; publik home + login portal/admin + panel admin diselaraskan. Sisa: port
      halaman portal lain (modul/materi/kuis/leaderboard) ke token NCH. Lihat PROGRESS + [[stitch-redesign-plan]].
- [x] **Data master warga** (2026-06-23) — kolom `nik` (pisah username), demografi + tabel referensi
      agama/status_perkawinan/pekerjaan + enum JenisKelamin. [[warga-data-master]]
- [x] **➡️ Tabel `penduduk` (refactor 3-lapisan)** (2026-06-23) — identitas(`penduduk`) /
      akun(`users.penduduk_id`) / akses(Spatie+Shield); tabel referensi `jabatan` (kepala desa/aparat).
      Demografi dipindah users → penduduk; `nik` di-mirror di users (kunci login). UserForm tetap
      terpadu (upsert penduduk via `PendudukService`+`InteractsWithPenduduk`). Test `PendudukTest` (3).

---

## Milestone 1 — Setup & Fondasi (Target: Minggu 1–2)

> Catatan: proyek dibuat dari nol. Laravel + MySQL sudah ada, sisanya belum.
> Fokus: panel /admin dulu untuk bangun fondasi data LMS. Panel /portal menyusul.

### 1.1 Inisialisasi Proyek
- [x] Buat proyek Laravel baru (folder: basamo-nch) + MySQL
- [x] Install Filament v5 + panel admin (INSTALL_COMMANDS BLOK 1)
- [x] Install dependency: Shield, Permission, Media Library, Sluggable, ActivityLog, ApexCharts (BLOK 2-5)
- [x] Install dev tools: Pint, Pest (BLOK 6)
- [x] Konfigurasi .env database + buat database basamo_nch (BLOK 7)
- [x] Init Git repository + push ke GitHub (git@github.com:rijallmmuk/basamo-nch.git)
- [ ] Setup Laravel Pint untuk code formatting

### 1.2 Auth & RBAC (panel admin dulu)
- [x] Tambah trait HasRoles (Spatie) + implements FilamentUser ke model User
- [x] Tambah kolom `role` & `nagari_id` di migration users
- [x] Implementasi `canAccessPanel()` — tahap ini izinkan super_admin & nagari_admin
- [x] Definisikan 4 role: `super_admin`, `nagari_admin`, `warga`, `umkm_owner`
- [x] Setup Filament Shield + generate permission (BLOK 8, 11)
- [x] Buat super admin pertama: admin@basamo.nch / password
- [x] Test login super admin ke /admin (/admin/login → HTTP 200 ✓)

### 1.3 Model & Migration — JALUR LMS DULU
> Hanya tabel yang dibutuhkan LMS. Tabel SDGs/UMKM/IoT dibuat saat fitur itu dikerjakan.
- [x] Migration: `nagaris`
- [x] Migration: `users` (kolom `nagari_id`, `role`, `total_points`) + FK ke nagaris
- [x] Migration: `modules`
- [x] Migration: `module_pages`
- [x] Migration: `quizzes` + `quiz_questions` + `quiz_options`
- [x] Migration: `quiz_attempts` + `quiz_answers`
- [x] Migration: `user_module_progress`
- [x] Migration: `discussions`
- [x] Buat Eloquent Model + relasi untuk semua tabel LMS di atas
- [x] Buat Policy: ModulePolicy, QuizPolicy (scaffolded)
- [x] Seeder: 1 nagari dummy (NCH-001) + super admin + nagari admin

### 1.4 Tabel fitur lain (DITUNDA — kerjakan saat fiturnya dimulai)
- [ ] (nanti) Migration SDGs: `sdgs_activities`, `sdgs_documents`
- [ ] (nanti) Migration UMKM: `umkm_profiles`, `umkm_products`, `umkm_product_photos`
- [ ] (nanti) Migration IoT: `iot_sensors`, `iot_readings`
- [ ] (nanti) Migration: `news_feeds`

---

## Milestone 2 — LMS (Target: Minggu 3–5)

> Urutan: 2.1 & 2.2 (admin Filament) dulu — bangun & isi data modul/kuis di /admin.
> Lalu 2.3 (portal warga CUSTOM Blade, bukan panel Filament) untuk sisi belajar.

### 2.1 Manajemen Modul (Admin — Filament)
- [x] FilamentResource: `ModuleResource` (CRUD modul global & lokal)
- [x] RelationManager: `PagesRelationManager` (halaman per modul: teks, PDF, video embed)
- [x] Integrasi RichEditor bawaan Filament untuk konten halaman (teks)
- [x] Field URL terpisah untuk embed video YouTube/Google Drive
- [x] Field prerequisite_module pada form modul
- [x] Cover modul (2026-06-19) — dihidupkan lagi via Spatie Media Library (koleksi `cover`,
      konversi `card` webp 800×450) + cover default global; tampil di portal
- [x] Estimasi durasi belajar (2026-06-19) — `modules.estimated_minutes` + field admin + badge portal
- [x] Distribusi modul: toggle global vs lokal per nagari (via nagari_id nullable)

### 2.2 Kuis & Evaluasi (Admin — Filament)
- [x] FilamentResource: `QuizResource` (buat kuis per modul); **judul kuis dihapus** — diturunkan
      dari modul "Kuis: {judul modul}" (2026-06-19)
- [x] Form builder soal **pilihan ganda saja** (Repeater opsi, tandai jawaban benar)
- [x] Jawaban benar boleh >1 → soal pilihan jamak + **partial credit** (2026-06-19)
- [x] Audit admin (2026-06-19): keamanan modul kuis (anti cross-nagari), validasi materi per tipe,
      guard kuis tanpa soal, emoji→Heroicons, kolom tabel modul (cover/materi/kuis/durasi)
- [x] ~~Antrian penilaian essay~~ — DIBATALKAN: kuis MC-only auto-grade; QuizAttemptResource + LmsEssayGradingService dihapus
- [x] ~~Beri nilai + feedback essay~~ — DIBATALKAN (lihat keputusan di DECISIONS.md 2026-06)

### 2.3 Portal Belajar Warga (CUSTOM Blade + Livewire — LAPISAN 2)
- [x] Setup auth Laravel untuk warga + middleware role
- [x] Provisioning akun warga (2026-06-19): dibuat Admin Nagari, login **NIK + OTP**, paksa ganti
      sandi login pertama, email opsional + No. WhatsApp, rate-limit login, self-register dihapus
- [x] Layout portal: header + bottom navigation bar (menggantikan sidebar)
- [x] Controller + halaman daftar modul dengan status (terkunci/tersedia/selesai)
- [x] Halaman detail modul + course outline + navigasi halaman per halaman
- [x] Tampil materi: teks (HTML prose), PDF embed, video YouTube embed
- [x] Livewire QuizPlayer: kuis pilihan ganda + auto-grade
- [x] ~~QuizPlayer essay + antrian admin~~ — DIBATALKAN: MC-only (pending_review dihapus)
- [x] Progress tracker visual (progress bar per modul + page dots)
- [x] Bug fix: url()->previous() di quiz result, page ownership check, module scope check
- [ ] UX belajar: layar "Selesai!" saat semua materi tuntas + CTA kuis
- [x] UX quiz: progress "Soal X dari Y terjawab" + highlight live + scroll ke error
- [x] Notifikasi in-app: modul baru + kuis baru (observer) + hasil kuis (QuizPlayer); lonceng + halaman notifikasi

### 2.4 Sistem XP & Leaderboard — SELESAI
> Diadakan kembali sebagai XP berbasis pencapaian (lihat DECISIONS.md 2026-06). XP: modul +50, kuis +100, diskusi +20 (sekali per modul, idempotent via xp_logs).
- [x] `LmsPointService` (award modul/kuis/diskusi, idempotent) + tabel `xp_logs`
- [x] Update `total_points` otomatis: hook di LmsProgressService, QuizPlayer, DiscussionController
- [x] Halaman leaderboard portal (per nagari, urut total_points) + panel Top 5 di dashboard

### 2.5 Forum Diskusi ✓ SELESAI
> Model + migration discussions sudah ada. Layer portal dibangun.
- [x] Route: `portal.modules.discuss`, `.store`, `.show`, `.reply`
- [x] `DiscussionController`: index, show, store, reply — scope per nagari
- [x] View: CTA "Ruang Diskusi" di `modules/show.blade.php` + `discuss/index.blade.php` (thread list + form tanya)
- [x] View: `modules/discuss/thread.blade.php` — thread detail + balasan + form balas
- [x] Warga hanya bisa lihat & reply diskusi sesama nagari (scope via nagari penulis)
- [x] Moderasi admin (2026-06-20): `DiscussionResource` di `/admin` (grup LMS) — super_admin
      semua nagari, nagari_admin hanya nagarinya. Aksi pin/lepas, hapus/pulihkan (soft-delete),
      force-delete. `DiscussionPolicy` + Activity Log. Rate-limit posting (throttle:15,1).
      Test `DiscussionModerationTest`

### 2.6 Kerangka Halaman Front-End (sample, konten placeholder)
> Dikerjakan setelah LMS (2.3–2.5) selesai
- [x] Beranda (dashboard): hero sapaan + statistik + spotlight "Lanjutkan" + Aktivitas Belajar
- [x] Redesign UI LMS menyeluruh + komponen `components/portal/` (avatar, status-badge, content-badge, empty)
- [x] Halaman Leaderboard XP (per nagari) + panel Top 5 di dashboard
- [x] Halaman Lapak UMKM "Produk Saya" (umkm_owner): profil usaha + CRUD produk + foto;
      menu hanya tampil untuk pemilik (middleware umkm.owner). Test PortalUmkmTest
- [x] Shell: sidebar (desktop) + bottom nav (mobile); notifikasi+dropdown di top header

---

## Milestone 3 — SDGs Desa (Target: Minggu 6–7)

### 3.1 Manajemen SDGs (Admin Nagari)
- [ ] FilamentResource: `SdgsActivityResource` (input kegiatan per poin SDGs)
- [ ] Form dengan pemilihan poin SDGs (1–18) + ikon resmi per poin
- [ ] Upload dokumen bukti via Spatie Media Library
- [ ] Filament PDF Viewer untuk preview dokumen inline
- [ ] Kalkulasi skor otomatis (jumlah kegiatan per poin)

### 3.2 Visualisasi SDGs
- [ ] Widget: diagram lingkaran/radial 18 segmen ApexCharts
- [ ] Klik segmen → tampil daftar kegiatan + dokumen
- [ ] Perbandingan skor antar nagari (Super Admin only)

---

## Milestone 4 — UMKM (Target: Minggu 8–9)

### 4.1 Manajemen UMKM (Admin Nagari)
- [x] Buat akun pemilik UMKM dari panel Admin Nagari (via UserResource + aksi beri akses)
- [x] (keputusan) Akses UMKM = aksi admin "beri akses" yang menaikkan warga → `umkm_owner`
      (warga existing, bukan akun baru) — aksi tabel "Beri/Cabut akses UMKM" + UmkmAccessTest
- [x] FilamentResource: `UmkmProfileResource` (kelola profil usaha; scope nagari, pemilik
      = akun umkm_owner, nagari diwarisi dari pemilik) + UmkmProfileResourceTest
- [x] Antrian verifikasi produk dengan approval/reject + alasan (ProductsRelationManager:
      aksi Setujui/Tolak → status + approved_by/at + rejection_reason)

### 4.2 Input Produk (Pemilik UMKM)
- [x] Portal: form profil usaha (nama, kategori, deskripsi, WhatsApp, alamat) — nagari ikut pemilik
- [x] Portal: form tambah/edit produk (nama, deskripsi, harga opsional)
- [x] Upload multiple foto produk (maks 5) via Media Library + hapus foto saat edit
- [x] Status produk: pending/approved/rejected — produk baru/diubah → pending (verifikasi ulang)

### 4.3 Katalog Publik ✓ SELESAI
- [x] Halaman `/umkm` — akses tanpa login (Lapisan 1, Blade+Tailwind); hanya produk approved
      dari usaha aktif. `UmkmCatalogController` + layout `public/layouts/app`
- [x] Filter by nagari + kategori + pencarian nama produk (query string preserved)
- [x] Kartu produk dengan foto, info, tombol WA (detail: galeri foto + WhatsApp via `whatsappUrl()`)
- [x] Counter view produk (atomik, tanpa bump updated_at)
- [x] Notifikasi in-app pemilik saat produk disetujui/ditolak (`UmkmProductVerified`)
- [x] Test `PublicUmkmCatalogTest` (scope approved/aktif, filter, view_count, 404 non-approved)

---

## Milestone 5 — Dashboard & IoT (Target: Minggu 10–11)

### 5.1 Dashboard Super Admin
- [x] Metric cards: total nagari, warga, UMKM, produk menunggu (PlatformStatsWidget; sensor IoT menyusul)
- [x] ApexCharts: kemajuan LMS per nagari (bar chart) — LmsProgresChart
- [ ] ApexCharts: SDGs 18 poin rata-rata (radial chart) — menunggu pilar SDGs
- [x] ApexCharts: tren aktivitas 30 hari (area chart) — AktivitasBelajarChart (modul + kuis)
- [x] ApexCharts: sebaran kategori UMKM (donut chart) — UmkmKategoriChart
- [ ] Panel IoT: status sensor semua nagari — menunggu pilar IoT
- [ ] Feed berita & aktivitas terbaru

### 5.2 Dashboard Admin Nagari
- [x] Dashboard filtered per nagari (widget yang sama, ter-scope ke nagari admin)
- [x] ~~Widget: antrian essay~~ — DIBATALKAN (kuis MC-only auto-grade)
- [x] Widget: produk UMKM menunggu verifikasi (kartu "Produk menunggu" di PlatformStatsWidget)

### 5.3 Sensor IoT Simulasi
- [ ] Model + migration iot_sensors + iot_readings
- [ ] Seeder: data simulasi sensor (nilai berubah acak tiap 30 detik)
- [ ] Widget dashboard: panel status sensor per parameter
- [ ] Indikator warna: hijau/kuning/merah berdasarkan threshold

---

## Milestone 6 — Data Dummy & Ekspor (Target: Minggu 12)

### 6.1 Data Dummy
- [ ] Seeder: 3 nagari dengan nama & data lengkap
- [ ] Seeder: 1 admin nagari + 30 warga + 10 UMKM per nagari
- [ ] Seeder: 5 modul global + 2 modul lokal per nagari
- [ ] Seeder: 18 poin SDGs × 2–4 kegiatan per nagari
- [ ] Seeder: data sensor IoT simulasi
- [ ] Seeder: feed berita dummy per nagari

### 6.2 Ekspor Laporan
- [ ] Ekspor PDF: laporan kemajuan LMS per nagari (DomPDF)
- [ ] Ekspor PDF: laporan SDGs per nagari
- [ ] Ekspor Excel: data warga per nagari (Maatwebsite Excel)
- [ ] Ekspor Excel: data UMKM per nagari

---

## Milestone 7 — Polish & Testing (Target: Minggu 13–14)

- [~] Audit semua scope nagari (pastikan tidak ada data bocor) — super_admin & QuizResource route-binding (L2) beres; sisanya saat resource baru
- [~] Test semua 4 role login dan akses fitur — `SuperAdminAccessTest` (akses panel + policy + observer) lulus; login flow per role menyusul
- [ ] RBAC: kolom `role` = sumber kebenaran (Gate::before + observer sync) — SELESAI (lihat DECISIONS 2026-06)
- [x] (M1) NagariResource — super_admin kelola nagari (CRUD, SoftDeletes, guard anti-orphan, withCount warga/modul, NagariPolicy super_admin-only) + test
- [x] (M2) UserResource — super_admin & nagari_admin kelola user (CRUD, scope nagari, hash password, pengaman self-lockout, UserPolicy) + test
- [x] (M1) WilayahResource (2026-06-19) — master data wilayah per nagari (1 tingkat, sebutan
      konfigurabel), scope nagari, WilayahPolicy, audit; alamat warga users.wilayah_id + test
- [ ] Responsive check portal warga di mobile
- [x] Optimasi query N+1 LMS — eager-load di Module/Quiz/ActivityLog Resource (with/withCount/withExists)
- [ ] Pest test untuk Services utama: LmsPointService, dll
- [x] Setup Spatie Activity Log di operasi kritis (2026-06-19) — Module/ModulePage/Quiz/Nagari/User
      + viewer "Log Aktivitas" super_admin + test
- [ ] Setup Spatie Backup terjadwal
- [ ] Review semua Policy untuk edge case
- [x] Dokumentasi `.env.example` lengkap (2026-06-19) — identitas, locale id, storage R2, upload PDF
- [ ] (enhancement) Ordering modul per-nagari (kini global; keputusan produk, lihat PROGRESS)

---

## Backlog (Fase 2 — setelah MVP)

- [ ] Integrasi IoT sensor fisik via REST API / MQTT
- [ ] Migrasi storage ke Cloudflare R2
- [ ] Forum diskusi lanjutan
- [ ] Laravel Reverb untuk real-time dashboard
- [ ] Perbandingan antar nagari (grafik radar)
- [ ] Sertifikat kelulusan modul (PDF otomatis)
- [ ] PWA untuk akses mobile offline
- [ ] Self-service onboarding nagari baru
