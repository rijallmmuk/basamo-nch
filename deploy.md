# Runbook Production Basamo NCH di Hostinger

Dokumen ini adalah checklist deployment untuk `basamonch.com` dan seluruh situs
`{slug}.basamonch.com`. Jalankan perintah dari **folder akar aplikasi** yang berisi
`artisan`. Jangan membuka trafik sebelum `php artisan ops:production-check` lulus.

## 1. Informasi yang harus disiapkan

Catat sebelum mulai:

- path absolut aplikasi di hosting;
- nama database, user database, password, host, dan port;
- daftar slug nagari yang akan diberi subdomain;
- DSN proyek Sentry untuk pemantauan error;
- password arsip backup yang kuat dan tempat penyimpanannya di password manager;
- akses hPanel untuk domain, SSL, cron, PHP, dan database.

Jangan menyalin rahasia ke tiket, chat, Git, atau screenshot. `APP_KEY` yang sudah
dipakai **tidak boleh diganti**, karena data/cookie terenkripsi lama dapat gagal
dibaca. Untuk instalasi yang benar-benar baru, hasilkan satu key dengan
`php artisan key:generate --show`, lalu simpan hasilnya sebagai `APP_KEY` di
`.env.production` sebelum berkas itu diunggah.

## 2. Setup satu kali di hPanel

### PHP dan database

- Pilih PHP **8.5** untuk web dan CLI. Proyek memakai Laravel 13 dan Composer
  mengunci PHP 8.5.
- Aktifkan ekstensi: bcmath, ctype, curl, dom/xml, fileinfo, gd, intl, mbstring,
  openssl, pdo_mysql, tokenizer, dan zip.
- Set `memory_limit` minimal 512M.
- Set `upload_max_filesize` dan `post_max_size` minimal 1024M agar materi sampai
  1 GB tidak ditolak PHP sebelum mencapai validasi aplikasi.
- Buat database serta user MySQL/MariaDB, lalu berikan hak akses ke database itu.
- Pastikan SSH, cron, dan pembuatan symbolic link tersedia.

Verifikasi versi dan modul dari SSH:

```bash
php -v
php -m
composer --version
```

### Domain, subdomain, dan SSL

Domain utama dan `www.basamonch.com` harus menunjuk ke aplikasi yang sama. Untuk
setiap nagari, buat subdomain **sesuai persis** dengan `nagaris.slug`, misalnya
`pangian.basamonch.com`.

Pada paket Hostinger Web/Cloud, lakukan untuk setiap subdomain nagari:

1. Buat subdomain secara individual di hPanel.
2. Arahkan ke aplikasi/document root yang sama dengan domain utama.
3. Aktifkan SSL individual dan tunggu sampai HTTPS valid.
4. Uji URL setelah DNS dan SSL aktif.

Jangan membuat instalasi Laravel terpisah per nagari. Jangan gunakan slug yang
dicadangkan seperti `www`, `mail`, `panel`, `admin`, atau `api`. Wildcard DNS saja
tidak otomatis membuat hosted subdomain maupun sertifikatnya di Web/Cloud.

### Web root dan `.htaccess`

Pilihan terbaik adalah document root domain utama dan seluruh subdomain langsung
ke `<root-aplikasi>/public`. Apache kemudian memakai `public/.htaccess`, sedangkan
kode, `.env`, dan storage tetap di luar web root.

Jika paket hosting memaksa proyek berada di `public_html`, unggah proyek utuh dan
pertahankan dua berkas ini:

- `/.htaccess` meneruskan request ke `/public` dan melindungi akar proyek;
- `/public/.htaccess` meneruskan route Laravel ke `public/index.php`.

Jangan memindahkan `public/index.php` atau mengubah path bootstrap secara manual.

## 3. Konfigurasi `.env.production`

`.env.production` sengaja tidak masuk Git dan harus diunggah melalui jalur aman.
Lengkapi seluruh rahasia database, `APP_KEY`, backup, dan Sentry. Nilai produksi
minimum berikut wajib benar:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://basamonch.com
PUBLIC_BASE_DOMAIN=basamonch.com

SESSION_DOMAIN=.basamonch.com
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

MAIL_ENABLED=false
MAIL_MAILER=log

