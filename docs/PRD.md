# Product Requirements Document (PRD)
## Smart Learning Center Basamo NCH

---

| Atribut | Detail |
|---|---|
| Nama Produk | Smart Learning Center Basamo NCH |
| Versi Dokumen | 1.1 — Final (Update Dependency Stack) |
| Tanggal | Juni 2026 |
| Status | Siap untuk Development |
| Program | Nagari Creative Hub (NCH) — Gubernur Sumatera Barat |
| Skala | Pilot 3 Nagari → Nasional (bertahap) |

---

## Daftar Isi

1. [Latar Belakang & Visi](#1-latar-belakang--visi)
2. [Tujuan & Sasaran](#2-tujuan--sasaran)
3. [Pengguna & Role](#3-pengguna--role)
4. [Arsitektur Sistem](#4-arsitektur-sistem)
5. [Empat Pilar Fungsional](#5-empat-pilar-fungsional)
   - 5.1 [LMS — Portal Belajar](#51-lms--portal-belajar)
   - 5.2 [SDGs Desa](#52-sdgs-desa)
   - 5.3 [Sistem Manajemen UMKM](#53-sistem-manajemen-umkm)
   - 5.4 [Sensor IoT Bencana](#54-sensor-iot-bencana)
6. [Dashboard Analitik](#6-dashboard-analitik)
7. [Tech Stack & Keputusan Teknologi](#7-tech-stack--keputusan-teknologi)
8. [Skema Role & Permission](#8-skema-role--permission)
9. [Alur Sistem Utama](#9-alur-sistem-utama)
10. [Skema Database](#10-skema-database)
11. [Rencana Pengembangan (Roadmap)](#11-rencana-pengembangan-roadmap)
12. [Data Dummy untuk Demo](#12-data-dummy-untuk-demo)
13. [Risiko & Mitigasi](#13-risiko--mitigasi)
14. [Keputusan yang Masih Terbuka](#14-keputusan-yang-masih-terbuka)

---

## 1. Latar Belakang & Visi

### Latar Belakang

**Medan Nan Balinduang** adalah konsep tradisional Minangkabau — ruang belajar bersama tempat masyarakat dulu menimba ilmu silat, adat, dan kearifan lokal. **Smart Learning Center Basamo NCH** menghidupkan kembali semangat ini dalam bentuk platform digital yang melayani nagari-nagari di Sumatera Barat.

Platform ini lahir dari program **Nagari Creative Hub (NCH)** yang digagas Gubernur Sumatera Barat, didukung oleh program pengabdian dosen dengan kepakaran di bidang Smart Learning. NCH memiliki 4 pilar utama yang seluruhnya terintegrasi dalam satu sistem terpadu.

### Visi

> Menjadi pusat pembelajaran digital dan pemberdayaan nagari yang inklusif, terukur, dan berkelanjutan — mulai dari nagari di Sumatera Barat hingga seluruh pelosok Indonesia.

### Misi

- Meningkatkan literasi digital masyarakat nagari melalui pembelajaran terstruktur
- Mendokumentasikan dan mengukur pencapaian SDGs di tingkat desa
- Mempromosikan dan memberdayakan UMKM lokal secara digital
- Menyediakan sistem pemantauan kondisi bencana berbasis IoT secara real-time

---

## 2. Tujuan & Sasaran

| Pilar | Tujuan | Indikator Keberhasilan |
|---|---|---|
| LMS | Meningkatkan literasi digital warga nagari | Jumlah warga aktif belajar, rata-rata nilai kuis, jumlah modul diselesaikan |
| SDGs | Mengukur & mendokumentasikan pencapaian SDGs Desa | Skor SDGs per poin, jumlah kegiatan tercatat, dokumen bukti terunggah |
| UMKM | Mempromosikan UMKM & mengukur adopsi digital | Jumlah UMKM terdaftar, produk dipromosikan, persentase adopsi digital |
| IoT | Monitoring kondisi bencana real-time | Sensor aktif, alert terkirim tepat waktu |

---

## 3. Pengguna & Role

Sistem memiliki **4 role utama** dengan hierarki akses yang jelas.

```
┌─────────────────────────────────────────────────┐
│                  SUPER ADMIN                    │
│         (Tim Dosen / Universitas)               │
│  Kendali penuh lintas semua nagari              │
└──────────────────┬──────────────────────────────┘
                   │ mengelola
        ┌──────────▼──────────┐
        │    ADMIN NAGARI     │
        │  (1 orang per desa) │
        │  Kelola data nagari │
        └──┬──────────────┬───┘
           │ mendaftarkan  │ memberi akun
    ┌──────▼──────┐  ┌────▼──────────┐
    │    WARGA    │  │ PEMILIK UMKM  │
    │  (Pelajar)  │  │  (Input produk│
    │  Portal LMS │  │   & promosi)  │
    └─────────────┘  └───────────────┘
```

### Rincian Hak Akses per Role

#### Super Admin
- Menambah, mengedit, menonaktifkan nagari
- Membuat dan mendistribusikan modul global ke semua nagari
- Memantau dashboard analitik lintas semua nagari
- Melihat laporan SDGs, LMS, dan UMKM semua nagari
- Mengekspor laporan PDF dan Excel/CSV
- Mengelola akun Admin Nagari
- Konfigurasi sistem global (parameter IoT, pengaturan leaderboard, dll)

#### Admin Nagari
- Mendaftarkan warga ke portal belajar
- Memberi akun kepada pemilik UMKM
- Menginput dan mengedit data kegiatan SDGs (18 poin)
- Mengunggah dokumen bukti kegiatan SDGs
- Memverifikasi dan menyetujui produk UMKM sebelum tampil publik
- Menambahkan modul lokal khusus nagarinya
- Memantau dashboard analitik nagarinya sendiri
- Mengekspor laporan PDF dan Excel/CSV nagarinya

#### Warga / Pelajar
- Mengakses modul belajar sesuai urutan prerequisite
- Mengerjakan kuis pilihan ganda dan essay
- Berpartisipasi di forum diskusi
- Melihat progress belajar pribadi
- Melihat leaderboard peringkat antar warga se-nagari
- Menerima notifikasi in-app (modul baru, hasil kuis, dll)

#### Pemilik UMKM
- Login dengan akun dari Admin Nagari
- Mengisi profil usaha (nama, kategori, deskripsi)
- Mengunggah foto produk
- Mencantumkan kontak WhatsApp untuk transaksi
- Mengedit informasi produk kapan saja
- Melihat statistik produk (jumlah tayangan)

---

## 4. Arsitektur Sistem

### Gambaran Umum

```
┌─────────────────────────────────────────────────────────────┐
│              SATU PROYEK LARAVEL — basamo-nch              │
│                                                             │
│  ┌───────────────┐ ┌───────────────┐ ┌──────────────────┐  │
│  │  LAPISAN 1    │ │  LAPISAN 2    │ │   LAPISAN 3      │  │
│  │ Frontend      │ │ Portal Warga  │ │  Panel Admin     │  │
│  │ Publik        │ │ LMS           │ │  /admin          │  │
│  │               │ │               │ │                  │  │
│  │ Blade+Tailwind│ │ Blade+Livewire│ │  Filament v5     │  │
│  │ tanpa login   │ │ auth Laravel  │ │  auth Filament   │  │
│  │ SEO, cepat    │ │ ramah HP      │ │  back-office     │  │
│  │               │ │               │ │                  │  │
│  │ Home+katalog  │ │ Warga (LMS)   │ │ Super Admin      │  │
│  │ UMKM publik   │ │ UMKM owner    │ │ Admin Nagari     │  │
│  └───────┬───────┘ └───────┬───────┘ └────────┬─────────┘  │
│          │                 │                  │            │
│        ┌─▼─────────────────▼──────────────────▼─┐          │
│        │         LARAVEL CORE                   │          │
│        │  Models · Services · Policies          │          │
│        └────────────────┬───────────────────────┘          │
│                         │                                   │
│            ┌────────────▼────────────┐                     │
│            │       MySQL             │                     │
│            │  Multi-tenant nagari_id │                     │
│            └─────────────────────────┘                     │
└─────────────────────────────────────────────────────────────┘

External Services:
  - YouTube / Google Drive  →  Embed konten modul LMS
  - WhatsApp                →  Kontak transaksi UMKM
  - IoT API (Fase 2)        →  Data sensor bencana
  - Cloudflare R2 (produksi)→  File storage
```

### Tiga Lapisan Presentasi

| | Lapisan 1 — Publik | Lapisan 2 — Portal Warga | Lapisan 3 — Admin |
|---|---|---|---|
| Teknologi | Blade + Tailwind | Blade + Livewire/Alpine | Filament v5 |
| Pengguna | Siapa saja (tanpa login) | Warga, Pemilik UMKM | Super Admin, Admin Nagari |
| Auth | — | Auth Laravel + middleware role | Filament + canAccessPanel |
| Fokus | Katalog UMKM, profil nagari, berita | Belajar, kuis, leaderboard, input produk | CRUD modul/SDGs, verifikasi, dashboard |
| Alasan | SEO & kecepatan untuk halaman utama | UX ramah HP, scalable untuk massa | Back-office, Filament hemat waktu |

### Multi-Tenancy

Setiap nagari memiliki namespace data sendiri dalam satu database. Semua tabel utama memiliki kolom `nagari_id` sebagai foreign key. Di panel admin, scope otomatis via Filament Tenant. Di portal & frontend publik, scope manual via query `nagari_id`. Super Admin melihat semua data; Admin Nagari hanya nagarinya sendiri.

---

## 5. Empat Pilar Fungsional

### 5.1 LMS — Portal Belajar

**Tujuan:** Menjadi pusat pembelajaran literasi digital bagi masyarakat nagari, dapat diakses dari HP maupun komputer.

#### Fitur Utama

**Manajemen Modul (Admin)**
- Buat modul global (tersedia untuk semua nagari) atau modul lokal (khusus nagari tertentu)
- Setiap modul terdiri dari: judul, deskripsi, thumbnail, urutan prerequisite, dan status (draft/published)
- Konten per modul: halaman teks & PDF, embed video YouTube atau Google Drive
- Setting prerequisite: modul X harus diselesaikan sebelum modul Y bisa diakses

**Pengalaman Belajar (Warga)**
- Tampilan kartu modul dengan status (terkunci / tersedia / selesai)
- Progress bar per modul
- Baca materi teks, lihat PDF inline, tonton video embed
- Kuis setelah setiap modul: pilihan ganda (otomatis dinilai) dan essay (dinilai Admin Nagari)
- Notifikasi in-app saat modul baru tersedia atau hasil kuis sudah dinilai

**Sistem Poin & Leaderboard**

| Aktivitas | Poin |
|---|---|
| Selesaikan satu halaman materi | 10 poin |
| Lulus kuis pilihan ganda | 20 poin |
| Lulus kuis essay (dinilai admin) | 30 poin |
| Selesaikan satu modul penuh | Bonus 50 poin |

- Leaderboard ditampilkan per nagari (bukan lintas nagari)
- Peringkat diperbarui real-time setiap ada aktivitas belajar
- Tampilan: nama warga, avatar inisial, total poin, jumlah modul selesai

**Forum Diskusi**
- Setiap modul memiliki thread diskusi sendiri
- Warga bisa bertanya dan menjawab
- Admin Nagari berperan sebagai moderator

#### Alur Belajar Warga

```
Login Portal  →  Lihat daftar modul  →  Pilih modul tersedia
     ↓
Baca materi (teks/PDF/video)  →  Selesaikan semua halaman
     ↓
Kerjakan kuis  →  Pilihan ganda: nilai otomatis
                →  Essay: tunggu penilaian admin
     ↓
Poin bertambah  →  Modul berikutnya terbuka  →  Leaderboard update
```

---

### 5.2 SDGs Desa

**Tujuan:** Mendokumentasikan, mengukur, dan memvisualisasikan pencapaian 18 poin SDGs Desa di setiap nagari.

#### 18 Poin SDGs Desa

```
 1. Desa Tanpa Kemiskinan       10. Desa Tanpa Kesenjangan
 2. Desa Tanpa Kelaparan        11. Kawasan Permukiman Desa
 3. Desa Sehat & Sejahtera      12. Konsumsi & Produksi Desa
 4. Pendidikan Desa Berkualitas 13. Tanggap Perubahan Iklim
 5. Keterlibatan Perempuan Desa 14. Ekosistem Laut Desa
 6. Desa Layak Air Bersih       15. Ekosistem Daratan Desa
 7. Desa Berenergi Bersih       16. Desa Damai & Berkeadilan
 8. Pertumbuhan Ekonomi Desa    17. Kemitraan untuk Desa
 9. Infrastruktur & Inovasi     18. Kelembagaan Desa Dinamis
```

#### Fitur Utama

**Input Kegiatan (Admin Nagari)**
- Pilih poin SDGs (1–18)
- Isi form kegiatan: judul kegiatan, deskripsi, pelaksana, tanggal, jumlah peserta
- Unggah dokumen bukti: PDF, foto (JPG/PNG), atau dokumen lainnya
- Edit dan hapus kegiatan yang sudah diinput

**Sistem Skoring**
- Skor per poin SDGs = **jumlah kegiatan** yang tercatat pada poin tersebut
- Tidak ada bobot — setiap kegiatan bernilai sama (1 kegiatan = 1 poin kontribusi)
- Skor ditampilkan sebagai angka di setiap segmen lingkaran SDGs

**Visualisasi Dashboard**
- Diagram lingkaran 18 segmen dengan ikon resmi SDGs per poin
- Setiap segmen berwarna sesuai tema SDGs internasional
- Klik segmen → tampil daftar kegiatan + dokumen bukti
- Perbandingan skor antar nagari (hanya Super Admin)

#### Alur Input SDGs

```
Admin Nagari login  →  Menu SDGs  →  Pilih poin SDGs (1–18)
     ↓
Isi form kegiatan  →  Upload dokumen bukti  →  Simpan
     ↓
Skor poin SDGs bertambah  →  Dashboard nagari terupdate
     →  Super Admin dapat melihat perubahan real-time
```

---

### 5.3 Sistem Manajemen UMKM

**Tujuan:** Menjadi repositori dan media promosi digital UMKM nagari, sekaligus alat ukur adopsi teknologi digital oleh pelaku usaha lokal.

#### Fitur Utama

**Input Produk (Pemilik UMKM)**
- Login dengan akun dari Admin Nagari
- Isi profil usaha: nama usaha, kategori, deskripsi singkat, alamat, kontak WhatsApp
- Upload foto produk (maks. 5 foto per produk)
- Tambah produk: nama produk, deskripsi, harga (opsional), foto
- Edit dan perbarui informasi kapan saja

**Proses Verifikasi**
```
Pemilik UMKM input produk  →  Status: "Menunggu Verifikasi"
     ↓
Admin Nagari menerima notifikasi  →  Review produk
     ↓
Disetujui  →  Produk tampil di katalog publik
Ditolak    →  Notifikasi ke pemilik UMKM + alasan penolakan
```

**Katalog Publik (Tanpa Login)**
- Halaman `/umkm` dapat diakses siapa saja tanpa perlu login
- Filter berdasarkan nagari dan kategori usaha
- Tampilan kartu produk: foto, nama, deskripsi singkat, tombol "Hubungi via WhatsApp"
- Tidak ada fitur transaksi dalam sistem — semua transaksi via WhatsApp

**Analitik UMKM (Admin & Super Admin)**
- Total UMKM terdaftar per nagari
- Jumlah produk aktif
- Kategori usaha terbanyak
- Indeks adopsi digital: persentase UMKM yang sudah menggunakan platform vs. total UMKM di nagari (data total UMKM diisi manual oleh Admin Nagari)
- Ekspor data UMKM ke Excel/CSV

---

### 5.4 Sensor IoT Bencana

**Tujuan:** Menampilkan data kondisi lingkungan dan peringatan dini bencana secara real-time di dashboard.

#### Status Pengembangan

> **Fase 1 (MVP):** Simulasi data sensor dengan nilai dummy yang berubah secara acak untuk keperluan demo dan pengembangan tampilan dashboard.
>
> **Fase 2:** Integrasi API sensor fisik yang sudah terpasang di lapangan. Protokol komunikasi ditentukan saat sensor fisik tersedia.

#### Parameter Sensor (Rencana)

| Parameter | Satuan | Ambang Waspada | Ambang Bahaya |
|---|---|---|---|
| Suhu udara | °C | > 35°C | > 40°C |
| Kelembaban | % | < 20% | < 10% |
| Ketinggian air | cm | > 50 cm | > 100 cm |
| Kecepatan angin | km/h | > 40 km/h | > 70 km/h |
| Curah hujan | mm/jam | > 20 mm | > 50 mm |

#### Tampilan Dashboard

```
┌────────────────────────────────────────┐
│  PANEL SENSOR IOT — Nagari X           │
│                                        │
│  Suhu    Kelembaban  Air     Angin      │
│  [ 32°C ] [ 65% ]  [12cm] [15km/h]    │
│  ● Normal  ● Normal ● Normal ● Normal  │
│                                        │
│  Status Keseluruhan: ● AMAN            │
│  Update terakhir: 2 menit yang lalu    │
└────────────────────────────────────────┘
```

- Indikator warna: Hijau (normal) / Kuning (waspada) / Merah (bahaya)
- Riwayat data dalam grafik garis (24 jam terakhir)
- Notifikasi in-app otomatis saat status berubah ke waspada atau bahaya

---

## 6. Dashboard Analitik

Dashboard adalah jantung platform — tempat seluruh data dari 4 pilar divisualisasikan secara terpadu.

### Dashboard Super Admin (Lintas Nagari)

```
┌─────────────────────────────────────────────────────────────────┐
│  SMART LEARNING CENTER BASAMO NCH — Super Admin Dashboard       │
├──────────────┬──────────────┬──────────────┬────────────────────┤
│ Total Nagari │ Total Warga  │ Total UMKM   │ Sensor Aktif       │
│     3        │    247       │     89       │    12 / 15         │
├──────────────┴──────────────┴──────────────┴────────────────────┤
│                                                                  │
│  KEMAJUAN LMS                    SDGs DESA                      │
│  [Grafik bar per nagari]         [Diagram lingkaran 18 poin]    │
│  Nagari A: 78% aktif             Rata-rata skor lintas nagari   │
│  Nagari B: 61% aktif                                            │
│  Nagari C: 45% aktif                                            │
├──────────────────────────────────┬──────────────────────────────┤
│  UMKM & ADOPSI DIGITAL           │  SENSOR IOT                  │
│  [Grafik donut kategori usaha]   │  [Panel status per nagari]   │
│  Indeks adopsi: 67%              │  Semua nagari: AMAN          │
├──────────────────────────────────┴──────────────────────────────┤
│  BERITA & AKTIVITAS TERBARU                                     │
│  [Feed aktivitas: modul baru, kegiatan SDGs, UMKM baru, dll]   │
└─────────────────────────────────────────────────────────────────┘
```

### Dashboard Admin Nagari (Per Nagari)

Tampilan serupa namun dibatasi hanya untuk data nagarinya sendiri, ditambah:
- Daftar warga yang perlu penilaian essay
- Produk UMKM yang menunggu verifikasi
- Ringkasan aktivitas nagari hari ini

### Komponen Dashboard

| Komponen | Visualisasi | Library |
|---|---|---|
| Kemajuan LMS per nagari | Bar chart | ApexCharts |
| SDGs 18 poin | Radial/donut chart | ApexCharts |
| Tren aktivitas belajar | Line chart (30 hari) | ApexCharts |
| Sebaran kategori UMKM | Donut chart | ApexCharts |
| Indeks adopsi digital | Gauge chart | ApexCharts |
| Status sensor IoT | Card dengan indikator warna | Filament Widget |
| Leaderboard warga | Tabel ranking | Filament Table |
| Feed berita & aktivitas | Timeline list | Filament Widget |
| Statistik ringkasan | Metric cards (angka besar) | Filament Stats |

---

## 7. Tech Stack & Keputusan Teknologi

### Stack Utama

| Layer | Teknologi | Versi | Keterangan |
|---|---|---|---|
| Backend | Laravel | terbaru | Framework utama PHP |
| Frontend | Blade + Tailwind CSS | terbaru | Templating & styling |
| Reaktivitas UI | Livewire | terbaru | Server-driven UI, dependensi Filament |
| JS Ringan | Alpine.js | terbaru | Interaksi UI, sudah bundled di Filament |
| Admin Panel | Filament | terbaru | Multi-panel: admin + portal warga |
| RBAC | Filament Shield | latest | Kelola permission 4 role |
| Auth | Filament bawaan (tanpa Jetstream) | built-in | Satu model User, pisah panel via canAccessPanel() |
| Database | MySQL | terbaru | Database utama, multi-tenant |
| Charts | ApexCharts | latest | Via paket leandrocfe/filament-apex-charts |
| Editor Modul | RichEditor bawaan Filament | built-in | Tiptap tidak support v5; video via field URL terpisah |
| Icon | Heroicons | built-in | Satu-satunya icon set, via Blade Icons |
| Notifikasi | Filament Notifications | built-in | Toast + database notification, tidak pakai library luar |
| File & Media | Spatie Media Library | terbaru | Upload foto, dokumen, thumbnail |
| Kompresi Gambar | Intervention Image | terbaru | Resize & optimasi foto produk UMKM |
| Ekspor PDF | Laravel DomPDF | latest | Laporan SDGs & LMS |
| Ekspor Excel | Maatwebsite Excel | latest | Data UMKM & warga |
| PDF Viewer | Filament PDF Viewer | latest | Tampil dokumen bukti SDGs inline |
| Audit Trail | Spatie Activity Log | latest | Catat semua aktivitas admin & user |
| Backup | Spatie Backup | latest | Backup database & file otomatis |
| Slug | Spatie Sluggable | latest | Auto-generate URL modul & produk |
| Version Control | Git + GitHub | — | Repository proyek |
| File Storage MVP | Laravel Local Disk | — | Development & demo |
| File Storage Prod | Cloudflare R2 | — | Sebelum go-live publik (gratis s/d 10GB) |
| Hosting (rencana) | VPS IDCloudHost | — | 2 vCPU / 4GB RAM / 50GB SSD |
| IoT Real-time | Laravel Reverb + Echo | — | Fase 2 — WebSocket sensor bencana |

### Aturan Konsistensi UI (Wajib Dipatuhi Tim)

> Kesepakatan ini berlaku untuk seluruh tim sepanjang development. Tidak boleh ada library UI tambahan yang masuk tanpa diskusi.

| Aspek | Keputusan | Alasan |
|---|---|---|
| Icon | **Heroicons saja** — jangan campur dengan Phosphor, Material, dll | Sudah built-in Filament, konsistensi visual terjaga |
| Toast / Alert | **Filament Notifications** — tidak pakai Toastr, SweetAlert, atau library lain | Menghindari dua sistem notifikasi yang bertabrakan |
| Editor teks | **RichEditor bawaan Filament** — Tiptap tidak support Filament v5 | Cukup untuk konten modul; video via field URL terpisah |
| Chart | **ApexCharts** via Filament widget — tidak pakai Chart.js atau D3.js langsung | Satu library chart, konsisten di semua dashboard |
| CSS | **Tailwind utility classes** — tidak pakai Bootstrap atau framework CSS lain | Tailwind sudah jadi fondasi Filament |
| JS | **Alpine.js** untuk interaksi kecil — tidak pakai jQuery | Sudah bundled di Filament, ringan |

### Dependency Lengkap — Composer (Fase 1 MVP)

```bash
# Core Filament (TANPA Jetstream)
composer require filament/filament -W
composer require livewire/livewire

# RBAC & Permission
composer require bezhansalleh/filament-shield
composer require spatie/laravel-permission

# File & Media
composer require spatie/laravel-medialibrary
composer require filament/spatie-laravel-media-library-plugin
composer require intervention/image

# Grafik Dashboard
composer require leandrocfe/filament-apex-charts

# Editor Modul LMS: pakai RichEditor BAWAAN Filament — tidak perlu install

# Ekspor Laporan
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel

# Utilitas
composer require spatie/laravel-sluggable

# Disarankan — tambah sebelum go-live
composer require spatie/laravel-activitylog
composer require spatie/laravel-backup
composer require joaopaulolndev/filament-pdf-viewer
```

### Dependency Lengkap — Composer (Dev Only)

```bash
composer require laravel/telescope --dev
composer require laravel/pint --dev
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
```

### Dependency Lengkap — NPM

```bash
# Tailwind CSS & tooling
npm install -D tailwindcss postcss autoprefixer
npm install -D @tailwindcss/forms @tailwindcss/typography

# ApexCharts
npm install apexcharts

# Alpine.js (untuk portal warga)
npm install alpinejs
```

### Dependency Fase 2 — IoT Real-time

```bash
# WebSocket server
composer require laravel/reverb

# Client-side real-time
npm install laravel-echo pusher-js
```

### Artisan Setup Commands (Urutan Setelah Install)

```bash
# 1. Install Filament + buat panel admin (satu-satunya panel)
php artisan filament:install --panels
# Saat ditanya ID panel → isi: admin

# 2. Setup Filament Shield (RBAC) — TANPA Jetstream
php artisan shield:setup
php artisan shield:install admin

# 3. Publish & migrate semua
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate

# 4. Buat super admin pertama + generate permission
php artisan make:filament-user
php artisan shield:generate --all

# Catatan: Portal warga & frontend publik dibangun dengan controller + Blade,
# BUKAN panel Filament. Tidak ada make:filament-panel portal.
```

### Struktur Panel Admin (Filament — Lapisan 3)

```php
// Admin Panel → /admin (satu-satunya panel Filament)
// app/Providers/Filament/AdminPanelProvider.php
->id('admin')
->path('admin')
->authGuard('web')
->tenant(Nagari::class)  // multi-tenancy per nagari
```

Akses panel admin dibatasi di model `User` via `canAccessPanel()`:

```php
public function canAccessPanel(\Filament\Panel $panel): bool
{
    // Hanya admin yang boleh masuk panel Filament
    return in_array($this->role, ['super_admin', 'nagari_admin']);
}
```

### Lapisan 2 — Portal Warga (custom, BUKAN Filament)

Portal warga dibangun dengan controller Laravel + Blade + Livewire, bukan panel Filament. Akses dibatasi via middleware:

```php
// routes/web.php
Route::middleware(['auth', 'role:warga,umkm_owner'])
    ->prefix('belajar')
    ->group(function () {
        Route::get('/', [Portal\DashboardController::class, 'index']);
        Route::get('/modul/{module:slug}', [Portal\ModuleController::class, 'show']);
        // ...
    });
```

### Lapisan 1 — Frontend Publik (custom, tanpa login)

```php
// routes/web.php
Route::get('/', [Public\HomeController::class, 'index']);        // home + katalog UMKM
Route::get('/umkm/{product:slug}', [Public\UmkmCatalogController::class, 'show']);
Route::get('/nagari/{nagari:slug}', [Public\NagariProfileController::class, 'show']);
```

---

## 8. Skema Role & Permission

### Hierarki Role

```
super_admin
    └── nagari_admin
            ├── warga
            └── umkm_owner
```

### Matrix Permission

| Permission | Super Admin | Admin Nagari | Warga | Pemilik UMKM |
|---|:---:|:---:|:---:|:---:|
| Tambah / hapus nagari | ✓ | — | — | — |
| Buat modul global | ✓ | — | — | — |
| Buat modul lokal nagari | ✓ | ✓ | — | — |
| Daftarkan warga | ✓ | ✓ | — | — |
| Beri akun UMKM | ✓ | ✓ | — | — |
| Input kegiatan SDGs | ✓ | ✓ | — | — |
| Input produk UMKM | — | — | — | ✓ |
| Verifikasi produk UMKM | ✓ | ✓ | — | — |
| Akses modul belajar | — | — | ✓ | — |
| Kerjakan kuis | — | — | ✓ | — |
| Nilai kuis essay | ✓ | ✓ | — | — |
| Lihat leaderboard | — | — | ✓ | — |
| Dashboard lintas nagari | ✓ | — | — | — |
| Dashboard nagari sendiri | ✓ | ✓ | — | — |
| Ekspor laporan PDF | ✓ | ✓ | — | — |
| Ekspor Excel/CSV | ✓ | ✓ | — | — |
| Kelola sensor IoT | ✓ | — | — | — |

---

## 9. Alur Sistem Utama

### Alur 1 — Onboarding Nagari Baru

```
Super Admin login ke /admin
     ↓
Menu Nagari → Tambah Nagari Baru
     ↓
Isi data: nama nagari, kode, lokasi, kontak
     ↓
Sistem membuat namespace data nagari di database
     ↓
Buat akun Admin Nagari → kirim kredensial
     ↓
Admin Nagari aktif → mulai daftarkan warga & UMKM
```

### Alur 2 — Warga Mulai Belajar

```
Admin Nagari daftarkan warga → warga terima akun
     ↓
Warga login ke /portal
     ↓
Lihat daftar modul → modul pertama tersedia, sisanya terkunci
     ↓
Buka modul → baca teks / lihat PDF / tonton video
     ↓
Selesaikan semua halaman → kerjakan kuis
     ↓
Pilihan ganda: nilai otomatis langsung
Essay: masuk antrian penilaian admin
     ↓
Poin bertambah → leaderboard update → modul berikutnya terbuka
```

### Alur 3 — Input & Verifikasi Produk UMKM

```
Admin Nagari buat akun Pemilik UMKM → kirim kredensial
     ↓
Pemilik UMKM login → isi profil usaha & produk
     ↓
Status produk: "Menunggu Verifikasi"
     ↓
Admin Nagari terima notifikasi → review produk
     ↓
Setujui → produk tampil di katalog publik /umkm
Tolak   → notifikasi + alasan ke Pemilik UMKM
```

### Alur 4 — Input Kegiatan SDGs

```
Admin Nagari login → menu SDGs
     ↓
Pilih salah satu dari 18 poin SDGs
     ↓
Tambah kegiatan: judul, deskripsi, pelaksana, tanggal, peserta
     ↓
Upload dokumen bukti (PDF / foto)
     ↓
Simpan → skor poin SDGs bertambah 1
     ↓
Dashboard SDGs nagari terupdate → Super Admin dapat melihat
```

---

## 10. Skema Database

### Tabel Utama

```sql
-- Nagari (multi-tenant root)
nagaris
  id, nama, kode, provinsi, kabupaten, kecamatan,
  koordinat_lat, koordinat_lng, kontak, status, created_at

-- Users (semua role)
users
  id, nagari_id (nullable untuk super_admin), name, email,
  password, role (enum: super_admin|nagari_admin|warga|umkm_owner),
  avatar, status, created_at

-- LMS: Modul
modules
  id, nagari_id (null = global), title, description, thumbnail,
  order, prerequisite_module_id, status (draft|published), created_at

-- LMS: Halaman Materi
module_pages
  id, module_id, title, type (text|pdf|video), content,
  video_url, file_path, order, created_at

-- LMS: Progress Warga
user_module_progress
  id, user_id, module_id, status (not_started|in_progress|completed),
  completed_at, total_points, created_at

-- LMS: Kuis
quizzes
  id, module_id, title, passing_score, created_at

quiz_questions
  id, quiz_id, question, type (multiple_choice|essay), order

quiz_options
  id, question_id, option_text, is_correct

quiz_attempts
  id, user_id, quiz_id, score, status (pending|passed|failed),
  submitted_at, reviewed_at, reviewed_by

quiz_answers
  id, attempt_id, question_id, answer_text, is_correct,
  score_given, feedback

-- LMS: Forum Diskusi
discussions
  id, module_id, user_id, parent_id (nullable), body,
  is_pinned, created_at

-- SDGs
sdgs_activities
  id, nagari_id, sdgs_point (1-18), title, description,
  pelaksana, tanggal, jumlah_peserta, created_by, created_at

sdgs_documents
  id, activity_id, file_name, file_path, file_type, created_at

-- UMKM
umkm_profiles
  id, nagari_id, user_id, nama_usaha, kategori, deskripsi,
  alamat, whatsapp, status (active|inactive), created_at

umkm_products
  id, umkm_profile_id, nama_produk, deskripsi, harga,
  status (pending|approved|rejected), rejection_reason,
  approved_by, approved_at, view_count, created_at

umkm_product_photos
  id, product_id, file_path, order, created_at

-- IoT Sensor
iot_sensors
  id, nagari_id, nama, tipe, lokasi, status (active|inactive)

iot_readings
  id, sensor_id, parameter, nilai, satuan, status (normal|waspada|bahaya),
  recorded_at

-- Notifikasi
notifications
  id, user_id, type, title, body, data (JSON),
  read_at, created_at

-- Berita / Aktivitas Feed
news_feeds
  id, nagari_id (null = global), title, body, type
  (berita|aktivitas|pengumuman), created_by, created_at
```

### Catatan Multi-Tenancy

Semua query di Admin Nagari secara otomatis di-scope dengan `nagari_id` milik admin yang sedang login. Ini diimplementasikan via **Filament Tenancy** sehingga tidak perlu filter manual di setiap query.

---

## 11. Rencana Pengembangan (Roadmap)

### Fase 1 — MVP (Bulan 1–3)

**Target:** Sistem berjalan dengan data dummy, siap demo ke stakeholder.

- Setup proyek Laravel + Filament multi-panel
- Implementasi RBAC 4 role (Filament Shield)
- Manajemen nagari oleh Super Admin
- LMS: modul, materi (teks, PDF, video embed), kuis pilihan ganda & essay
- LMS: progress tracker, sistem poin, leaderboard per nagari
- SDGs: input 18 poin, upload dokumen bukti, visualisasi lingkaran
- UMKM: profil usaha, produk, alur verifikasi, katalog publik
- Dashboard analitik dasar (4 pilar + metric cards)
- Data dummy: 3 nagari, 10 modul, 18 SDGs terisi, 10 UMKM per nagari
- Sensor IoT: simulasi data dummy di dashboard
- Notifikasi in-app dasar
- Ekspor PDF laporan SDGs & LMS
- Ekspor Excel/CSV data UMKM & warga

### Fase 2 — Penguatan (Bulan 4–6)

**Target:** Siap digunakan oleh 3 nagari pilot secara nyata.

- Integrasi sensor IoT fisik via REST API / MQTT
- Migrasi storage ke Cloudflare R2
- Forum diskusi LMS
- Penilaian essay oleh Admin Nagari
- Dashboard perbandingan lintas nagari (grafik radar)
- Feed berita & aktivitas nagari
- Optimasi performa & keamanan
- Pelatihan Admin Nagari ketiga desa pilot
- Deploy ke VPS produksi

### Fase 3 — Skala Nasional (Bulan 7–12)

**Target:** Platform siap dibuka untuk nagari/desa di seluruh Indonesia.

- Self-service onboarding nagari (form pendaftaran + aktivasi manual)
- Sertifikat kelulusan modul untuk warga (PDF otomatis)
- API publik data SDGs (untuk integrasi pihak luar)
- Gamifikasi LMS lanjutan (badge, pencapaian)
- Aplikasi mobile PWA (Progressive Web App)
- Multi-bahasa (persiapan untuk daerah di luar Sumatera Barat)
- Sistem backup otomatis dan monitoring uptime

---

## 12. Data Dummy untuk Demo

### Struktur Data Dummy

```
3 Nagari:
  - Nagari Maju Bersama (Kab. Agam)
  - Nagari Sejahtera Mandiri (Kab. Tanah Datar)
  - Nagari Karya Bersama (Kab. Padang Pariaman)

Per Nagari:
  - 1 Admin Nagari
  - 30 akun Warga
  - 10 akun Pemilik UMKM

Modul LMS:
  - 5 modul global (literasi digital dasar):
      1. Mengenal Smartphone & Internet
      2. Media Sosial yang Bijak
      3. Keamanan Digital & Anti Hoaks
      4. Belanja Online & E-commerce
      5. Pemanfaatan AI untuk Kehidupan Sehari-hari
  - 2 modul lokal per nagari (konten potensi desa)

SDGs:
  - 18 poin terisi dengan 2–4 kegiatan dummy per poin
  - Dokumen bukti: file PDF placeholder

UMKM:
  - 10 profil UMKM per nagari (total 30)
  - Kategori: kuliner, kerajinan, pertanian, jasa
  - Foto produk: placeholder image

Sensor IoT:
  - Nilai simulasi berubah acak setiap 30 detik
  - Sesekali memicu status "waspada" untuk demo alert
```

---

## 13. Risiko & Mitigasi

| Risiko | Dampak | Probabilitas | Mitigasi |
|---|---|---|---|
| Admin nagari kewalahan merangkap semua tugas | Tinggi | Sedang | Buat UI admin sesederhana mungkin, sediakan panduan singkat (tooltip, help text) di setiap form |
| Warga tidak aktif menggunakan LMS | Sedang | Tinggi | Gamifikasi (leaderboard, poin), notifikasi pengingat, pelatihan langsung di nagari |
| Koneksi internet terbatas di nagari | Tinggi | Tinggi | Desain halaman ringan (< 200KB per halaman), hindari autoplay video, konten PDF dapat diunduh offline |
| Data SDGs tidak konsisten antar nagari | Sedang | Sedang | Template input terstandar, panduan pengisian per poin SDGs, validasi form ketat |
| Produk UMKM tidak ter-update | Rendah | Sedang | Reminder notifikasi otomatis jika produk tidak diperbarui > 3 bulan |
| Sensor IoT tidak stabil (Fase 2) | Sedang | Sedang | Buffer/queue data, tampilkan status koneksi sensor, data terakhir valid tetap ditampilkan saat offline |
| Skalabilitas saat nagari bertambah banyak | Tinggi | Rendah (jangka panjang) | Arsitektur multi-tenant dari awal, indeks database per nagari_id, pertimbangkan caching (Redis) di Fase 3 |
| Keamanan data warga nagari | Tinggi | Rendah | HTTPS wajib, Filament auth dengan verifikasi email, rate limiting, backup rutin |

---

## 14. Keputusan yang Masih Terbuka

Dua hal ini tidak memblokir development Fase 1, namun harus diputuskan sebelum masuk Fase 2:

| # | Keputusan | Opsi yang Direkomendasikan | Deadline |
|---|---|---|---|
| 1 | **Hosting & VPS** | IDCloudHost VPS — 2 vCPU / 4GB RAM / 50GB SSD (server Indonesia, latensi baik untuk warga nagari) | Sebelum akhir Fase 1 |
| 2 | **File Storage Produksi** | Cloudflare R2 — gratis s/d 10GB/bulan, tidak ada biaya egress, cocok untuk proyek akademik/pengabdian | Sebelum deploy Fase 2 |

---

## Lampiran — Ringkasan Keputusan Teknis

| # | Aspek | Keputusan |
|---|---|---|
| 1 | Backend | Laravel (terbaru) |
| 2 | Frontend | Blade + Tailwind CSS |
| 3 | Admin Panel | Filament — Multi-panel |
| 4 | Database | MySQL |
| 5 | Auth | Filament bawaan (tanpa Jetstream) + Filament Shield |
| 6 | Charts | ApexCharts |
| 7 | Notifikasi | In-app (Laravel Notifications) |
| 8 | IoT | Simulasi Fase 1, integrasi API Fase 2 |
| 9 | Version Control | Git + GitHub |
| 10 | Tim | Internal kecil |
| 11 | Bahasa | Bahasa Indonesia |
| 12 | Pendaftaran Warga | Oleh Admin Nagari |
| 13 | Akses Portal | Hanya warga nagari terdaftar |
| 14 | Modul LMS | Global + lokal per nagari |
| 15 | Alur Belajar | Berurutan (prerequisite) |
| 16 | Tipe Kuis | Pilihan ganda + essay |
| 17 | Format Konten | Teks, PDF, embed YouTube/GDrive |
| 18 | Leaderboard | Per nagari (bukan lintas nagari) |
| 19 | Input SDGs | Hanya Admin Nagari |
| 20 | Verifikasi UMKM | Wajib disetujui Admin Nagari |
| 21 | Katalog UMKM | Publik tanpa login |
| 22 | Transaksi UMKM | Tidak ada — via WhatsApp |
| 23 | Onboarding Nagari | Manual oleh Super Admin |
| 24 | Ekspor | PDF (SDGs & LMS) + Excel/CSV (UMKM & warga) |
| 25 | Data Awal | Dummy untuk demo |
| 26 | Skala | Pilot 3 nagari → terbuka nasional bertahap |

---

*Dokumen ini merupakan PRD final yang disusun berdasarkan hasil diskusi dan kesepakatan bersama. Seluruh keputusan di atas menjadi acuan pengembangan Smart Learning Center Basamo NCH.*

*Versi 1.0 — Siap untuk Development*
