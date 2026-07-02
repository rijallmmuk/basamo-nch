# PROGRESS.md — Basamo NCH

> File ini ditulis UNTUK agent (Claude Code) sesi berikutnya. Ringkas (≤60 baris).
> Detail task → TASKS.md · Keputusan → DECISIONS.md
> Aturan: baca CLAUDE.md + PROGRESS.md + TASKS.md di awal sesi.

---

## Sesi 2026-07-02 (lanjutan 5) — Konsistensi UI admin (keputusan user via diskusi)

Branch `feat/ref-wilayah-sumbar`. Suite **311 (309 lulus, 2 skip)**. Pint bersih.

User menagih konsistensi + menjawab 4 pertanyaan (AskUserQuestion). KEPUTUSAN USER:
- **Quiz = persis Modul**: baris DAPAT diklik → Edit + aksi dalam menu ⋮ (dulu non-klik
  + aksi inline kuning). Desa/Warga tetap non-klik + ⋮.
- **Aksi Ubah inline = KUNING semua** (Wilayah, Data Master, Kategori UMKM); aksi di
  dalam ⋮ tetap netral.
- **Kolom `kode` pekerjaan DIBUANG dari tampilan** (form+tabel). Kolom DB tetap (NOT
  NULL+unik) → `Pekerjaan::booted creating` auto-isi nomor Dukcapil lanjutan.
- **Kolom "Dipakai" data master DIBUANG** — pesan penolakan hapus tetap menyebut jumlah.
- **Field `urutan` DIBUANG dari form** (Data Master + Kategori UMKM): entri baru
  auto-append (`IsLookup::bootIsLookup` / hook UmkmCategory); ubah urutan = seret baris
  (Kategori UMKM kini juga reorderable). Kolom urutan → toggle tersembunyi.
- **Urutan jenis_desa/jenis_sub_unit dinormalkan 1-based** (dulu seed 0-based, beda dgn
  agama/pekerjaan): migrasi diperbaiki + data live di-increment.
- **Tabel Diskusi admin = PERTANYAAN saja, permanen** (`whereNull('parent_id')` di
  resource; kolom Tipe + filter Tipe dihapus). KONSEKUENSI SADAR (user sudah
  diperingatkan & memilih ini): balasan warga TAK bisa dimoderasi dari panel admin.

---

## Sesi 2026-07-02 (lanjutan 4) — CRUD Data Master + audit robustness super admin

Branch `feat/ref-wilayah-sumbar`. Suite **310 (308 lulus, 2 skip)**. Pint bersih.

User minta CRUD semua tabel data master + pastikan robust. Dibangun grup navigasi
**"Data Master"** (super admin only) berisi 5 resource: **Agama, Status Perkawinan,
Pekerjaan (＋kolom kode), Penyebutan Desa (jenis_desa), Sebutan Sub-Unit (jenis_sub_unit)**.
- **Arsitektur**: basis `App\Filament\Resources\DataMaster\LookupResource` (abstract) —
  tabel referensi berbentuk sama (nama unik + urutan + aktif) → form/tabel/guard sekali
  tulis; 5 subclass tipis + 5 halaman `ManageRecords` (modal, pola persis Kategori UMKM:
  No., aksi inline, tanpa klik-baris) + **5 Policy** (all-false, super via Gate::before).
- **Guard hapus-terpakai** (kolom "Dipakai" + tolak delete): FK `penduduk.*` & `desas.
  jenis_sub_unit_id` = SET NULL (hapus terpakai = data hilang diam-diam), `desas.
  jenis_desa_id` = RESTRICT (= error 500). Jalur pensiun nilai = toggle **Nonaktif**.
  Guard yang sama ditambahkan ke **Kategori UMKM** (dulu hapus terpakai lolos diam-diam).
- **Trait `IsLookup::options(?includeId)`** di 5 model: opsi dropdown = baris aktif
  + nilai terpilih (walau sudah nonaktif) → record lama tetap bisa dibuka/disimpan.
  Dipakai seragam di **UserForm** (agama/status kawin/pekerjaan — dulu filter aktif tanpa
  penyertaan nilai lama = record bisa gagal save), **DesaForm** & **PengaturanDesa**
  (jenis_desa/jenis_sub_unit — dulu TANPA filter aktif sama sekali; inkonsisten).
- Tabel data master **reorderable** (seret = ubah `urutan`) + `defaultSort('urutan')`.
- Panel `navigationGroups` + 'Data Master' (antara UMKM & Sistem); tak tampil bagi
  admin desa (canAccess super-only).
- **RefWilayah SENGAJA tanpa CRUD** (keputusan): data resmi Kemendagri ±1464 baris,
  jadi induk FK `desas.wilayah_kode` — diubah lewat seeder/`ImportWilayahBoundaries`,
  bukan tangan. ActivityLog diskim ulang: super-only, read-only, aman.
- Test `DataMasterResourceTest` (15): render per halaman (super), 403 (admin desa),
  create/edit/unik, guard hapus terpakai (penduduk & desa), perilaku `options()`.

---

## Sesi 2026-07-02 (lanjutan 3) — Audit kesiapan produksi (deploy-readiness)

Branch `feat/ref-wilayah-sumbar`. Suite **295 (293 lulus, 2 skip)**. Pint bersih.

Pertanyaan user: "kalau mentok di sini, siap deploy?" Audit backend deploy-readiness.
**Verdict: SIAP deploy MVP** dgn catatan di bawah. Fix yang diterapkan:
- **CoreSeeder produksi**: sandi super admin TIDAK lagi hardcoded — di produksi dibangkitkan
  acak 24 char (dicetak sekali di console) + `must_change_password` (masuk alur modal OTP).
  Lokal/demo tetap `password`. `CoreSeederTest` (2).
- **DemoSeeder diberi guard produksi** (return + error message) — cegah data dummy masuk
  server nyata via kebiasaan `migrate:fresh --seed`.
- **README deploy dilengkapi**: langkah pemasangan sekali (composer→env→key→migrate→
  CoreSeeder→storage:link→build→optimize+filament:optimize), langkah tiap deploy,
  **cron scheduler WAJIB** (retensi notifikasi+activity log), HTTPS+SESSION_SECURE_COOKIE,
  trust proxies bila di belakang proxy/Cloudflare, batas upload PHP, daftar "belum
  disiapkan" (backup DB terjadwal, monitoring eksternal).
- **.env.example**: hint `SESSION_SECURE_COOKIE=true` utk produksi.
- **Diverifikasi OK**: `config:cache`+`route:cache` jalan (tak ada env() di luar config,
  tak ada dd/dump); `/up` health route; login→logout tiap peran ter-test (UnifiedLoginTest);
  boundary endpoint ter-scope; schedule `onOneServer`; queue sudah terdokumentasi.
- **Blocker tersisa sebelum go-live (bukan kode)**: backup DB terjadwal belum ada;
  cek browser manual (daftar NEXT); server HTTPS + cron + supervisor sesuai README.

---

## Sesi 2026-07-02 (lanjutan 2) — Audit Desa/Warga/login-pertama/profil semua peran

Branch `feat/ref-wilayah-sumbar`. Suite **293 (291 lulus, 2 skip)**. Pint bersih.

Audit kedalaman sama dgn audit LMS: CRUD Desa, CRUD Warga (incl. impor), login pertama
(warga & admin), profil semua peran. Verdict: **solid** — 5 perbaikan diterapkan:
- **Tabel Warga: aksi Pulihkan & Hapus Permanen DITAMBAHKAN** (dulu absen — warga terarsip
  TERJEBAK: tak bisa dipulihkan lewat UI padahal TrashedFilter ada, policy mendukung, dan
  NIK unik membuat restore satu-satunya jalan pakai-ulang NIK). Modal force-delete
  menjelaskan data ikut terhapus; identitas penduduk tetap.
- **Hook `forceDeleting` di `User` & `UmkmProfile`**: hapus permanen akun → lapak & produk
  dihapus via Eloquent (cascade DB melewati event model → foto produk yatim di storage).
  Sekalian menutup bug laten ForceDeleteAction Profil UMKM yang sudah ada. Test media bersih.
- **Force-delete Desa kini memanggil `forceDeleteAdmin`** (dulu DEAD CODE, tak pernah
  di-wire) — dipanggil di `before` SETELAH guard, karena FK `users.desa_id` SET NULL
  memutus relasi `desaAdmin()` begitu desa lenyap → dulu akun admin tertinggal yatim.
- **Pesan validasi NIK bentrok** kini mengarahkan admin: pulihkan akun terarsip lewat
  filter "Dihapus" (bukan bingung "sudah dipakai" tanpa solusi).
- **Diverifikasi solid tanpa perubahan**: login gabungan (throttle identitas+IP, deteksi
  NIK/email/username, guard nonaktif/peran asing, regenerate session, redirect per peran);
  login pertama (EnsurePortalUser & EnsureAdminPasswordChanged saling-lengkap; hook
  `User::saving` bersihkan OTP; jalur OTP warga=admin); CRUD Desa (create/edit transaksional
  + syncAdmin, guard anti-orphan 2 mode); CRUD Warga (provisioning transaksional, penduduk
  reuse-by-NIK, impor queue tervalidasi/baris); profil warga+admin (current_password,
  normalisasi email/HP — kolasi CI menutup celah kapitalisasi email).
- **Dicatat, sengaja TAK diubah**: `users.nik`/`email` unik kolom-tunggal (termasuk
  terarsip) = by design benar (pakai-ulang → restore, hindari duplikat saat pulihkan);
  `Penduduk::firstOrNew` tak lihat penduduk terarsip (laten murni — tak ada jalur hapus
  penduduk). **CEK BROWSER**: ganti sandi sendiri via modal Profil admin — kemungkinan
  sesi batal (AuthenticateSession, kasus sama dgn ForcePasswordChange) → verifikasi manual.

---

## Sesi 2026-07-02 (lanjutan) — Audit akhir LMS lintas peran sebelum lanjut UMKM

Branch `feat/ref-wilayah-sumbar`. Suite **290 (288 lulus, 2 skip)**. Pint bersih.

Audit menyeluruh migrasi/model/service/controller/policy/observer/views LMS (super admin, admin desa,
warga). Verdict: **solid** — 4 perbaikan kecil diterapkan, sisanya dicatat sadar-tak-diubah:
- **Notif "Kuis baru" tak lagi prematur**: dulu terkirim saat baris kuis dibuat (soal belum ada →
  portal menolak, warga bingung). Kini saat **SOAL PERTAMA** dibuat: `QuizObserver` DIHAPUS →
  `QuizQuestionObserver::created` (syarat modul published; kuis terarsip otomatis lolos krn relasi
  quiz null). +3 test N1 di `LmsAuthoringHardeningTest`.
