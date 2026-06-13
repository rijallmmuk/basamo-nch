# TASKS.md — Basamo NCH

> File ini adalah sumber kebenaran untuk semua task development.
> Update status setiap kali task selesai atau berubah.
> Format status: `[ ]` belum mulai · `[~]` sedang dikerjakan · `[x]` selesai

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
- [ ] FilamentResource: `ModuleResource` (CRUD modul global & lokal)
- [ ] FilamentResource: `ModulePageResource` (halaman per modul: teks, PDF, video embed)
- [ ] Integrasi RichEditor bawaan Filament untuk konten halaman (teks)
- [ ] Field URL terpisah untuk embed video YouTube/Google Drive
- [ ] Field prerequisite_module pada form modul
- [ ] Upload thumbnail modul via Spatie Media Library
- [ ] Distribusi modul: toggle global vs lokal per nagari

### 2.2 Kuis & Evaluasi (Admin — Filament)
- [ ] FilamentResource: `QuizResource` (buat kuis per modul)
- [ ] Form builder untuk soal pilihan ganda + essay
- [ ] Antrian penilaian essay untuk Admin Nagari
- [ ] Fitur beri nilai + feedback untuk jawaban essay

### 2.3 Portal Belajar Warga (CUSTOM Blade + Livewire — LAPISAN 2)
> Bukan panel Filament. Controller + Blade + Livewire. Auth Laravel standar.
- [ ] Setup auth Laravel untuk warga (login/register portal) + middleware role
- [ ] Layout portal Blade (header, nav, mobile-first)
- [ ] Controller + halaman daftar modul dengan status (terkunci/tersedia/selesai)
- [ ] Halaman detail modul + navigasi halaman per halaman
- [ ] Tampil materi: teks (HTML), PDF embed, video YouTube/GDrive embed
- [ ] Livewire QuizPlayer: kuis pilihan ganda + auto-grade
- [ ] Livewire QuizPlayer: kuis essay + submit ke antrian admin
- [ ] Progress tracker visual (progress bar per modul)
- [ ] Notifikasi in-app sederhana: modul baru, hasil kuis

### 2.4 Sistem Poin & Leaderboard
- [ ] Service: `LmsPointService` (kalkulasi poin per aktivitas)
- [ ] Update poin otomatis saat halaman selesai, kuis lulus
- [ ] Widget leaderboard per nagari di portal warga
- [ ] Tampilan peringkat + total poin warga

### 2.5 Forum Diskusi
- [ ] Thread diskusi per modul
- [ ] Warga bisa post pertanyaan & jawaban
- [ ] Admin nagari bisa pin dan moderasi

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
- [ ] Buat akun pemilik UMKM dari panel Admin Nagari
- [ ] FilamentResource: `UmkmProfileResource` (kelola profil usaha)
- [ ] Antrian verifikasi produk dengan approval/reject + alasan

### 4.2 Input Produk (Pemilik UMKM)
- [ ] Portal: form profil usaha (nama, kategori, deskripsi, WhatsApp)
- [ ] Portal: form tambah/edit produk (nama, deskripsi, harga opsional)
- [ ] Upload multiple foto produk (maks 5) via Media Library
- [ ] Status produk: pending/approved/rejected

### 4.3 Katalog Publik
- [ ] Halaman `/umkm` — akses tanpa login
- [ ] Filter by nagari + kategori
- [ ] Kartu produk dengan foto, info, tombol WA
- [ ] Counter view produk

---

## Milestone 5 — Dashboard & IoT (Target: Minggu 10–11)

### 5.1 Dashboard Super Admin
- [ ] Metric cards: total nagari, warga, UMKM, sensor aktif
- [ ] ApexCharts: kemajuan LMS per nagari (bar chart)
- [ ] ApexCharts: SDGs 18 poin rata-rata (radial chart)
- [ ] ApexCharts: tren aktivitas 30 hari (line chart)
- [ ] ApexCharts: sebaran kategori UMKM (donut chart)
- [ ] Panel IoT: status sensor semua nagari
- [ ] Feed berita & aktivitas terbaru

### 5.2 Dashboard Admin Nagari
- [ ] Dashboard filtered per nagari (sama strukturnya, data nagari sendiri)
- [ ] Widget: antrian essay menunggu penilaian
- [ ] Widget: produk UMKM menunggu verifikasi

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

- [ ] Audit semua scope nagari (pastikan tidak ada data bocor)
- [ ] Test semua 4 role login dan akses fitur
- [ ] Responsive check portal warga di mobile
- [ ] Optimasi query N+1 (gunakan `with()` di semua Resource)
- [ ] Pest test untuk Services utama: LmsPointService, dll
- [ ] Setup Spatie Activity Log di semua operasi kritis
- [ ] Setup Spatie Backup terjadwal
- [ ] Review semua Policy untuk edge case
- [ ] Dokumentasi `.env.example` lengkap

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
