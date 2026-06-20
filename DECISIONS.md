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

### Login admin: username ATAU email
Satu field `login`; deteksi email via `FILTER_VALIDATE_EMAIL`. Custom `App\Filament\Auth\Login` (di `app/Filament/Auth/`, bukan `Pages/`, agar tak ter-discover sebagai page). Kolom `users.username` nullable+unique.

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
XP berbasis pencapaian, **sekali per modul** (idempotent via `xp_logs` UNIQUE(user,source,source_id)): selesai materi modul **+50**, lulus kuis **+100**, partisipasi diskusi (posting pertama) **+20** → maks 170/modul. Akumulatif di `users.total_xp`. Leaderboard **per nagari** (warga aktif), urut total_xp, peringkat kompetisi; dashboard Top 5.
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

### Standar lain
- **Heroicons** satu-satunya icon set (built-in Filament).
- **Filament Notifications** untuk toast admin + database notification (pusat notifikasi in-app portal; notif fan-out modul/kuis `ShouldQueue` + `chunkById`).
- **ApexCharts** (`leandrocfe/filament-apex-charts`) untuk semua chart dashboard (ter-scope nagari).

### Storage: satu knob `MEDIA_DISK` untuk semua unggahan
- **Spatie Media Library** (cover modul, foto produk) + **PDF materi** (`ModulePage.file_path`) semua memakai disk **`MEDIA_DISK`** (`config('media-library.disk_name')`, default `public` = lokal+symlink). `FILESYSTEM_DISK` (default `local`) terpisah untuk disk privat app.
- **Produksi R2**: set `FILESYSTEM_DISK=s3` **dan** `MEDIA_DISK=s3` + kredensial (S3-compatible, gratis ≤10GB, tanpa egress).
**Alasan**: Sebelumnya PDF hard-coded disk `public` di 5 tempat → tak ikut R2. Satu env menyatukan semua unggahan agar migrasi storage = ubah env.
**Catatan**: PDF di disk publik bisa diakses tanpa login bila URL bocor (keputusan MVP; materi edukatif non-sensitif).

---

## Admin (Resources)

### NagariResource — super_admin only + anti-orphan
Hanya super_admin (`NagariPolicy` semua false; super via Gate::before). SoftDeletes + guard: nagari tak bisa dihapus selama punya pengguna/modul (`guardAgainstDependents` + `$action->halt()`); tanpa bulk-delete agar guard per-record selalu jalan.

### UserResource — manajemen pengguna
super_admin: CRUD lintas nagari, semua peran (super_admin → nagari_id null). nagari_admin: hanya **warga** di nagarinya (scope query + record-binding + `UserPolicy`). Password hash (cast) + opsional saat edit + confirmed. Pengaman self: tak bisa hapus/demote/nonaktifkan diri sendiri.
**Ditunda**: impersonate, 2FA, bulk-import.

### Ekspor laporan (rencana, belum dibangun)
DomPDF (`barryvdh/laravel-dompdf`) untuk laporan LMS/SDGs; Maatwebsite Excel untuk data warga/UMKM.

---

## Fitur ditunda / batas lingkup
- **Onboarding nagari** manual oleh super_admin (self-service di Fase 3).
- **Squash migrasi** jadi baseline bersih = langkah pra-deploy **terakhir** (jangan saat masih ada perubahan skema). 42 migrasi terbukti jalan di MySQL+SQLite.
- SDGs & IoT = pilar lain (programmer lain).

```
### [YYYY-MM] Judul keputusan
**Keputusan**: ... · **Alasan**: ... · **Ditolak**: ...
```
