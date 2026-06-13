# INSTALL & SETUP — basamo-nch (dari nol)
# Status: Laravel bersih + MySQL sudah terpasang. Filament & dependency BELUM.
# Fokus: LMS dulu, satu panel /admin untuk fondasi data (modul, materi, kuis).
#
# KEPUTUSAN FINAL (jangan diubah):
# - TANPA Jetstream → auth Filament bawaan, 1 model User
# - Editor modul: RichEditor BAWAAN Filament (Tiptap tidak support v5)
# - ApexCharts: leandrocfe/filament-apex-charts
# - Tailwind: ikut bawaan Filament (tidak setup terpisah dulu)
# - Notifikasi: Filament Notifications bawaan
# - Panel: mulai /admin dulu, /portal menyusul
# - Jalankan BLOK PER BLOK, tunggu selesai

# ============================================================
# BLOK 1 — INSTALL FILAMENT (panel admin)
# ============================================================

composer require filament/filament -W
php artisan filament:install --panels

# Saat ditanya ID panel → ketik: admin → Enter
# Ini otomatis membuat app/Providers/Filament/AdminPanelProvider.php
# dan setup Tailwind + Vite bawaan Filament


# ============================================================
# BLOK 2 — RBAC: SHIELD + SPATIE PERMISSION
# ============================================================

composer require bezhansalleh/filament-shield
composer require spatie/laravel-permission


# ============================================================
# BLOK 3 — MEDIA & FILE (untuk thumbnail modul, nanti foto UMKM/dok SDGs)
# ============================================================

composer require spatie/laravel-medialibrary
composer require filament/spatie-laravel-media-library-plugin
# intervention/image ikut otomatis via medialibrary


# ============================================================
# BLOK 4 — UTILITAS LMS
# ============================================================

composer require spatie/laravel-sluggable      # slug URL modul
composer require spatie/laravel-activitylog     # audit trail


# ============================================================
# BLOK 5 — GRAFIK DASHBOARD (untuk statistik LMS nanti)
# ============================================================

composer require leandrocfe/filament-apex-charts


# ============================================================
# BLOK 6 — DEV TOOLS
# ============================================================

composer require laravel/pint --dev
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev


# ============================================================
# BLOK 7 — KONFIGURASI DATABASE
# Edit file .env:
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=basamo_nch
#   DB_USERNAME=root        (sesuaikan)
#   DB_PASSWORD=            (sesuaikan)
# Buat database kosong dulu:
# ============================================================

mysql -u root -e "CREATE DATABASE IF NOT EXISTS basamo_nch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"


# ============================================================
# BLOK 8 — SETUP SHIELD + PUBLISH MIGRATION
# ============================================================

php artisan vendor:publish --tag="filament-shield-config"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"


# ============================================================
# BLOK 9 — EDIT MODEL User (manual, lihat catatan)
# Tambahkan sebelum migrate:
# ============================================================
# File: app/Models/User.php
#
#   use Filament\Models\Contracts\FilamentUser;
#   use Filament\Panel;
#   use Spatie\Permission\Traits\HasRoles;
#
#   class User extends Authenticatable implements FilamentUser
#   {
#       use HasRoles;
#
#       public function canAccessPanel(Panel $panel): bool
#       {
#           // Sementara fokus admin: izinkan super_admin & nagari_admin
#           return in_array($this->role, ['super_admin', 'nagari_admin']);
#       }
#   }
#
# Juga tambah kolom 'role' & 'nagari_id' di migration users (lihat DATABASE.md)


# ============================================================
# BLOK 10 — MIGRATE
# ============================================================

php artisan migrate


# ============================================================
# BLOK 11 — BUAT SUPER ADMIN + GENERATE PERMISSION
# ============================================================

php artisan make:filament-user
php artisan shield:generate --all


# ============================================================
# BLOK 12 — COMPILE & JALANKAN
# Dua terminal terpisah:
# ============================================================

npm install
npm run dev          # terminal 1 (hot reload)
php artisan serve    # terminal 2


# ============================================================
# VERIFIKASI
# ============================================================
# Admin panel → http://localhost:8000/admin
# Login dengan akun super admin yang dibuat di BLOK 11
# ============================================================


# ============================================================
# CATATAN VERSI COMMAND FILAMENT v5
# Jika ada command yang tidak dikenali, cek daftar yang tersedia:
#   php artisan list | grep filament
#   php artisan list | grep shield
# Konsep tetap: 1 panel admin, RBAC via shield, auth Filament bawaan.
# ============================================================
