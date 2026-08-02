<?php

namespace App\Services;

use App\Filament\Resources\Modules\RelationManagers\MaterisRelationManager;
use Illuminate\Support\Str;
use RuntimeException;

class ProductionReadinessService
{
    /**
     * @return list<array{label: string, passed: bool, message: string}>
     */
    public function checks(): array
    {
        $backupDisks = config('backup.backup.destination.disks', []);
        $mailAddress = (string) config('mail.from.address');
        $backupRecipient = (string) config('backup.notifications.mail.to');
        $trustedProxies = config('production.trusted_proxies');
        $reverseProxyEnabled = config('production.reverse_proxy_enabled') === true;
        $mailEnabled = config('production.mail_enabled') === true;
        $baseDomain = mb_strtolower(trim((string) config('app.public_base_domain')));
        $appHost = mb_strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $sessionDomain = mb_strtolower(ltrim(trim((string) config('session.domain')), '.'));
        $mediaDisk = (string) config('media-library.disk_name');
        $materialDisk = (string) config('slc.material_disk');
        $materialDiskConfig = config("filesystems.disks.{$materialDisk}");
        $materialStorageProtected = is_array($materialDiskConfig)
            && (
                ($materialDiskConfig['driver'] ?? null) !== 'local'
                || in_array(
                    $materialDiskConfig['root'] ?? null,
                    config('backup.backup.source.files.include', []),
                    true,
                )
            );
        $runtimeEnvironment = app()->environmentFile();
        $runtimeEnvironmentPath = app()->environmentPath().DIRECTORY_SEPARATOR.$runtimeEnvironment;
        $publicStorage = public_path('storage');
        $publicStorageTarget = realpath($publicStorage);

        return [
            $this->check('Mode production', app()->isProduction(), 'APP_ENV harus production.'),
            $this->check(
                'Environment runtime',
                $runtimeEnvironment === '.env',
                'Request web Laravel membaca .env. Salin .env.production menjadi .env sebelum deploy dan jalankan preflight tanpa --env.',
            ),
            $this->check(
                'Izin environment runtime',
                is_file($runtimeEnvironmentPath)
                    && is_readable($runtimeEnvironmentPath)
                    && (fileperms($runtimeEnvironmentPath) & 0077) === 0,
                'Berkas .env runtime harus ada dan berizin 600 (chmod 600 .env).',
            ),
            $this->check('Debug dimatikan', ! config('app.debug'), 'APP_DEBUG harus false.'),
            $this->check('Application key', filled(config('app.key')), 'APP_KEY belum diisi.'),
            $this->check(
                'Password awal bersama',
                is_string(config('onboarding.initial_password')) && mb_strlen(config('onboarding.initial_password')) >= 8,
                'INITIAL_PASSWORD wajib diisi minimal 8 karakter.',
            ),
            // Tiap peran harus benar-benar bisa diselesaikan, bukan cuma password
            // bersamanya terisi: mengisi INITIAL_PASSWORD_<PERAN> saja lalu
            // mengosongkan yang bersama membuat peran lain gagal dibuatkan akun,
            // dan itu baru ketahuan saat operator menambah warga di produksi.
            $this->check(
                'Password awal tiap peran',
                $this->semuaPeranPunyaSandiAwal(),
                'Ada peran tanpa password awal. Isi INITIAL_PASSWORD, atau INITIAL_PASSWORD_<PERAN> untuk tiap peran.',
            ),
            $this->check('URL HTTPS', Str::startsWith((string) config('app.url'), 'https://'), 'APP_URL harus memakai HTTPS.'),
            $this->check(
                'Domain publik',
                ! in_array(config('app.public_base_domain'), [null, '', 'localhost'], true),
                'PUBLIC_BASE_DOMAIN belum menunjuk domain produksi.',
            ),
            $this->check(
                'Konsistensi domain utama',
                $baseDomain !== '' && $appHost === $baseDomain,
                'Host APP_URL harus sama dengan PUBLIC_BASE_DOMAIN.',
            ),
            $this->check(
                'Cookie lintas subdomain',
                $baseDomain !== '' && $sessionDomain === $baseDomain,
                'SESSION_DOMAIN harus .'.$baseDomain.' agar sesi berlaku di domain utama dan seluruh subdomain nagari.',
            ),
            $this->check('Secure session cookie', config('session.secure') === true, 'SESSION_SECURE_COOKIE harus true.'),
            $this->check('Encrypted session', config('session.encrypt') === true, 'SESSION_ENCRYPT harus true.'),
            $this->check(
                'Konfigurasi reverse proxy',
                ! $reverseProxyEnabled || filled($trustedProxies),
                'REVERSE_PROXY_ENABLED=true mewajibkan TRUSTED_PROXIES berisi IP/CIDR proxy.',
            ),
            $this->check('Versi PHP', PHP_VERSION_ID >= 80500, 'Aplikasi membutuhkan PHP 8.5 atau lebih baru.'),
            $this->check(
                'Aset frontend production',
                is_file(public_path('build/manifest.json')),
                'public/build/manifest.json tidak ada. Jalankan npm ci && npm run build lalu sertakan public/build.',
            ),
            $this->check(
                'Endpoint SEO',
                app('router')->has('seo.robots') && app('router')->has('seo.sitemap'),
                'Route robots.txt dan sitemap.xml dinamis wajib tersedia.',
            ),
            $this->check(
                'Robots per domain',
                ! is_file(public_path('robots.txt')),
                'Hapus public/robots.txt statis; berkas itu menimpa robots dinamis per subdomain di Apache.',
            ),
            $this->check(
                'Front controller Apache',
                is_file(base_path('.htaccess')) && is_file(public_path('.htaccess')),
                '.htaccess akar dan public/.htaccess wajib ikut release Hostinger.',
            ),
            $this->check(
                'Direktori runtime writable',
                is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
                'storage dan bootstrap/cache harus dapat ditulis oleh user PHP.',
            ),
            $this->check(
                'Mail transport',
                ! $mailEnabled || ! in_array(config('mail.default'), ['array', 'log'], true),
                'MAIL_ENABLED=true membutuhkan MAIL_MAILER production seperti smtp/resend.',
            ),
            $this->check(
                'Alamat pengirim',
                ! $mailEnabled || (filter_var($mailAddress, FILTER_VALIDATE_EMAIL) !== false && ! Str::endsWith($mailAddress, '@example.com')),
                'MAIL_ENABLED=true membutuhkan MAIL_FROM_ADDRESS milik tim yang nyata.',
            ),
            $this->check(
                'Queue asynchronous',
                ! in_array(config('queue.default'), ['sync', 'background', 'deferred', 'null'], true),
                'QUEUE_CONNECTION harus database atau redis.',
            ),
            $this->check(
                'Persistent cache',
                ! in_array(config('cache.default'), ['array', 'null'], true),
                'CACHE_STORE harus database atau redis.',
            ),
            $this->check(
                'Persistent session',
                ! in_array(config('session.driver'), ['array', 'cookie', 'file'], true),
                'SESSION_DRIVER harus database atau redis.',
            ),
            $this->check(
                'Media storage server',
                $mediaDisk === 'public'
                    && is_link($publicStorage)
                    && $publicStorageTarget === realpath(storage_path('app/public')),
                'MEDIA_DISK harus public (storage server hosting) dan symlink public/storage wajib ada (jalankan `php artisan storage:link`).',
            ),
            $this->check(
                'Penyimpanan materi terlindungi',
                $materialStorageProtected,
                'SLC_MATERIAL_DISK tidak valid atau storage materi privat lokal belum disertakan dalam backup.',
            ),
            $this->check(
                'Backup terjadwal (lokal)',
                in_array('backup_local', $backupDisks, true),
                'BACKUP_DISKS wajib mencakup backup_local (backup tersimpan di server hosting).',
            ),
            $this->check(
                'Enkripsi backup',
                filled(config('backup.backup.password')),
                'BACKUP_ARCHIVE_PASSWORD wajib diisi.',
            ),
            $this->check(
                'Verifikasi backup',
                config('backup.backup.verify_backup') === true,
                'Verifikasi integritas arsip backup harus aktif.',
            ),
            $this->check(
                'Notifikasi backup',
                ! $mailEnabled || (filter_var($backupRecipient, FILTER_VALIDATE_EMAIL) !== false && ! Str::endsWith($backupRecipient, '@example.com')),
                'MAIL_ENABLED=true membutuhkan BACKUP_NOTIFICATION_EMAIL milik tim yang nyata.',
            ),
            $this->check(
                'Health checks',
                config('production.health.enabled') === true,
                'HEALTH_CHECKS_ENABLED harus aktif.',
            ),
            $this->check(
                'Batas unggah PHP',
                $this->uploadLimitKb() >= MaterisRelationManager::MAX_UKURAN_KB,
                'upload_max_filesize dan post_max_size harus minimal '
                    .(int) ceil(MaterisRelationManager::MAX_UKURAN_KB / 1024).'M agar berkas materi dapat diunggah.',
            ),
            $this->check(
                'Batas unggah Livewire',
                $this->livewireUploadLimitKb() >= MaterisRelationManager::MAX_UKURAN_KB,
                'livewire.temporary_file_upload.rules membatasi ukuran di bawah batas berkas materi, unggahan akan ditolak sebelum sampai ke form.',
            ),
            $this->check(
                'Error monitoring',
                filled(config('sentry.dsn')),
                'SENTRY_LARAVEL_DSN wajib diisi agar exception produksi memicu alert.',
            ),
        ];
    }