- **Kode mati dihapus**: `ModuleObserver::deleting` (cleanup berkas force-delete) redundan — event
  `forceDeleting` (hook di `Module::booted`) berjalan LEBIH DULU dan sudah menghapus halaman+berkas
  via `ModulePage::deleted`. Test M1 tetap menjamin perilaku.
- **Avatar diskusi kini berfoto** (index+thread+balasan; dulu inisial saja — tak konsisten dgn
  header/peringkat/profil): eager-load `user.media` + prop `:src` di `x-portal.avatar`.
- **Riwayat XP tahan arsip**: `XpController` pakai `withTrashed()` (Quiz+Module) + fallback judul
  "Kuis" di view → entri modul/kuis terarsip tetap berjudul. +1 test `XpLogPageTest`.
- Konsistensi kecil: observer pakai enum `ActiveStatus::Active` (bukan string `'active'`).
- **Dicatat, sengaja TAK diubah (anti over-engineering)**: unique `quizzes(module_id,deleted_at)` tak
  menegakkan keunikan baris aktif di MariaDB (NULL boleh ganda) — aman krn validasi opsi Select
  Filament ter-scope server-side (diverifikasi ke vendor, `getInValidationRuleValues`); `$hasQuiz`
  query di Blade `modules/show` (1 tempat); label enum ModuleStatus "Draft/Published" (Inggris);
  notif ShouldQueue gagal senyap bila modul dihapus sebelum worker jalan.
- Views: semua halaman portal 1 layout, tanpa override lebar, 0 warna Tailwind mentah (scan grep).
- NEXT: cek browser (item sesi sebelumnya masih berlaku), PR ke main, lalu mulai fitur UMKM.

---

## Sesi 2026-07-02 — Profil admin/super (read-only+modal), modal OTP ramping, user menu ringkas, dropdown warga

Branch `feat/ref-wilayah-sumbar`. Suite **286 (284 lulus, 2 skip)**. Pint bersih. Belum merge.

- **Profil admin & super admin** = halaman Filament kustom **`App\Filament\Pages\Profil`** (route `/admin/profil`),
  pola "read-only dulu, ubah lewat modal" (seperti warga): tampilan read-only + aksi header **Ubah Profil**
  (nama super-only, email, No. HP) & **Ubah Keamanan** (username super-only, sandi; **wajib `current_password`**).
  Nama & username admin desa FIX (dikelola super admin). `->profile()` bawaan dimatikan; `EditProfile` dihapus.
  KEPUTUSAN user: username hanya super admin; ganti sandi wajib sandi lama.
- **Modal OTP login pertama admin** (`ForcePasswordChange`): field **kontak (No.HP/email) DIHAPUS** — cukup
  ganti sandi. Kontak dilengkapi lewat Profil. 2 test kontak dibuang.
- **Panel Filament**: `->darkMode(false)` (tanpa switcher tema). **Topbar tetap** (avatar + badge peran).
  **Dropdown** diringkas jadi **Profil + Keluar** via key `'profile'` (menimpa item akun default Filament yang
  menampilkan header nama) — TANPA override view vendor. CATATAN: sempat over-remove avatar+badge topbar
  (user koreksi: hanya dropdown), sudah dikembalikan.
- **Dropdown warga** (portal): header avatar+nama dihapus, isi cukup **Profil** + **Keluar**.
- **Kolom Kode Wilayah** (tabel Desa, super admin): `->copyable()->copyableState(digit-only)` — tampil
  "13.71.01.1001", tersalin "13710110001" (angka saja).
- NEXT: cek browser (login pertama OTP, /admin/profil kedua peran, dropdown), lalu PR ke main.

---

## Sesi 2026-07-01 — Login gabungan, profil ready-production, notifikasi modal, audit bisnis+storage, loading bersama

Branch `feat/ref-wilayah-sumbar`. Suite **284 (282 lulus, 2 skip)**. Pint bersih. Belum merge.

- **Login GABUNGAN (semua peran) di `/login`** (nama route `login`, keluar dari prefix `/portal`): warga=NIK/email,
  admin/super=username/email — deteksi otomatis (16 digit→NIK, `@`→email, selain itu→username). Redirect per
  peran (admin→`/admin`, warga→portal); **BUKAN `intended()`** (cegah warga terlempar ke /admin lalu ditolak).
  Login bawaan Filament DIMATIKAN (`->login()` + kelas `App\Filament\Auth\Login` dihapus); tamu `/admin`→`/login`
  via fallback auth handler. Semua ref `portal.login`→`login`. `UnifiedLoginTest` (11).
- **Halaman profil warga ready-production** (`profile/edit`): foto (Cropper.js crop/zoom + kompres klien **<50KB,
  160px**=2× tampil terbesar 80px; thumb 160²), kontak (email/HP), ganti sandi (wajib sandi lama) — semua
  **read-only dulu + tombol "Ubah"** (Alpine); data kependudukan **read-only** (baris "Desa" dibuang; catatan
  "hubungi admin {sebutan}"). Endpoint `profile.contact`/`profile.password`. `PortalProfileUpdateTest`.
- **Notifikasi = MODAL** (bukan halaman): lonceng buka modal Alpine (teleport, bottom-sheet HP); buka→POST
  `notifications/read` (badge nol). Halaman+route lama dihapus. **+notif balasan diskusi** (`DiscussionReplied`,
  admin & warga→penanya, bukan self). `PortalNotificationTest`, `DiscussionModerationTest` +3.
- **Loading form = pola bersama**: handler global `app.js` (delegation, skip Livewire) + `x-portal.button`
  type=submit auto spinner (`data-loading`); auth pill pakai atribut generik. Dipakai login/ganti-sandi/profil/
  UMKM/diskusi. Lihat DECISIONS.
- **Reader materi** (`modules/page`): isi memenuhi kartu (buang `max-w-[52rem]`), sidebar ramping `w-72`
  (fokus isi), padding HP `px-5`, dots progres disembunyikan di HP. **Prose dirapatkan** (`app.css`): body
  `on-surface` (kontras), margin p/li/ul dipadatkan, `space-y-5` antar-blok.
- **Foto top-5 leaderboard beranda** kini tampil (dulu inisial saja) + eager-load media.
- **Badge `urutan` modul dihapus dari daftar warga** (angka global bercelah, mis. "10" dari seeder — menyesatkan;
  urutan tetap diterapkan). Reorder admin tetap.
- **Audit alur+logika bisnis LMS** = robust (progres/kuis/XP/scoping). Catatan deploy **`queue:work` WAJIB**
  (`QUEUE_CONNECTION=database` + notif ShouldQueue) ditulis di README.
- **Audit hapus/ganti file storage** (super/admin/warga) = aman: Media Library (avatar/cover/foto UMKM/logo)
  auto-hapus saat force-delete/replace; blok materi via hook `ModulePage`. +3 test regresi (ganti/buang/hapus blok).
- **Dropdown**: nama desa dibuang, "Keluar dari Akun"→"Keluar".
- **Dep baru**: `cropperjs` (DECISIONS). NEXT: cek browser (login pertama OTP warga & admin), PR ke main.

---

## Sesi 2026-06-26 (lanjutan 16) — Reader: margin konsisten, tombol disederhanakan, scope badge dihapus

Suite **260 (258 lulus, 2 skip)**. Pint bersih.
- **Margin reader konsisten**: `page.blade.php` hapus override `max-w-7xl` → ikut default `max-w-[120rem]`
  (sama dgn halaman lain). Isi kartu dipusatkan `mx-auto max-w-[52rem]` (header+body sejajar) agar full-width
  tetap nyaman dibaca. Bottom bar ikut `max-w-[120rem]` + `lg:px-margin-desktop`.
- **Tombol reader disederhanakan** (sesuai permintaan): kiri = `Sebelumnya`/`Ke Modul`; kanan =
  `Selanjutnya`/`Selesaikan` (terakhir, hijau sdg-3 + centang). Hilang istilah "Tandai selesai"/"Lanjut"
  (perilaku POST-menandai-selesai tetap di balik tombol saat materi belum selesai).
- **Scope dihapus dari modul**: badge "Semua Desa" dibuang dari `show.blade.php` (hero) & `index.blade.php`
  (kartu) — warga tak perlu lihat info global/desa.
- **Pematangan isian opsional kosong** (`module-block.blade.php`): blok teks `konten` kosong tidak dirender;
  caption video hanya tampil bila ada video valid (`$hasVideo`). Deskripsi/estimasi/cover/kuis sudah ter-guard.

## Sesi 2026-06-26 (lanjutan 15) — Reader materi: baca berurutan + semua tipe + typography

Suite **260 (258 lulus, 2 skip)**. Pint bersih.
- **Materi WAJIB berurutan** (anti-skip): `LmsProgressService::isPageAccessible()` (semua materi sebelum
  target harus selesai) + `firstIncompletePage()`. `PageController::show` & `complete` menolak halaman
  yang dilewati → `show` mengalihkan ke materi yang seharusnya (flash error), `complete` tolak ke modul.
  Test `SequentialPageAccessTest` (6) — service + HTTP (akses langsung URL & POST loncat).
- **UI terkunci**: di `show.blade.php` (daftar materi) & `page.blade.php` (sidebar reader), materi setelah
  titik "lanjut" tampil ikon gembok, non-klik, redup, label "Terkunci".
- **Dependency baru**: **`@tailwindcss/typography`** (devDep) — plugin diaktifkan `@plugin` di `app.css`,
  var `--tw-prose-*` di-override ke token NCH (`@layer components .prose`). Sebelumnya kelas `prose`
  tak berefek (plugin belum ada) → heading/list/kutipan materi tampil datar. Blok 'teks' kini
  `prose prose-base max-w-none sm:prose-lg`. (DECISIONS.md diperbarui; user izinkan install dep.)
- **Semua 6 tipe blok terverifikasi render** (teks kaya, video YouTube/Drive embed, gambar+caption,
  PDF preview+unduh, audio player, lampiran unduh) via modul uji sementara → screenshot → dihapus.

## Sesi 2026-06-26 (lanjutan 14) — Rombak halaman detail modul (CTA jelas, 1 kolom fokus)

Masalah lama: tombol "Mulai Belajar" terdampar di sidebar kanan → user bingung di mana mulai;
di mobile CTA jatuh jauh ke bawah. `portal/modules/show.blade.php` dirombak:
- **Lebar penuh konsisten** (`max-w-[120rem]` default, TANPA override) = sama dgn Beranda/Peringkat/dst
  → jaga konsistensi margin kiri-kanan (sempat sempat keliru pakai `max-w-4xl` terpusat, sudah dibatalkan).
