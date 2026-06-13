# DECISIONS.md — Smart Learning Center Basamo NCH

> Log semua keputusan arsitektur dan teknologi beserta alasannya.
> Setiap kali ada keputusan baru atau perubahan keputusan, catat di sini.
> Format: tanggal · keputusan · alasan · alternatif yang ditolak

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

---

## Template untuk keputusan baru

```
### [YYYY-MM] Judul keputusan
**Keputusan**: Apa yang diputuskan.
**Alasan**: Mengapa keputusan ini dibuat.
**Ditolak**: Alternatif yang dipertimbangkan dan alasan penolakannya.
```