REVERSE_PROXY_ENABLED=false
HEALTH_CHECKS_ENABLED=true
SENTRY_LARAVEL_DSN=<dsn-proyek-sentry>
BACKUP_ARCHIVE_PASSWORD=<password-arsip-yang-kuat>
```

`SESSION_DOMAIN` pada domain induk memungkinkan login resmi tetap hidup ketika
pengguna yang berwenang berpindah dari subdomain ke domain utama. Jika kelak
memakai Cloudflare/CDN/reverse proxy, ubah `REVERSE_PROXY_ENABLED=true` dan isi
`TRUSTED_PROXIES` dengan IP/CIDR resmi proxy. Untuk koneksi Hostinger langsung,
biarkan keduanya seperti konfigurasi saat ini.

Email saat ini tidak dipakai. Notifikasi warga tetap disimpan di database dan
ditampilkan di aplikasi; notifikasi backup via email juga dinonaktifkan. Jangan
mengisi konfigurasi SMTP sampai fitur email memang diaktifkan dan diuji.

Laravel tidak otomatis membaca `.env.production`. Pada server, salin menjadi
runtime `.env`, kemudian lindungi keduanya:

```bash
cp .env.production .env
chmod 600 .env .env.production
```

Sesudah ini semua command produksi dijalankan **tanpa** `--env=production` supaya
yang diperiksa sama dengan konfigurasi yang dipakai web dan cron.

## 4. Persiapan setiap release di komputer pengembangan

Jalankan sebelum upload:

```bash
composer validate --no-check-publish
php artisan test
npm ci
npm run build
```

Pastikan release memuat kode terbaru, `composer.lock`, `package-lock.json`, dua
`.htaccess`, dan hasil build `public/build`. Aset production ini dilacak Git agar
clean clone siap dilayani tanpa membutuhkan Node.js di server. Jangan unggah
`.env` lokal, database lokal, log, atau `node_modules`.

## 5. Instalasi pertama di server

Clone kode dan pulihkan `.env.production`, lalu masuk ke folder aplikasi.
Pastikan `pwd` menampilkan lokasi yang benar dan `artisan` ada:

```bash
pwd
test -f artisan
cp .env.production .env
chmod 600 .env .env.production
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
chmod -R u+rwX,g+rwX storage bootstrap/cache
php artisan optimize:clear
php artisan migrate:status
php artisan migrate --force
php artisan db:seed --class=CoreSeeder --force
php artisan storage:link
php artisan filament:assets
php artisan optimize
```

`CoreSeeder` mengisi role/hak akses, data master, referensi wilayah, SDGs, perangkat
EWS awal, dan akun superadmin secara idempoten. Pada instalasi pertama produksi,
command mencetak sandi acak superadmin **sekali saja**. Catat langsung di password
manager, login dengan username `superadmin`, lalu ganti sandi pada login pertama.

Pastikan `public/storage` adalah symlink, bukan salinan folder. Git hanya membawa
kode, **bukan berkas unggahan**. Jika instalasi memakai database yang sudah berisi
data, pulihkan pasangan backup yang sama untuk kedua folder berikut sebelum situs
dibuka:

```text
storage/app/public   # sampul Nagari, UMKM, dan media Media Library
storage/app/private  # PDF, gambar, audio, dan lampiran materi SLC
```

Database tanpa `storage/app/private` yang cocok akan membuat materi masih tercatat
tetapi file-nya 404 dan field unggah tampak kosong. Jangan menghapus, mengganti,
atau membuat ulang `storage/app/private` saat deploy.

## 6. Cron, queue, scheduler, dan backup

Cari binary PHP CLI yang benar:

```bash
command -v php
```

Buat **satu cron setiap menit** di hPanel dengan path absolut hasil pemeriksaan di
atas dan path absolut aplikasi, contohnya:

```text
/usr/bin/php /home/USER/path-ke-aplikasi/artisan schedule:run
```

Jangan menambahkan `>/dev/null 2>&1` pada kolom PHP cron hPanel. Scheduler aplikasi
sudah menangani worker queue pendek setiap menit, perekaman EWS, refresh SDGs/IDM,
retensi, pemantauan antrean, backup, dan verifikasi backup. Jangan membuat cron
terpisah untuk setiap task tersebut.

Setelah cron dibuat, verifikasi jadwal dan jalankan satu siklus manual:

```bash
php artisan schedule:list
php artisan schedule:run -v
php artisan queue:failed
```

Backup saat ini berada pada disk lokal hosting (`backup_local`). Buat dan validasi
backup pertama:

```bash
php artisan backup:run --disable-notifications
php artisan backup:list
php artisan backup:monitor
php artisan ops:verify-backup
```

Unduh/salin arsip secara berkala ke lokasi di luar akun Hostinger. Backup yang
hanya berada di akun hosting yang sama tidak cukup untuk pemulihan bencana.

## 7. Gerbang verifikasi sebelum trafik dibuka

Jalankan dari server menggunakan `.env` runtime:

```bash
php artisan ops:production-check
php artisan migrate:status
php artisan route:list
php artisan schedule:list
php artisan ops:check-media
php artisan queue:failed
php artisan backup:list
php artisan backup:monitor
php artisan ops:verify-backup
```

`ops:production-check` harus berakhir dengan **Semua production preflight lulus**.
Tes endpoint dari server dan browser:

```bash
curl --fail --show-error --location https://basamonch.com/up
curl --fail --show-error --location https://basamonch.com
curl --fail --show-error --location https://SLUG-NAGARI.basamonch.com
```

Ganti `SLUG-NAGARI` dengan slug nyata. Lalu lakukan smoke test manual:

1. Domain utama, `www`, dan minimal dua subdomain berbeda tampil melalui HTTPS.
2. Konten, UMKM, pelatihan, statistik, dan IoT tidak bercampur antar-nagari.
3. Warga hanya dapat masuk ke subdomain nagarinya sendiri.
4. Superadmin, DPMD, operator, dan pengajar mengikuti batas akses masing-masing;
   perpindahan yang sah ke domain utama tidak menghilangkan sesi.
5. Buat pelatihan, modul, materi privat, pre-test, evaluasi, dan balasan forum;
   pastikan alur pengajar sampai warga dapat dibaca dan diselesaikan.
6. Unggah satu gambar publik dan satu materi privat, lalu jalankan kembali
   `php artisan ops:check-media`.
7. Picu satu notifikasi database/queued job, jalankan cron, dan pastikan antrean
   terkuras serta `php artisan queue:failed` kosong.
8. Pastikan Sentry menerima satu event uji yang terkendali tanpa data pribadi.
9. Verifikasi backup terbaru dan uji pemulihan pada database staging, bukan database
   production aktif.
10. Pastikan endpoint SEO merespons dan berisi hostname yang benar:

    ```bash
    test ! -f public/robots.txt
    curl --fail --show-error https://basamonch.com/robots.txt
    curl --fail --show-error https://basamonch.com/sitemap.xml
    curl --fail --show-error https://SLUG-NAGARI.basamonch.com/robots.txt
    curl --fail --show-error https://SLUG-NAGARI.basamonch.com/sitemap.xml
    ```

    `public/robots.txt` memang harus tidak ada karena robots dibuat dinamis untuk
    setiap hostname. Berkas statis lama akan mengalahkan route Laravel di Apache.

### Google Search Console setelah domain aktif

1. Tambahkan **Domain property** `basamonch.com` dan verifikasi melalui DNS; properti
   domain mencakup domain utama serta seluruh subdomain nagari.
2. Kirim `https://basamonch.com/sitemap.xml` dan sitemap setiap subdomain aktif,
   misalnya `https://pangian.basamonch.com/sitemap.xml`.