- **Tata letak 2 kolom** (mirip Beranda): kiri (col-span-2) Hero+CTA, Kuis, Diskusi; kanan Daftar Materi.
  Penempatan grid eksplisit (`lg:col-start-3 lg:row-span-2 lg:self-start`) agar **urutan mobile** tetas
  benar: Hero+CTA → Materi → Kuis → Diskusi.
- **CTA utama tepat di bawah judul** di hero: tombol "Mulai Belajar"/"Lanjutkan Belajar" (full-width mobile,
  `sm:w-fit` desktop) + hint "Mulai/Lanjut dari materi: {judul}". State **selesai**: badge "sudah kamu
  selesaikan" + tombol sekunder "Tinjau Ulang Materi".
- **Materi banyak tidak memanjangkan halaman (desktop)**: kartu Daftar Materi `lg:sticky lg:top-20`, daftar
  `lg:max-h-[calc(100vh-9rem)] lg:overflow-y-auto` → scroll di dalam kartu. Diverifikasi DOM (1440×720:
  OL clientH 576 < scrollH 1004 = scroll internal; tinggi dokumen tetap ~822, tak ikut memanjang).
- Item berikutnya **disorot** (bg primary, nomor terisi, pill "MULAI/LANJUT"); selesai = ceklis hijau
  "Selesai dibaca"; belum dibaca tampil label tipe blok (Teks·Video via `ModuleBlockType`).
- Verifikasi visual: belum-dimulai, selesai, materi-banyak; desktop+mobile. Test modul 34 + smoke 12 hijau.

## Sesi 2026-06-26 (lanjutan 13) — Label "Peringkat" tanpa sebutan + kartu KPI desktop diperkecil

- Kartu KPI **"Peringkat {sebutan}" → "Peringkat"** (hapus `$sebutanDesa`, kini tak terpakai).
- **Desktop (sm+) kartu KPI diperkecil sedikit** (`home.blade.php`): value `sm:text-metric-lg`(32px)→
  `sm:text-2xl`(24px); ikon tile tak lagi membesar (`sm:h-10 sm:w-10` dihapus, tetap `h-9 w-9`); label
  header `text-headline-sm`(18px)→`text-sm font-semibold`; padding `sm:p-lg`→`sm:p-4`; margin `sm:mb-md`→
  `sm:mb-2.5`. Mobile (kotak kecil) tak berubah. Smoke 12 lulus; verifikasi visual desktop+mobile.

## Sesi 2026-06-26 (lanjutan 12) — Beranda mobile (kotak KPI kecil, modul dulu)

- **Kartu KPI beranda responsif** (`home.blade.php`): mobile = 3 kotak kecil (`grid-cols-3 gap-3`, kartu
  kompak `p-3`, header-label & chevron disembunyikan, value text-xl, label ringkas `line-clamp-2`);
  sm+ = kartu penuh seperti semula (`sm:p-lg`, label di header, chevron, value `sm:text-metric-lg`).
- **Urutan beranda**: `lg:order-*` → `order-*` (berlaku semua ukuran) → di mobile **modul (Lanjutkan
  Belajar) tampil dulu, baru Peringkat**; desktop tetap Lanjutkan kiri-lebar + Peringkat kanan.

## Sesi 2026-06-26 (lanjutan 11) — Hapus nav Peringkat, kartu KPI interaktif, halaman XP baru

Suite **254 (252 lulus, 2 skip)**. Pint bersih.
- **"Peringkat" dihapus dari nav** (sidebar desktop + bottom nav mobile) di `portal/layouts/app.blade.php`;
  rute & halaman `portal.leaderboard` TETAP. Diakses lewat kartu KPI interaktif.
- **3 kartu KPI beranda jadi link** (`<div>`→`<a>` + chevron indikator): Modul Selesai→modules.index,
  Poin Terkumpul→**portal.xp** (baru), Peringkat→leaderboard.
- **Halaman XP baru**: rute `portal.xp` → `Portal\XpController` → `portal/xp/index.blade.php`. Tampilkan
  ledger `XpLog` user (sumber module/quiz/discussion; judul di-resolve massal anti-N+1; `Quiz::title` =
  accessor "Kuis: <judul modul>", butuh eager-load module). Banner total + breakdown +50/+100/+20.
  Test: `XpLogPageTest` (render judul sumber + isolasi antar-user) + smoke `portal.xp`.
  Lebar halaman XP disamakan dgn leaderboard: full-width (hapus override `max-w-3xl`), banner `p-5 mb-5`.
  Ikon entri XP: HANYA entri 'module' (Menyelesaikan modul) pakai **cover modul asli** (`$module->coverUrl()`,
  fallback default svg), ukuran tetap h-11 w-11; 'quiz' & 'discussion' tetap ikon tile masing-masing
  (clipboard-check / chat). Controller resolve modul tiap entri (kuis via `quiz.module_id`), eager-load `media`.

## Sesi 2026-06-26 (lanjutan 10) — Rapikan label & kartu peringkat beranda

- Kartu peringkat beranda: judul "Peringkat 5 Teratas Warga {sebutan}" → **"Peringkat 5 Teratas"**;
  kembalikan **baris posisi-Anda** (top 5 + baris Anda = 6 baris bila di luar 5 besar) → mengisi kartu,
  jarak antar-baris lebih rapat (atasi keluhan "terlalu jarak antar vertikal").
- KPI card **"Peringkat Desa" → "Peringkat {sebutan}"** (mis. "Peringkat Nagari", `$sebutanDesa` di top @php).
- Link kartu Lanjutkan Belajar: "Lihat Semua Modul" → **"Lihat Semua"**.

## Sesi 2026-06-26 (lanjutan 9) — Inisial avatar 2 huruf konsisten

- **Komponen `x-portal.avatar`**: inisial fallback dari 1 huruf → **2 huruf** (huruf depan 2 kata pertama,
  mis. "Nurul Hidayah"→"NH"; nama 1 kata tetap 1 huruf). Kini SAMA dgn kartu peringkat beranda yang sudah
  pakai helper `$initials`. Berlaku ke semua avatar tanpa foto: header, profil, leaderboard, podium, diskusi.

## Sesi 2026-06-26 (lanjutan 8) — Logo 2-baris sama-lebar, kartu beranda sejajar

- **Logo sidebar**: subtitle "Smart Learning Center" di-`justify` (text-align-last:justify, wordmark `w-fit`
  jadi acuan lebar) → kedua baris SAMA PANJANG (rata kiri & kanan). Lihat [[portal-design-tokens]].
- **Kartu Lanjutkan Belajar**: link "Semua" → "Lihat Semua Modul".
- **Kartu peringkat beranda**: judul "Peringkat Warga Desa" → "Peringkat 5 Teratas Warga {sebutan}"
  (`$user->desa?->jenisDesa?->nama` mis. "Nagari"); tabel diganti daftar flex, tampil TEPAT 5 teratas
  (baris posisi-Anda tambahan dihapus).
- **Sejajarkan 2 kartu**: header keduanya `text-headline-sm` + truncate (tinggi sama); body `flex flex-1
  flex-col`, tiap baris `flex-1` → 5 modul & 5 peringkat tinggi baris identik (grid stretch samakan tinggi kartu).

## Sesi 2026-06-26 (lanjutan 7) — Logo 1-baris + thumbnail Lanjutkan Belajar

- **Brand sidebar**: "Basamo NCH" diperbesar (text-xl, mark h-10) & subtitle "Smart Learning Center"
  diperkecil (text-[11px]); keduanya `whitespace-nowrap` + padding dikecilkan → masing-masing 1 baris.
- **Kartu Lanjutkan Belajar**: kini 5 modul dgn THUMBNAIL asli (`$module->coverUrl()`, fallback default
  SVG) ganti ikon-tile; badge centang utk selesai. `HomeController`: `take(4)`→`take(5)`, buang terkunci
  (`->reject(... === 'locked')`), eager-load `media`. Urutan prioritas TETAP: in_progress→available→completed, lalu urutan.

## Sesi 2026-06-26 (lanjutan 6) — Fix timezone (sapaan salah)

- **Bug sapaan "Selamat pagi" jam 15:00 WIB** = `app.timezone` masih `UTC` → `now()->hour` pakai UTC
  (15 WIB = 08 UTC → rentang pagi). Fix: `config/app.php` `'timezone' => env('APP_TIMEZONE','Asia/Jakarta')`
  + `APP_TIMEZONE=Asia/Jakarta` di `.env` & `.env.example`. Verifikasi: now()=15:07 → "Selamat sore". Suite tetap 249 lulus.
  CATATAN: timestamp lama (disimpan saat UTC) akan dibaca sbg WIB (geser 7 jam) — sembuh saat migrate:fresh --seed.

## Sesi 2026-06-26 (lanjutan 5) — Samakan logo portal dgn brand kanonik

- **Logo portal diperbaiki** (sidebar desktop + header mobile di `portal/layouts/app.blade.php`): dari
  LINGKARAN biasa + ikon putih → **gonjong-peak** (clip-path atap, bg deep-blue) + academic-cap EMAS
  (`text-secondary-container`) + wordmark "Basamo <span text-secondary>NCH</span>". Kini SAMA dgn publik
  (`public/layouts/app.blade.php`), admin (`filament/brand.blade.php`), dan login. Lihat [[portal-design-tokens]].
- Konfirmasi: sapaan beranda DINAMIS per jam (pagi/siang/sore/malam) — `home.blade.php` match($hour).
- (Belum diubah) 3 kartu KPI beranda (Modul Selesai/Poin/Peringkat Desa): "Peringkat Desa" redundan
  dgn kartu Peringkat Warga Desa — menunggu keputusan user apakah dipangkas.

## Sesi 2026-06-26 (lanjutan 4) — Podium leaderboard, sidebar bersih, ukuran kartu beranda

Suite 251 (249 lulus, 2 skip). Diverifikasi Playwright @1920px.
- **Beranda tukar UKURAN kartu** (lanjutan dari swap posisi): "Lanjutkan Belajar" kini KIRI+LEBAR
  (`lg:order-1 lg:col-span-2`), "Peringkat Warga Desa" KANAN+SEMPIT (`lg:order-2`).
- **Sidebar dibersihkan dari tombol duplikat**: hapus CTA "Mulai Belajar" (= nav Belajar) + blok bawah
  "Profil Saya"/"Keluar" (sudah ada di dropdown menu pengguna pojok kanan atas). Sidebar = brand + nav saja.
