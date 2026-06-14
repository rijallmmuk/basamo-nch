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

---

## Template untuk keputusan baru

```
### [YYYY-MM] Judul keputusan
**Keputusan**: Apa yang diputuskan.
**Alasan**: Mengapa keputusan ini dibuat.
**Ditolak**: Alternatif yang dipertimbangkan dan alasan penolakannya.
```