3. Gunakan URL Inspection untuk beranda utama, satu Teras Nagari, satu pelatihan,
   satu etalase UMKM, dan satu produk; pastikan canonical pilihan Google sesuai.
4. Validasi halaman produk, etalase, dan pelatihan dengan Rich Results Test.
5. Pantau laporan Page Indexing, HTTPS, Core Web Vitals, serta Enhancements setelah
   Google mulai merayapi situs. Sitemap membantu penemuan URL, tetapi tidak menjamin
   semua URL akan langsung atau selalu diindeks.

## 8. Deploy pembaruan berikutnya

Sebelum mengganti kode, buat backup dari versi yang masih berjalan:

```bash
php artisan ops:check-media
php artisan backup:run --disable-notifications
php artisan ops:verify-backup
php artisan down --with-secret
```

Backup pra-deploy wajib memuat database serta `storage/app/public` dan
`storage/app/private` dari keadaan yang sama. Jangan memakai `git clean -fdx`,
menimpa folder `storage`, atau mengganti checkout tanpa menyalin kembali storage
lama. Kode baru tidak boleh dijadikan alasan untuk menyalin folder `storage`
kosong ke production. Jika pemeriksaan awal gagal,
hentikan deploy dan pulihkan file terlebih dahulu agar backup baru tidak
mengabadikan keadaan yang sudah rusak.

Simpan URL bypass rahasia yang dicetak command maintenance. Setelah
`git pull --ff-only`, jalankan:

```bash
cp .env.production .env
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
chmod 600 .env .env.production
chmod -R u+rwX,g+rwX storage bootstrap/cache
php artisan optimize:clear
php artisan migrate:status
php artisan migrate --force
php artisan storage:link
php artisan filament:assets
php artisan optimize
php artisan queue:restart
php artisan ops:production-check
php artisan ops:check-media
php artisan up
```