- **Leaderboard redesign PODIUM top-3**: `LeaderboardController` kirim `$podium` (top 3). View baru:
  podium emas/perak/perunggu (sdg-2 / on-surface / sdg-12), juara 1 tengah+trofi+tertinggi, urut tampil
  [2,1,3], tumpuan tinggi beda + garis lantai (`border-b-2`); daftar peringkat 4+ di bawah (penuh).
  Podium ter-center `max-w-3xl`, banner+daftar full-width (minim margin).

## Sesi 2026-06-26 (lanjutan 3) — Lebarkan portal (minim margin), tukar kartu beranda

View-only. Diverifikasi Playwright @1920px.
- **Minim margin kiri-kanan SEMUA halaman portal**: default `<main>` `max-w-5xl`→`max-w-[120rem]`
  (≈full-width pada layar lebar, sisa hanya padding `margin-desktop` 48px). Hero beranda diselaraskan.
  Reader `max-w-6xl`→`max-w-7xl` (+ nav bawah). CATATAN: `max-w-[120rem]` arbitrer (skala nama pecah, lihat [[portal-design-tokens]]).
- **Tukar posisi kartu beranda** (via `lg:order-1/2`): "Lanjutkan Belajar" kini KIRI (sempit),
  "Peringkat Warga Desa" KANAN (lebar col-span-2).
- **Modul index**: `md:grid-cols-2`→`+xl:grid-cols-3` (lebih padat di layar lebar).

## Sesi 2026-06-26 (lanjutan 2) — Hilangkan dialog native, modal konfirmasi, perbesar reader, verifikasi visual

Suite 251 (249 lulus, 2 skip). View/JS/SVG-only. **Diverifikasi langsung via Playwright + google-chrome**
(screenshot login/home/reader/leaderboard/quiz/modal/cover, desktop+mobile).

- **Ganti SEMUA dialog native browser** ("localhost:8000 says…") → komponen `x-portal.confirm-dialog`
  (Alpine + x-teleport, bertema NCH, ikon+overlay-blur, tombol Batal/Konfirmasi). Dipakai di: submit
  kuis (`quiz-player`, dulu `wire:confirm`) & hapus produk UMKM (`umkm/index`, dulu `onsubmit confirm()`).
  Komponen dukung prop `form="id"` (submit form by-id) atau `on-confirm="ekspresiAlpine"`.
- **Perbesar laman baca materi**: layout `<main>` kini override-able via `@yield('main-width')`;
  `modules/page` → `max-w-6xl` + header/padding lebih lega + prose `module-block` naik ke base/lg.