    /** Batas unggah efektif PHP dalam KB: yang terkecil antara dua direktif. */
    private function uploadLimitKb(): int
    {
        return (int) min(
            $this->iniToKb((string) ini_get('upload_max_filesize')),
            $this->iniToKb((string) ini_get('post_max_size')),
        );
    }

    /** Batas `max:` pada aturan unggahan sementara Livewire (KB); 12288 = bawaan. */
    private function livewireUploadLimitKb(): int
    {
        $rules = config('livewire.temporary_file_upload.rules');

        if (! is_array($rules)) {
            return 12288;
        }

        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'max:')) {
                return (int) substr($rule, 4);
            }
        }

        return 12288;
    }

    private function iniToKb(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $angka = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $angka * 1024 * 1024,
            'm' => $angka * 1024,
            'k' => $angka,
            default => (int) ($angka / 1024),
        };
    }

    private function check(string $label, bool $passed, string $message): array
    {
        return compact('label', 'passed', 'message');
    }

    private function semuaPeranPunyaSandiAwal(): bool
    {
        $initialPassword = app(InitialPasswordService::class);

        foreach (InitialPasswordService::ROLES as $role) {
            try {
                $initialPassword->forRole($role);
            } catch (RuntimeException) {
                return false;
            }
        }

        return true;
    }
}