`ops:check-media` memeriksa seluruh media publik **dan seluruh referensi file
materi privat** tanpa memuat semuanya sekaligus ke memori. Jika hasilnya gagal,
jangan jalankan `up`, jangan simpan ulang form materi yang tampak kosong, dan
jangan menghapus referensi database. Pulihkan
`storage/app/private` dari backup pra-deploy yang cocok, lalu ulangi pemeriksaan.
Jika backup fisik memang tidak ada, unggah ulang adalah pemulihan terakhir—kode
tidak dapat menciptakan kembali isi file yang sudah hilang.

Jalankan seluruh pemeriksaan pada bagian 7 setelah `up`. Jangan menjalankan seeder
pada deploy rutin kecuali release secara khusus menginstruksikannya. `CoreSeeder`
boleh diulang karena idempoten, tetapi bukan langkah wajib setiap update.

### Tugas satu kali: sampul etalase UMKM 3:1

Release yang mengubah sampul etalase menjadi banner 3:1 perlu membuat ulang
konversi `hero` untuk foto yang sudah tersimpan. Jalankan sekali setelah kode baru
terpasang; unggahan baru sudah dipotong otomatis menjadi 1800 × 600 px.

```bash
php artisan media-library:regenerate 'App\Models\UmkmProfile' --only=hero --force
```

## 9. Menambahkan nagari/subdomain baru

Urutan wajib:

1. Buat data nagari melalui panel dan pastikan slug finalnya benar.
2. Pastikan slug tidak termasuk nama subdomain yang dicadangkan.
3. Buat `{slug}.basamonch.com` di hPanel.
4. Arahkan ke document root aplikasi yang sama.
5. Aktifkan SSL untuk subdomain itu.
6. Buka beranda, Teras Nagari, Medan Nan Balinduang, Lapau Nagari, serta IoT.
7. Uji login warga nagari tersebut dan penolakan akses lintas nagari.

Tidak ada command migrate, instalasi Laravel, atau cron baru untuk satu nagari.

## 10. Penanganan masalah dan rollback

Jika terjadi error 500, tetap aktifkan maintenance mode dan periksa:

```bash
php artisan about
php artisan ops:production-check
php artisan migrate:status
php artisan queue:failed
tail -n 200 storage/logs/laravel.log
```

Periksa versi PHP CLI, ekstensi, permission `storage`/`bootstrap/cache`, keberadaan
`public/build/manifest.json`, symlink `public/storage`, kredensial database, dan
config cache. Setelah memperbaiki `.env`, selalu jalankan:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Untuk rollback, jangan menebak dengan `migrate:rollback`. Pertahankan maintenance
mode, kembalikan kode serta `public/build` ke release terakhir yang diketahui baik,
dan pulihkan database dari backup pra-deploy bila migrasi baru telah mengubah data.
Pemulihan database dilakukan melalui tool restore hPanel/MySQL yang terkontrol dan
lebih dulu diuji pada staging. Setelah itu jalankan:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan storage:link
php artisan filament:assets
php artisan optimize
php artisan queue:restart
php artisan ops:production-check
php artisan up
```

### Larangan di production

Jangan pernah menjalankan command berikut pada database production aktif:

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
php artisan key:generate
php artisan queue:clear
php artisan queue:flush
```

Jangan memakai permission `777`, jangan menyalakan `APP_DEBUG`, jangan mengganti
`APP_KEY`, dan jangan membuka trafik jika preflight, migrasi, media, antrean, SSL,
atau backup belum lolos pemeriksaan.

## 11. Checklist selesai

- [ ] PHP web dan CLI 8.5 beserta seluruh ekstensi tersedia.
- [ ] Database production dan kredensial `.env` valid.
- [ ] `APP_KEY`, password backup, serta DSN Sentry tersimpan aman.
- [ ] `.env.production` disalin ke `.env`; keduanya permission 600.
- [ ] Domain utama, `www`, dan seluruh subdomain memakai aplikasi yang sama.
- [ ] SSL aktif untuk setiap hostname.
- [ ] `public/build`, `vendor`, dan `public/storage` tersedia dengan benar.
- [ ] Migrasi selesai; `CoreSeeder` dijalankan pada instalasi pertama.
- [ ] Cron satu-menit berjalan dan queue tidak menumpuk.
- [ ] Backup dibuat, diverifikasi, disalin off-site, dan restore staging diuji.
- [ ] `ops:production-check` lulus tanpa pengecualian.
- [ ] Smoke test domain, hak akses, SLC, forum, media, IoT, dan lintas nagari lulus.
- [ ] Aplikasi dikeluarkan dari maintenance mode hanya setelah semua pemeriksaan lulus.