- **Cover modul default** (`public/images/default-module-cover.svg`): gradient indigo→violet diganti
  deep-blue NCH (#003857→#1b4f72) + aksen Minang gold.
- **⚠️ GOTCHA (lihat [[portal-design-tokens]]):** `max-w-sm` PECAH jadi 8px karena proyek redefinisi
  skala nama sm/md/lg/xl. Util max-width WAJIB nilai arbitrer (`max-w-[26rem]`). Ketahuan saat lihat
  screenshot modal kolaps — bukti pentingnya verifikasi visual.

## Sesi 2026-06-26 (lanjutan) — Audit & konsistensi front-end Portal Warga + fix kuis multi-jawaban

Suite **251 (249 lulus, 2 skip)**. View-only, belum di-commit.

- **Fix bug kuis multi-jawaban** (`QuizPlayer::mount`): pra-inisialisasi `answers[questionId]` = `[]`
  untuk soal multi (else skalar `null`). Tanpa ini Livewire perlakukan grup checkbox sebagai boolean
  tunggal → mencentang satu mencentang semua.
- **Rebrand 7 halaman portal off-brand → token NCH** (sebelumnya pakai indigo/violet/gray/emerald/amber/
  slate mentah): `notifications/index`, `leaderboard/index` (gradient indigo→violet diganti `bg-primary`
  + medali sdg-2/sdg-12), `profile/edit`, `umkm/index`, `umkm/product-form`, `umkm/profile`,
  `auth/change-password` (disamakan dgn login: gonjong-peak + academic-cap). Scan grep: 0 warna mentah tersisa.
- **Hapus flash sukses ganda** di `profile/edit` (layout sudah render flash global).
- **Seragamkan sapaan**: leaderboard `(kamu)`→`(Anda)`, judul "Peringkat XP"→"Peringkat".
- Penempatan tombol & "duplikat" (Profil/Keluar di sidebar + dropdown header) ditinjau = konvensional, dipertahankan.
- **Satukan sistem alert → 1 kanal toast.** Dulu ada 2: banner flash statis (polos, monoton) + toast.
  Banner flash di `portal/layouts/app.blade.php` DIHAPUS, diganti skrip jembatan flash→`CustomEvent('toast')`
  (success/info/error; error timeout 6s, lainnya 4s). Komponen `toast.blade.php` kini berikon per-tipe
  (check/info/exclamation/sparkles) + ukuran naik (max-w-26rem, p-4, rounded-2xl) + tombol tutup lebih besar.
  Confetti `app.js` diwarnai ulang ke palet NCH (#003857/#fed33e/#4c9f38/#0a97d9).
  Catatan: alert "Kata sandi berhasil diperbarui" di dashboard pasca ganti-OTP = flash `info` by design (bukan bug).
- ⚠️ Perlu `npm run dev`/`build` agar utility token baru ter-compile (sudah `npm run build` di sesi ini).

## Sesi 2026-06-26 — Polish UX tabel admin (non-klik + aksi), drill-in sidebar, kurasi kolom, balas diskusi

Branch `feat/ref-wilayah-sumbar`. Suite **242 (240 lulus, 2 skip)**. Pint bersih. **5 commit, di-push**
(`c4bc352 · d98b056 · cd76e3a · 4dfdc77 · c03a141`). Belum merge. Sesi banyak iterasi UI bersama user.

- **Pola tabel baru (REVISI user, ganti pola lama "klik baris → edit/view"):** baris tabel **TIDAK dapat
  diklik** (`->recordUrl(null)`) di **Desa, Warga, Wilayah, Modul, Quiz, Diskusi** → buka **Ubah lewat
  tombol aksi**. Aksi: **Desa & Warga = menu ⋮ (ActionGroup)** (aksi banyak); **Wilayah/Modul/Quiz =
  inline/sejajar** (sedikit). **Aksi Ubah inline diberi warna kuning** (`->color('warning')`) di
  Wilayah/Modul/Quiz (permintaan user). Lihat [[admin-ui-conventions]] (sudah diperbarui).
- **Halaman Lihat DIHAPUS** untuk **Desa** (ViewDesa/DesaInfolist) & **Warga** (ViewUser/UserInfolist) →
  resource hanya index/create/edit. Tes `PendudukTest` "halaman lihat warga" dialihkan ke `EditUser`
  (assertFormSet identitas penduduk termuat). CATATAN ITERASI (dibatalkan, JANGAN diulang): sempat dicoba
  kelola sub-unit INLINE di form Desa + DesaInfolist + halaman Lihat read-only Desa/Warga — semua dihapus.
- **Drill-in: menu per-desa DISEMBUNYIKAN dari sidebar super admin** (commit 4dfdc77). Dulu masuk konteks
  desa (Kelola Warga/Wilayah/UMKM) → DesaContext aktif → ketiga menu muncul di sidebar (membingungkan).
  Fix: `shouldRegisterNavigation()` di UserResource/DesaUnitResource/UmkmProfileResource = **hanya
  `isDesaAdmin()`** (dipisah dari `canAccess()` yg tetap izinkan super admin saat konteks). Sidebar super
  admin tetap bersih (Desa+LMS); navigasi per-desa via aksi tabel Desa + "Kembali ke Desa".
- **Tabel Wilayah:** nama sub-unit **diprefiks sebutan** (mis. "Jorong Koto Tuo") via `formatStateUsing`
  (search/sort tetap pakai `nama` mentah).
- **Kurasi kolom Modul (permintaan user):** **hapus kolom Materi/Durasi/Urutan** (buang `withCount('pages')`).
  Default: No./Cover/Judul/Desa/Status/Kuis; sekunder (toggle "Kolom"): Prasyarat (eager-load
  `prerequisite`)/Dibuat oleh/Dibuat. Pengurutan modul tetap (drag, field `urutan` ada).
- **Diskusi: aksi "Balas"** — admin (super & desa, gate `can('update')`) menjawab pertanyaan warga;
  balasan via `$record->replies()->create([module_id,user_id=auth,isi])` atas nama admin, hanya pada
  pertanyaan top-level belum dihapus, TANPA XP. Tes baru di `DiscussionModerationTest`.
- **Label "Global" → "Semua"** se-aplikasi (user): kolom Desa modul="Semua", placeholder form="— Semua
  desa —", badge portal="Semua Desa" (perlu `npm run build`), filter="Semua (termasuk semua-desa)".
  Komentar kode & `withoutGlobalScopes` dibiarkan (bukan teks user-facing).

---

## Sesi 2026-06-25 — Hardening menyeluruh admin (Warga/Desa/Wilayah/UMKM) + audit LMS + model akun/OTP seragam

Branch `feat/ref-wilayah-sumbar`. Suite **235 (233 lulus, 2 skip)**. Pint bersih. **6 commit di-push akhir
sesi** (`df16ac5 · 0474827 · 37aa422 · dd40bf9 · 6922780 · 57c2709`). Belum merge. Sesi panjang, audit
per-fitur (user minta robust & production-ready).

- **Warga (admin desa) — hardening**: hapus dead trait `InteractsWithPenduduk`; aksi custom (resetOtp/UMKM)
  digate `! trashed() && can('update')`; cabut UMKM transaksional; email lowercase; NIK editable + validasi
  anti-bentrok identitas penduduk; **impor Excel → queue** (`App\Jobs\ImportWarga` + notifikasi DB; panel
  `->databaseNotifications()`); `WargaImportService`/`WargaImport` terima `Desa` eksplisit (fix bug super-admin).
- **Wilayah (sub-unit) & Desa — audit+fix**: guard anti-orphan hapus sub-unit berpenghuni; **unique ramah
  soft-delete** untuk `desa_units`, `desas.wilayah_kode`, `users.username`, `quizzes.module_id` (semua
  `(col, deleted_at)`; migrasi: tambah komposit DULU baru drop unique lama karena FK); EditDesa transaksional.
- **Drill-in super admin** (DesaContext/managedDesaId): kini **Warga + Wilayah + UMKM** lewat satu dropdown
  **"Kelola ▾"** di tabel Desa. LMS sengaja TANPA drill-in (modul terpusat global/per-desa).
- **Akun admin desa = paralel 100% warga**: username OTOMATIS = digit kode nagari (`Desa::usernameFromKode`),
  nama FIX "Admin {nama_lengkap}", **OTP model tunda** (blank→ditunda, bukan auto-gen), Reset OTP di **tabel**
  Desa. OTP warga↔admin konsisten (satu jalur `issueOtp`). Email & No.HP admin = opsional.
- **Login pertama (ganti sandi) bisa lengkapi kontak**: warga (No.HP+email di form portal) & admin (profil
  Filament custom `App\Filament\Auth\EditProfile` — **tanpa field Nama** karena nama fix, +No.HP).
- **Konsistensi istilah UI** lintas panel + **glosarium di `CONVENTIONS.md`** (sub-unit fallback "Wilayah",
  "Penyebutan desa", "No. HP", "No. WhatsApp" khusus UMKM, kosakata "Hapus/Dihapus"). Copyable NIK & Kode Wilayah.
- **LMS — audit + hardening**: penyelesaian materi kini **eksplisit** (tombol "Tandai selesai" POST, bukan
  auto saat GET); guard modul published tak boleh tanpa materi; **Quiz SoftDeletes** (Restore/Force/Trashed);
  kuis submit transaksi + lockForUpdate; force-delete modul bersihkan PDF yatim; url_video dibatasi http/https;
  buang aksi massal di relation manager Soal/Materi. **Materi video/PDF kini bisa diberi teks penjelasan/
  instruksi opsional** (kolom `konten` utk semua tipe, tampil disanitasi di atas media).
- **BUG penting diperbaiki**: field materi kondisional (url_video/path_file/label konten) tak muncul saat ganti
  tipe — `Select::options(EnumClass)` membuat `$get('tipe')` = instance enum dibanding string → selalu false.
  Fix helper `isType()`. **PELAJARAN: jangan `$get('field') === 'string'` utk Select ber-options enum.** Disapu
  seluruh app: tak ada kasus serupa lain. Fidelity teks RichEditor→warga PERSIS (sanitizeHtml mempertahankan style).
- **CATATAN demo**: login admin desa demo kini **username = kode nagari** (sandi `password`), berlaku setelah
  `migrate:fresh --seed`. Verdict: Warga + Desa + Wilayah + LMS = robust & production-ready.

---

## Sesi 2026-06-24 — Impor Excel · konsistensi form · Pengaturan Desa+peta · navigasi per-peran + drill-in Warga

Branch `feat/ref-wilayah-sumbar`. Suite **202 (200 lulus, 2 skip)**. Pint bersih. **Di-push** s/d
`29dfb3f`. Belum merge. Banyak iterasi bersama user (sering ganti arah → "rela mulai dari 0").

- **Impor warga via Excel** (paket baru `maatwebsite/excel`, dicatat DECISIONS): ActionGroup "Impor" di
  header ListUsers — **Unduh Template** (`WargaTemplateBuilder`: XLSX 3 sheet Data/Petunjuk/Referensi,
  dropdown enum = persis form create, **tanggal d/m/yyyy**, tooltip tiap kolom) + **Impor** (`WargaImport`
  hanya sheet pertama → `WargaImportService` per-baris: validasi ketat, transaksi, lapor error per-baris).
  Guard: blokir bila sebutan/daftar sub-unit belum siap. `WargaImportTest` (16).
- **Konsistensi form admin**: SEMUA form (Desa/Wilayah/Modul/Kuis/Profil UMKM/Kategori/Warga/Pengaturan
  Desa) → pola **section bertumpuk full-width + ikon, tanpa deskripsi section** (helperText per-field tetap).
  Form Warga dikelompokkan ala-KTP (Identitas/Data Sosial/Alamat/Kontak/Akun & Status).
- **OTP awal**: kolom **tampil default** di index; **Reset OTP** bisa input manual (kosong→auto); field OTP
  *disabled* **dihapus** dari Edit (ubah OTP via aksi Reset OTP). Paginasi tabel +opsi **"Semua"**.
- **Pengaturan Desa** (admin desa): editable **penyebutan desa + sebutan sub-unit + logo**; identitas resmi
  (nama/kode wilayah/prov/kab/kec) **read-only** = header profil (nama + badge kode + breadcrumb). **Peta
  batas wilayah** di bawah via **endpoint ter-cache** `admin/desa/peta-batas` (`DesaBoundaryController`,
  ter-scope, `Cache::remember` + `ST_AsGeoJSON` presisi + `Cache-Control`), fetch klien (Leaflet CDN).
- **Navigasi per-peran** (lihat DECISIONS "Navigasi per-peran + drill-in"): super admin master **Desa+LMS**;
  admin desa master **Warga**. **LMS (Modul/Kuis/Diskusi) = super-only** → **disembunyikan** dari admin desa
  (`shouldRegisterNavigation`, akses kode tetap → reversibel, BUKAN dihapus). Kategori UMKM `canAccess` super-only.
- **Drill-in Warga (kunci)**: aksi **"Kelola Warga"** per-baris di tabel Desa → set **`DesaContext`** (session)
  → redirect ke halaman Warga desa itu, **100% identik** panel admin desa karena `UserResource` yang SAMA
  dipakai ulang, di-scope via **`User::managedDesaId()`** (desa_admin→desanya; super→desa konteks). Semua jalur
  warga (resource/form/tabel/impor/template) ikut `managedDesaId`. Super tanpa konteks **tak bisa** akses Warga
  (`canAccess` guard); masuk daftar Desa membersihkan konteks; tombol "Kembali ke Desa" + subjudul desa.
- **`WargaProvisioningService`** (create/update warga + penduduk + OTP) = sumber tunggal; `CreateUser`/`EditUser`
  delegasi ke sini (refactor DRY, hapus trait `InteractsWithPenduduk`).
- **Redirect ke index** setelah create/edit (trait `RedirectsToIndex` di 12 halaman) — bukan ke Edit.
- **Label sub-unit ikut sebutan desa** (`DesaUnitForm`): "Nama Jorong"/"Data Jorong" + helperText benar
  (isi cukup nama, tanpa awalan — sebutan disimpan terpisah). Data `desa_units.nama` = nama saja.
- Tes baru: `WargaImportTest`, `SidebarNavigationTest`, `DesaWargaContextTest`, `WargaProvisioningServiceTest`.
- **Ditunda (sengaja, "itu saja dulu")**: drill-in UMKM/Wilayah/Modul per-desa untuk super admin (baru Warga).

## Sesi 2026-06-23 (lanjutan) — Refactor 3-lapisan: tabel `penduduk` (identitas / akun / akses)

Branch `feat/ref-wilayah-sumbar`. Suite **170 (168 lulus, 2 skip)**. Pint bersih. Belum merge.
Membongkar [[warga-data-master]] sesuai [[penduduk-plan]]. Keputusan user (via AskUserQuestion):
**(1)** `nik` di-mirror di `users` sbg kunci login (AuthController tak berubah); **(2)** form
UserResource tetap terpadu (upsert penduduk inline).

- **Tabel `penduduk`** (identitas, lapisan 1): nik(unik)/nama/desa_id/desa_unit_id + demografi
  (tempat_lahir/tanggal_lahir/jenis_kelamin/agama_id/status_perkawinan_id/pekerjaan_id);
  softDeletes + LogsActivity + BelongsToDesa. Orang bisa ada tanpa akun. Relasi `Penduduk::users()`
  **hasMany** (penduduk_id non-unik → 1 orang boleh >1 akun).
- **`users`** (akun, lapisan 2): +`penduduk_id` (FK nullOnDelete, **non-unik**) + `nik` (mirror login).
  **Drop** 6 kolom demografi (pindah ke penduduk). Relasi `User::penduduk()`; relasi agama/status/
  pekerjaan pindah ke `Penduduk`. LMS/UMKM/XP TETAP di users. **1 akun = 1 role.**
- **Akses** (lapisan 3): Spatie+Shield apa adanya (YAGNI; cek role hardcoded belum diubah).
- **Sinkronisasi**: `PendudukService::syncForUser` (upsert penduduk + mirror nik/nama/desa) dipanggil via
  trait `InteractsWithPenduduk` di Create/EditUser. UserForm demografi: `relationship()`→`options()`.
  Migrasi 200559 di-rewrite (create penduduk + link users) & di-rename.
- **DemoSeeder**: buat `penduduk` (demografi acak) lalu tautkan ke user warga.
- **CRUD warga lengkap** di `/admin` (UserResource): + **halaman View** (`ViewUser` + infolist
  `UserInfolist` — Identitas Kependudukan/Akun/Aktivitas, demografi via relasi `penduduk`) +
  `ViewAction` di tabel. Create/Edit/Delete/Restore/Reset OTP/akses UMKM sudah ada. `PendudukTest` (4).
- **Penyederhanaan UserResource (koreksi user):** label menu/resource → **"Warga"** (bukan "Pengguna").
  **Admin desa** kini murni mode warga: field **Peran disembunyikan** (super_admin only; peran tetap
  dipaksa `warga` di server) + default `role=warga`; `isPortalRole()` short-circuit utk desa_admin.
  Tabel: kolom **Peran & Desa** + filter Peran/Desa tampil **hanya untuk super_admin** (bagi desa_admin
  semua baris = warga senagari → redundan). Infolist View juga sembunyikan Peran/Desa utk non-super.
  Tes `UserResourceTest` "tak bisa buat admin" diubah → `assertFormFieldIsHidden('role')` + dipaksa warga.
- **Aturan create warga (koreksi user):** (a) **semua field wajib** saat create kecuali email & no. HP
  (demografi + alamat/`desa_unit_id` `->required(operation create)`; edit tak dipaksa agar record lama
  bisa disunting); (b) field `phone` di-relabel **"No. HP"** (generik, tak khusus WA — helper sebut
  HP/WA/Telegram); (c) **OTP tak auto-generate** saat create — bila field OTP kosong, akun dibuat dgn
  sandi acak tak terpakai + notif "terbitkan OTP via Reset OTP"; bila diisi, jadi sandi awal; (d) menu
  **Warga dipindah dari grup "Pengaturan" → tingkat atas** (`navigationGroup null`, sort 1).
  Helper tes `wargaFormData()` di `tests/Pest.php` (payload warga lengkap). Suite 171 (169 lulus, 2 skip).
- **Penyempurnaan warga (praktik nyata + EYD):** (a) **alamat wajib + graceful** — helperText
  dinamis (Pilih desa dulu / ⚠️ belum ada wilayah → arahkan ke menu Wilayah), `required` tetap
  jalan tanpa crash; (b) **No. HP dinormalkan ke `62`** via `App\Support\PhoneNumber::normalize`
  (`dehydrateStateUsing`), helper DRY dipakai ulang oleh `UmkmProfile::normalizedWhatsapp`;
  (c) **jenis_kelamin tetap `L`/`P`** (standar Dukcapil; 0/1 ditolak — ISO 5218 pakai 1/2);
  (d) **99 pekerjaan diverifikasi sudah lengkap & EYD** (Penerjemah/Provinsi/Atlet/Chef/"Anggota
  Lembaga Tinggi Lainnya") — daftar non-EYD user tidak dipakai; (e) data fondasi: lookup FK
  (agama/status/pekerjaan) **tetap inline di migrasi** (dijamin ada saat migrate), wilayah via seeder.
  Suite 172 (170 lulus, 2 skip).
- **Audit robustness CRUD warga (admin & super_admin):** verdict robust untuk MVP. Tutup 1 risiko
  laten — `PendudukService::syncForUser` kini `firstOrNew(['nik'])` (pakai ulang identitas ber-NIK
  sama, cegah unique violation utk fitur penduduk-mandiri nanti). Sengaja TAK diubah (by-design/
  kosmetik, hindari over-engineering): force-delete sisakan penduduk (identitas independen),
  label validasi huruf kecil (default Filament), guard desa_admin tanpa desa_id (state mustahil).
  +tes: super_admin buat warga, soft-delete sisakan penduduk, reuse penduduk by-NIK. **Suite 175
  (173 lulus, 2 skip).** Semua pekerjaan sesi ini ter-commit; design-system lintas-sesi + referensi
  Stitch ikut di-commit & di-push pada penutupan sesi.
- **UserResource jadi murni WARGA (koreksi user):** (1) +kolom **No.** (`rowIndex`); (2) aksi **Lihat
  dihapus** → **klik baris membuka View** (`recordUrl`); (3) daftar di-scope `role=warga` untuk SEMUA
  (desa_admin: warganya; super_admin: warga semua desa) → akun admin (termasuk diri) tak muncul; admin
  dikelola lewat **form Desa** (sudah ada); (4) demografi dari `penduduk` tersedia sbg kolom **toggle**
  ("Kolom") — eager-load per-halaman, aman N+1; (5) kolom default kurasi: No./Nama/NIK/Wilayah/Akses
  UMKM/Status (+Desa utk super_admin). **Kolom & menu "Wilayah" pakai sebutan sub-unit per desa**
  (Jorong/Korong/…) via `jenisSubUnit`. Form: **field Peran/username/sandi dibuang** (resource warga-
  saja), `role` dipaksa `warga` di CreateUser. Tes diselaraskan (hapus jalur admin-create yang pindah
  ke DesaResource; +tes warga-only list & route-binding). **Suite 173 (171 lulus, 2 skip).**
- **Pola tabel diseragamkan ke SEMUA halaman admin (koreksi user):** kolom **No.** (rowIndex);
  **aksi massal/checkbox dibuang** semua (hapus/pulihkan/force per-baris di ⋮ atau header Edit);
  **klik baris → Edit** (Warga → Lihat) untuk resource ber-form (Desa/Wilayah/Modul/Kuis/Profil UMKM);
  aksi baris dirapikan ke **⋮ ActionGroup**. Read-only (Diskusi/Antrean Produk/Log Aktivitas) +No. saja.
  UmkmCategory (modal/ManageRecords) +No. + ⋮ tanpa klik-baris. Desa: guard anti-orphan + arsip admin
  dipertahankan di ⋮ (Delete/Restore/Force). Kolom Akses UMKM Warga = ikon boolean (centang/silang).
  Smoke 10 halaman index = 200. **Suite 173 (171 lulus, 2 skip).** Belum di-push.
- **Aksi inline (koreksi user):** halaman beraksi sedikit → aksi **langsung tampil** (bukan ⋮). **Hanya
  Warga** yang pakai ⋮ (aksinya banyak: Reset OTP, Beri/Cabut UMKM, Hapus). ActionGroup dibongkar di
  Desa/Wilayah/Modul/Kuis/Profil UMKM/Diskusi/Antrean Produk/Kategori UMKM. Smoke 8 index = 200.
- **Audit RBAC + keputusan ekspansi (user):** kepala desa/aparat **belum dibangun** (cukup 3 peran).
  Rancangan masa depan **dimatangkan & didokumentasikan** (DECISIONS.md "Jalur ekspansi peran/jabatan"):
  peran baru = data Spatie (nol migrasi); data jabatan = tabel baru `jabatan`+`penugasan_jabatan`
  (aditif, gantung di penduduk); kepala desa yg mau LMS = akun warga terpisah. **Tabel `jabatan` +
  kolom `penduduk.jabatan_id` yang sempat dibuat → DICABUT** (prematur/YAGNI). Temuan audit: RBAC
  sekarang biner hardcoded (`isSuperAdmin`/`isDesaAdmin`, 0 permission Shield); saat peran baru
  diaktifkan → pindah gating ke permission-based.
- **Belum (sadar, YAGNI):** PendudukResource mandiri; tabel jabatan/penugasan; authz permission-based.
- **Polish UI panel (koreksi user):** (1) ikon toggle sidebar `SIDEBAR_COLLAPSE/EXPAND_BUTTON` →
  `heroicon-m-bars-3` (hamburger) via `FilamentIcon::register` di `AppServiceProvider` — chevron-ganda
  default disalahartikan sbg "back". (2) Aksi baris bertumpuk/berlabel panjang dibungkus **`ActionGroup`**
  (tombol ⋮, item tetap berlabel+ikon) di UsersTable, DiscussionsTable, UmkmProductsTable,
  ProductsRelationManager — hemat ruang tanpa mengorbankan kejelasan. Tabel beraksi tunggal (Edit) dibiarkan.

## Sesi 2026-06-23 — Design system "Nagari Creative Hub" + Data master warga (NIK)

Branch `feat/ref-wilayah-sumbar`. Suite **166 (164 lulus, 2 skip)**. Pint bersih. Belum commit/merge.
Design kanonik BARU di `stitch_nagari_creative_hub_redesign/` (ganti indigo lama). Memori:
[[stitch-redesign-plan]], [[warga-data-master]], [[penduduk-plan]].

**A. Design system → NCH (palet tunggal):** `app.css` `@theme` = Deep Blue `#003857` + Minang Gold
`#fed33e` + green + **Plus Jakarta Sans** (self-host bunny di `vite.config.js`) + `@layer` motif
gonjong/songket/bada-mudiak. `docs/UI-GUIDE.md` = SoT + tabel ikon + **gotcha `max-w-{sm,md,lg,xl}`**
(token `--spacing-*` menimpa skala lebar → pakai `max-w-2xl..7xl`/arbitrary; fix di toast/login/
change-password/profile).
- **Publik**: `public/layouts/app` + `public/home` dibangun ulang (hero/4-pilar/statistik/produk/
  peta/CTA) — hanya section ber-data nyata; SDGs/IoT "Segera hadir". `HomeController` +produkUnggulan.
- **Login portal** (`portal/auth/login`) diretrofit NCH; **login admin Filament** direstyle senada
  (brand `filament/brand`, `theme.css` `.fi-simple-*`, input fill+fokus emas, tombol pill+ikon).
- **Panel admin** (`AdminPanelProvider`): primary navy (ramp eksplisit, 600=#003857; `Color::hex`
  gagal), font Plus Jakarta Sans, **hapus tema per-peran** (Teal/Indigo) → palet tunggal; brandLogo;
  badge peran netral. Dashboard: `App\Filament\Pages\Dashboard` (heading kosong), card sapaan
  **tanpa peran**, banner+chart navy, latar `.fi-main-ctn` motif belah ketupat.

**B. Data master warga** (warga = baris `users` peran warga — BUKAN tabel terpisah, lihat [[warga-data-master]]):
- Kolom **`nik`** (16, unik) pisah dari `username` (warga login via nik; username=khusus admin).
  Migrasi data warga lama username→nik. `AuthController` portal pakai `nik`.
- Demografi di `users`: tempat/tanggal_lahir, jenis_kelamin(enum), agama_id/status_perkawinan_id/
  pekerjaan_id (FK). Alamat=`desa_unit_id`. Tabel referensi `agama`(6)/`status_perkawinan`(4)/
  `pekerjaan`(99+kode) di-seed inline. UserForm: field nik/username terpisah + section demografi.

**➡️ NEXT (disepakati, belum dikerjakan): buat tabel `penduduk`** — refactor 3-lapisan untuk
extensibility (peran baru kepala desa/aparat dll). Lihat [[penduduk-plan]]. Akan memindah
nik+demografi dari `users` → `penduduk`, tambah `users.penduduk_id` + tabel `jabatan`.

## Sesi 2026-06-22 (lanjutan 3) — Redesign Stitch "Nagari Creative": token + Layout + Dashboard

Branch `feat/ref-wilayah-sumbar`. Suite **166 (164 lulus, 2 skip spasial)**. Belum merge.
Replikasi desain Google Stitch (MCP `stitch`, project "Portal Warga Digital" 5674614783916017095).
Iterasi 1 dari 4 (sisa: Daftar Modul · Materi · Kuis). Memori: [[stitch-redesign-plan]].
- **Token design-system penuh** → `resources/css/app.css` `@theme` (Tailwind v4): palet semantik
  Material-3 (primary `#3525cd`/primary-container `#4f46e5`/surface/on-* + 18 warna SDG), named
  spacing (`xs..xl`,`gutter`,`margin-mobile/desktop`), skala tipografi (`headline-lg/md/sm`,
  `body-md`,`label-md`,`metric-lg`). Class lama indigo/slate **tetap valid** (default v4 utuh) →
  migrasi halaman portal sisa bertahap.
- **Font Inter self-host**: `vite.config.js` `bunny('Instrument Sans')` → `bunny('Inter')`
  (bobot 400–800). Hapus link Google Fonts CDN (auto-inject via @vite, low-bandwidth PRD).
- **Layout `portal/layouts/app.blade.php`**: sidebar `bg-surface-container-low` + logo bulat primary
  + CTA "Mulai Belajar" + nav aktif `bg-primary-container/on-primary-container`; tambah nav
  **Peringkat** (leaderboard); bottom sidebar Profil+Keluar. Top header & bottom-nav di-retoken.
  Ikon Material Symbols Stitch → **Heroicons** (aturan #6).
- **Dashboard `portal/home.blade.php`**: welcome banner `bg-primary-container` + blur dekoratif +
  progress widget; 3 stat card (Modul Selesai sdg-4 / Poin sdg-7 / Peringkat sdg-10, `metric-lg`);
  grid 3-kol = **tabel Peringkat** (col-span-2, baris user `bg-primary-fixed`) + **Lanjutkan
  Belajar** (samping). "Aktivitas Terbaru" Stitch diganti "Lanjutkan Belajar" (data nyata; kolom
  Trend leaderboard dibuang—tak ada data).
- **Catatan teknis:** class Tailwind dinamis dirakit-string TAK ter-generate v4 → semua varian
  warna pakai literal penuh (array `tile`/match). `npm run build` wajib.

---

## Sesi 2026-06-22 (lanjutan 2) — Audit penamaan, konsolidasi migrasi, rename DesaUnit, 7 rekomendasi

Branch `feat/ref-wilayah-sumbar`. Suite **157 lulus, 2 skip** (159; +9 smoke). Belum merge.

- **Penamaan kolom → Indonesia** (kecuali standar Inggris: `id`/`*_id`/`*_at`/`slug`/`status`/`is_*`/kolom `users`). LMS & UMKM diseragamkan: `title→judul`, `description→deskripsi`, `content→konten`, `type→tipe`, `sort_order→urutan`, `question→pertanyaan`, `option_text→teks_opsi`, `score→nilai`, `passing_score→nilai_lulus`, `max_attempts→maks_percobaan`, `pages_completed→halaman_selesai`, `body→isi`, `source/source_id/amount→sumber/sumber_id/jumlah`, `rejection_reason→alasan_penolakan`, `view_count→jumlah_dilihat`, `video_url→url_video`, `file_path→path_file`, `estimated_minutes→estimasi_menit`, `prerequisite_module_id→prasyarat_module_id`. **Nama tabel/model tetap Inggris.** Memori: [[naming-convention-indonesian]].
- **Drop `desas.kode`** (redundan; `wilayah_kode` = kunci resmi). Tabel admin tampilkan `wilayah_kode`.
- **Konsolidasi migrasi 26→13 file** (per-domain): `create_wilayah_reference_tables` (ref_wilayah+boundaries), `create_jenis_wilayah_tables` (+seed jenis), `create_desa_tables` (desas+desa_units+FK users), `create_lms_module_tables`, `create_lms_quiz_tables`, `create_umkm_tables`. Migrasi drop (otp_expires_at, path) dilipat (kolom tak dibuat sejak awal). Lookup kecil (jenis, umkm_categories) di-seed di migrasi; wilayah/boundaries/super-admin di CoreSeeder.
- **Rename `wilayahs`→`desa_units`** (model `Wilayah`→`DesaUnit`), kolom `users.wilayah_id`→`desa_unit_id`, relasi `User::desaUnit()`/`Desa::desaUnits()`. Hilangkan tabrakan nama dgn `ref_wilayah`/`wilayah_boundaries`. Label UI tetap "Wilayah".
- **7 rekomendasi (semua dikerjakan):**
  - **CI** `.github/workflows/ci.yml` (pint --test + pest, PHP 8.4, sqlite) — sebelumnya tak ada CI.
  - **Trait `BelongsToDesa`** (`app/Models/Concerns`): relasi `desa()` + scope `forDesa()` di Module/UmkmProfile/XpLog/DesaUnit/User. **TANPA global scope** (super_admin lintas-desa).
  - **Smoke test** `SmokeTest.php` (9 halaman publik+portal warga, HTTP-level; peta/data dilewati — spasial sqlite).
  - **`quiz_attempts.status`**: hapus `InProgress` (auto-grade sinkron → langsung passed/failed).
  - **Konsistensi data referensi**: `jenis_desa`/`jenis_sub_unit` di-seed di migrasi (samakan `umkm_categories`), keluar dari CoreSeeder.
  - **Docs sync**: `docs/DATABASE.md` ditulis ulang akurat; `CLAUDE.md` drift diperbaiki (`nagari_id→desa_id`, role `nagari_admin→desa_admin`, umkm_owner=kapabilitas).
- **Verifikasi:** `migrate:fresh --seed` bersih (ref 1464, boundaries 1358, 2 desa, 7 modul); `pint --test` lulus.

---

## Sesi 2026-06-22 (lanjutan) — Form Desa diringkas (pilih desa) + akun admin + OTP tanpa expired

**Form Desa (Filament) dirombak.** Suite **145** (143 lulus, 2 skip spasial; verifikasi MariaDB terpisah).
- **Pilih desa, bukan ketik manual**: satu Select cari `wilayah_kode` (getSearchResultsUsing, label "Nama · Kec, Kab") → otomatis isi `nama`, `kode` (= **kode wilayah resmi**, mis. 13.71.01.1001), provinsi/kab/kec, **koordinat** (dari `wilayah_boundaries.lat/lng`). Dropdown bertingkat prov→kab→kec **dihapus**. **Dihapus** juga: input Kode internal, input koordinat manual, kontak desa. Tetap: Penyebutan wilayah (wajib), Sub-unit (opsional), Logo, Status. Validasi unik `wilayah_kode` (cegah desa dobel).
- **Akun admin desa di form Desa** (create & edit): field `admin_name` (opsional → fallback "Admin {desa}"), `admin_username` (wajib, unik, alpha_dash), `admin_kontak` (→ `users.phone`), `admin_otp` (opsional). Logika di `DesaResource::syncAdmin()`; Create = transaksi desa+admin (`handleRecordCreation`), Edit = `mutateFormDataBeforeFill`/`afterSave`. Relasi `Desa::desaAdmin()` (hasOne role=desa_admin). OTP ditampilkan via notifikasi persisten.
- **OTP dirombak (admin & warga)**: bisa **otomatis atau manual** (`issueOtp(?string $code)`), **tanpa kedaluwarsa** (kolom `otp_expires_at` di-drop; `OTP_TTL_DAYS`/`otpExpired()` dihapus; cek expired di `AuthController` dihapus). **Terhapus otomatis saat sandi diganti** via hook `User::saving` (kondisi: record lama + password dirty + bukan penerbitan OTP + `must_change_password` tak di-set eksplisit) → berlaku di portal & admin. `UserForm`/`CreateUser` warga kini OTP opsional (kosong=auto).
- **Wajib ganti sandi admin**: panel `/admin` kini `->profile()` aktif + middleware `EnsureAdminPasswordChanged` (di `authMiddleware`) alihkan admin ber-`must_change_password` ke `/admin/profile` sampai sandi diganti (lewatkan route profile/logout/livewire). Ganti sandi → OTP terhapus (hook). Test `AdminPasswordChangeTest`.
- Dihapus (redundan): `WilayahLookup` + test + tombol ST_Contains di form (alur baru tak perlu — koordinat dari pilihan desa). Spatial index `wilayah_boundaries.geom` dibiarkan (infra, potensi guna).

---

## Sesi 2026-06-22 — Geometri batas wilayah spasial + peta drill-down (branch `feat/ref-wilayah-sumbar`)

**Opsi B SELESAI** (batas desa). Sumber: dump cahyadsn/wilayah_boundaries (22 berkas `.sql`, prov/kab/kec/desa) → dipindah ke `database/data/boundaries/`. Suite **145** (143 lulus, 2 skip di SQLite; ketiga test peta diverifikasi lulus di MariaDB via DB test terpisah).
- **Tabel spasial `wilayah_boundaries`** (migrasi baru): `kode` PK, level, parent_kode, nama, lat/lng, **`geom` GEOMETRY NOT NULL + SPATIAL INDEX**, `geom_simplified` (nullable). Dipisah dari `ref_wilayah` agar tabel referensi tetap ringan → **`ref_wilayah.path` DIHAPUS** (migrasi drop; sumber tunggal geometri kini tabel ini). Spatial index di-guard hanya MySQL/MariaDB (SQLite test tak dukung).
- **DB = MariaDB 10.11**: `ST_AsGeoJSON` & `ST_Contains` ADA (point-in-polygon siap utk auto-deteksi desa dari koordinat UMKM nanti); **`ST_Simplify` TIDAK ada** → simplifikasi via Douglas–Peucker di importer (saat impor).
- **ETL** `app/Services/WilayahBoundaryImporter.php` (+command `wilayah:import-boundaries` +`WilayahBoundarySeeder`, dipanggil CoreSeeder): muat `.sql` ke staging (engine DB yg parse, bukan regex—regex lolos 106 baris), `path` JSON `[lat,lng]` (kedalaman 2–4 tak konsisten) → WKT MULTIPOLYGON `[lng,lat]` → `ST_GeomFromText(.,4326)` + `geom_simplified` (DP per-level). **1.358 tersimpan, 0 gagal**. Selisih dari 1.464: **106 desa memang tak ada di dataset cahyadsn** (kekosongan sumber, bukan bug) → level: prov 1, kab 19, kec 179, desa 1.159.
- **Peta drill-down**: `PublicMapController` refactor → query `ST_AsGeoJSON(COALESCE(geom_simplified,geom),5)` (presisi ~1 m), parser manual `toMultiPolygon` DIHAPUS. Endpoint: tanpa param → kab/kota (78KB); `?kab=13.01` → desa dalam kab (mis. Padang 104 desa ~99KB). Front-end `peta.blade.php`: klik kab → muat desa (lazy per-kab) + tombol "kembali" + tooltip kab/popup desa; desa terdaftar ditandai hijau.

---

## Sesi 2026-06-21 (lanjutan) — Referensi wilayah resmi Sumbar (branch `feat/ref-wilayah-sumbar`, belum merge)

Manfaatkan dump Kepmendagri (`wilayah.sql` + `wilayah_level_1_2.sql`, **tak masuk repo**). Suite **144 hijau**. **Belum merge / belum selesai** (lihat "Lanjut berikutnya").
- **`ref_wilayah`** (datar: kode/nama/level/parent_kode + geo lat/lng/luas/penduduk/path utk prov & kab). Model `RefWilayah` (scope `level`/`childrenOf`, `logoUrl`). Data **Sumbar saja** (kode `13`): 1 prov + 19 kab + 179 kec + 1.265 desa = 1.464 baris → diekstrak ke `database/data/sumbar_wilayah.csv` + `_geo.json` (20 geo), diimpor `WilayahSumbarSeeder` (dipanggil CoreSeeder, idempotent). Provinsi lain menyusul = sediakan lagi dump + tambah berkas + perluas seeder.
- **`desas.wilayah_kode`** FK opsional → ref_wilayah. **DesaForm**: dropdown bertingkat Provinsi→Kab→Kec→Desa (helper prov/kab/kec `dehydrated(false)`, hidrasi balik dari wilayah_kode saat edit); auto-isi nama + provinsi/kab/kec (denormalized). **jenis_desa & jenis_sub_unit tetap manual** (bukan dari kode).
- **Logo kab/kota**: 19 logo Sumbar → `public/images/wilayah/{kode}.png` (+thumbs); `Desa::kabupatenLogoUrl()` diturunkan dari wilayah_kode (media `logo_kabupaten` per-desa **dihapus**). Ditampilkan: kolom ImageColumn di DesasTable + di halaman UMKM publik (show) samping nama desa.
- **Peta publik `/peta` SELESAI + teroptimasi**: Leaflet (CDN). Endpoint `public.peta.data` kini **GeoJSON FeatureCollection** (geometry MultiPolygon, koordinat `[lng,lat]`); normalisasi ring tak-konsisten + tutup ring **sekali di server** (`PublicMapController::toMultiPolygon`). Pisah cache (geometri 1 hari + hitungan desa per request) + `Cache-Control: public, max-age=300`. Klien pakai `L.geoJSON` + status muat/error. Choropleth jumlah desa terdaftar; popup nama/ibukota/luas/penduduk/logo. Link nav + hero. Test `PublicMapTest`.

**⚠️ Batas data peta (penting):** boundaries **hanya s/d kab/kota**. `wilayah_level_1_2.sql` = polygon level 1–2 saja; `wilayah.sql` = kode+nama semua level **tanpa koordinat/polygon**. Di DB: hanya level 1 (1) & 2 (19) punya `path`; kecamatan (179) & desa (1.265) **tak punya** geometri/koordinat.

**Lanjut berikutnya (belum dikerjakan, menunggu keputusan):** peta level desa —
- **Opsi A** (bisa langsung): tandai desa terdaftar sebagai **marker titik** pakai `desas.koordinat_lat/lng` (diisi admin; demo masih kosong).
- **Opsi B** (butuh data): impor **GeoJSON batas desa** Sumbar (1.265 poligon) dari sumber ke-3 (BPS/Ina-Geoportal/OSM) — file belum ada.
Opsional lain: provinsi selain Sumbar (butuh dump lagi); halaman profil desa publik tersendiri.

---

## Sesi 2026-06-21 — Refactor besar: nagari→desa, backed enums, jenis tabel (branch `feat/penyebutan-wilayah-nasional`)

Tiga fase, tiap fase di-commit terpisah & suite hijau (138 test):
1. **Rename `nagari` → `desa`** (commit 3feaa3d): istilah Sumbar diganti netral nasional sebagai
   nama internal entitas tenant; tampilan tetap dari `jenis`. Tabel `nagaris`→`desas`, kolom
   `nagari_id`→`desa_id` (users/modules/umkm_profiles/xp_logs/wilayahs), model `Nagari`→`Desa`,
   relasi `desa()`. **Role `nagari_admin`→`desa_admin`**, helper `isDesaAdmin()`. Filament
   Resources/Desas, PengaturanDesa. Bukan Filament Tenancy (scoping manual).
2. **Kolom status/type → PHP backed enum** (commit ec80d4e): 8 kolom `enum()` DB → `string` + cast
   ke `app/Enums/` (ModuleStatus, ModuleProgressStatus, QuizAttemptStatus, UmkmProductStatus,
   ModulePageType, ActiveStatus). Implement HasLabel/HasColor/HasIcon → badge/Select Filament
   digerakkan enum (closure `fn(string $state)` & map manual dihapus). Tambah nilai tanpa ALTER.
   Catatan: state **kolom tabel** = objek enum; state **form** Livewire = string value.
3. **`jenis` & sub-unit → tabel referensi** (commit e2cd6ed): tabel global `jenis_desa` &
   `jenis_sub_unit` (nama/urutan/aktif), diseed di **CoreSeeder**. `desas.jenis`→FK `jenis_desa_id`
   (restrictOnDelete), `desas.wilayah_label`→FK `jenis_sub_unit_id`. Relasi `Desa::jenisDesa/jenisSubUnit`;
   `namaLengkap`/`subUnitLabel` via relasi. Const JENIS/SUB_UNIT dihapus.

**Audit penuh pasca-refactor (model→controller→Filament→views), suite 138→141 hijau, 6 commit:**
- **Model** (24f5ef0): bersih (verifikasi DB live). +cast eksplisit `QuizAttempt::score`, `XpLog::source_id/amount` +relasi `XpLog::desa()`.
- **Controller/Service** (a6ec65f): **BUG nyata diperbaiki** — `LmsProgressService::getModuleStatus[Using]` mem-`match` `$progress->status` (enum) lawan string → badge status modul di beranda/daftar **selalu "Belum Dimulai"** (lolos 138 test). Kini match enum case. +regresi `LmsProgressStatusTest`.
- **Konsistensi enum** (4979793, 72fc25d): klausa `where()/whereIn()`/set status di controller/service/Livewire/Filament/widget pakai backed enum (query builder ubah enum→value). `UmkmService::verifyProduct` & `setStatus` jadi `UmkmProductStatus`-typed. **Sengaja string**: `->default()` & rule `$value==='published'` di form (state form Livewire = string); param-boundary; `priorityOrder`/`resultStatus` (status UI, bukan enum DB).
- **Filament**: tenancy `desa_id` utuh & benar (`scopeToActor`/`scopeToDesa`, `isDesaAdmin`).
- **Views** (1dfb8dd): bersih (nol "nagari", semua atribut enum via `->value`). **N+1 diperbaiki**: `nama_lengkap` kini baca relasi `jenisDesa` → eager-load `umkmProfile.desa.jenisDesa` (katalog) & `jenisDesa` (DesaResource).
- Catatan minor pra-refactor (tak diubah): `ActivityLogsTable` filter `log_name` kurang opsi diskusi/produk/umkm/wilayah.

Belum merge ke main.

---

## Status

**Fase**: MVP. LMS + UMKM (admin/portal/publik) + dashboard admin lengkap. Branch aktif
**`feat/ref-wilayah-sumbar`** (belum merge ke main, **sudah di-push** s/d `29dfb3f`): master wilayah +
peta, design system NCH, refactor 3-lapisan `penduduk`, **impor warga Excel**, **konsistensi form admin**,
**Pengaturan Desa + peta**, dan **navigasi per-peran + drill-in Warga** (super admin "Kelola Warga" per
desa lewat `DesaContext`/`managedDesaId`). Suite **202 (200 lulus, 2 skip)**, Pint bersih.
**Login demo** (jalankan `php artisan migrate:fresh --seed`):
- super_admin: email `admin@basamo.nch` (username `superadmin`) / `password`
- desa_admin: `admin.nch001@basamo.nch` & `admin.nch002@basamo.nch` / `password`
- warga (portal): login **NIK** mis. `3201000000000101` / `password` (16 warga, 2 desa)
- Catatan: warga demo `must_change_password=false` agar bisa langsung login showcase.

---

## ⏭️ BERIKUTNYA (sesi baru — setelah 2026-06-24)
> Konfirmasi arah dulu ke user, lalu kerjakan. Branch sudah di-push (s/d `29dfb3f`); tak ada git tertunda.

> Kandidat lanjutan (urut saran):
1. **Cek browser** alur drill-in: Desa → "Kelola Warga" → halaman Warga desa (Buat/Ubah/Reset OTP/Impor)
   → "Kembali ke Desa". Juga peta Pengaturan Desa (Leaflet CDN).
2. **(Opsional) PR `feat/ref-wilayah-sumbar` → main** bila sudah mantap (banyak fitur menumpuk di branch).
3. **Drill-in lanjutan super admin** (ditunda sengaja): UMKM / Wilayah(sub-unit) / Modul per-desa — pola
   sama seperti Warga (`managedDesaId` + aksi di tabel Desa). Tanyakan dulu mana yang diprioritaskan.
4. **Port halaman portal warga** (modul/materi/kuis/leaderboard) ke token design NCH (sisa redesign).
5. **Pilar SDGs (M3)** atau **IoT (M5.3)** — dikerjakan programmer lain (lihat memori scope).

> Catatan teknis untuk agent berikut:
- **Desa-konteks** super admin = `App\Support\DesaContext` (session) + `User::managedDesaId()`. Halaman Warga
  super admin **hanya** lewat aksi "Kelola Warga" (tanpa konteks → `UserResource::canAccess()` false).
- **LMS disembunyikan** dari admin desa via `shouldRegisterNavigation` (super-only) — bukan dihapus; mudah
  dibalik bila admin desa perlu LMS lagi.
- Verifikasi tampilan (screenshot panel) tidak andal otomatis — minta user cek browser.

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

## Penyebutan wilayah administratif nasional (2026-06-20) — branch `feat/penyebutan-wilayah-nasional`
Dukungan multi-istilah administratif Indonesia (skala nasional). Suite **hijau (138)**.
- **`nagaris.jenis`** (baru, wajib): penyebutan setingkat desa (Desa/Kelurahan/Nagari/Gampong/
  Kampung/Kalurahan/Lembang/Pekon/Tiyuh/Negeri/Nagori/Huta) — string+dropdown, dipilih super_admin
  & melekat. `wilayah_label` jadi **nullable** (sebutan sub-unit, diatur admin nagari).
- Accessor `Nagari::namaLengkap` → "{jenis} {nama}" dipakai di landing/katalog/portal/tabel admin.
  `subUnitLabel()` fallback **"Sub-Unit Wilayah"**. Daftar pilihan = konstanta `Nagari::JENIS`/`SUB_UNIT`.
- **NagariForm (super_admin):** Select `jenis` wajib + `wilayah_label` opsional. NagarisTable +kolom jenis.
- **Halaman baru "Pengaturan Nagari" (admin nagari, grup Pengaturan):** self-service sebutan sub-unit,
  logo desa, kontak, koordinat — terikat nagarinya sendiri. super_admin tetap punya kendali penuh via
  NagariResource. `nama` demo/factory tak lagi berawalan "Nagari" (jenis terpisah).
- Validasi istilah via riset web (UU Desa / ragam sebutan desa). Test: `NagariPenyebutanTest`.
- Migrasi diedit langsung di file konsolidasi `create_nagaris` (bukan alter baru) → `migrate:fresh`.

## Keputusan teknis aktif (detail di DECISIONS.md)
- Kuis MC-only; nilai angka 0–100 (bukan %); tanpa bobot poin per soal.
- Scoping nagari admin manual (bukan Filament Tenancy). super_admin kelola global.
- Leaderboard berbasis XP pencapaian (sempat dihapus, lalu dihidupkan lagi).
- UI: komponen Blade sendiri (bukan Flux/WireUI) + canvas-confetti.
- PDF materi: disk `public` + symlink; PHP `upload_max_filesize=10M`/`post_max_size=12M` (set di server; lihat DECISIONS).
- Filament v5: `Schema $schema`; shield define_via_gate; super_admin via Gate::before.
- RBAC: role = persona (super_admin/nagari_admin/warga). Akses UMKM = kapabilitas (`users.umkm_access_granted_at`, `hasUmkmAccess()`), bukan role. Kategori UMKM = tabel `umkm_categories`.
