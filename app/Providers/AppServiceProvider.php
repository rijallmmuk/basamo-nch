<?php

namespace App\Providers;

use App\Http\Middleware\EnsurePortalUser;
use App\Models\Evaluasi;
use App\Models\Faq;
use App\Models\KontakMasuk;
use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\TemaPelatihan;
use App\Models\UmkmCategory;
use App\Models\User;
use App\Observers\ActivityObserver;
use App\Policies\ActivityPolicy;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\View\PanelsIconAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Stringable;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Activity::observe(ActivityObserver::class);
        Gate::policy(Activity::class, ActivityPolicy::class);

        Model::preventLazyLoading(! $this->app->isProduction());

        Stringable::macro('sanitizeHtml', function () {
            // @phpstan-ignore-next-line
            return new static(Purifier::clean($this->value));
        });

        // Request Livewire memakai endpoint bersama, sehingga middleware route asal
        // harus dipersist agar tab lama tetap memeriksa status akun/nagari dan hash
        // password pada setiap aksi, bukan hanya saat navigasi halaman penuh.
        Livewire::addPersistentMiddleware([
            EnsurePortalUser::class,
            AuthenticateSession::class,
        ]);

        // Sumber kebenaran otoritas = Spatie multi-role. Superadmin lolos ability
        // umum, termasuk authoring SLC. DPMD mengawasi lintas nagari: hanya boleh
        // viewAny/view dan membalas forum. Mutasi struktur/data lain tetap ditolak
        // keras, apa pun kombinasi perannya.
        // Return null (bukan true/false) agar peran lain diteruskan ke policy.
        Gate::before(function (?User $user, string $ability, array $arguments = []): ?bool {
            $subject = $arguments[0] ?? null;

            // Pembuatan struktur SLC: Pengajar, Superadmin, dan Operator boleh membuat.
            // Aturan spesifik (mis. operator tidak bisa kolaborasi) diatur di Policy.
            if (in_array($ability, ['create', 'kelolaKonten', 'kelolaKolaborator', 'kelolaStatus'])
                && in_array($subject, [Pelatihan::class, Module::class, Evaluasi::class], true)) {
                // Biarkan Policy yang menangani aturan detail (termasuk operator).
                // Hanya cegat jika user sama sekali tidak punya peran yang relevan.
                if (! ($user?->isPengajar() || $user?->isSuperAdmin() || $user?->isOperator())) {
                    return false;
                }
            }

            if ($user?->isDpmd()) {
                if ($this->isDpmdRestrictedSubject($subject)) {
                    return false;
                }

                return in_array($ability, ['viewAny', 'view', 'reply'], true);
            }

            if ($user?->isSuperAdmin()) {
                // Permanent delete LMS tetap melewati Policy karena dibatasi
                // oleh keberadaan aktivitas belajar, termasuk untuk superadmin.
                if ($ability === 'forceDelete'
                    && ($subject instanceof Pelatihan
                        || $subject instanceof Module
                        || $subject instanceof Evaluasi)) {
                    return null;
                }

                return true;
            }

            return null;
        });

        // Nama role yang sudah dipensiunkan tidak boleh lahir kembali lewat jalur mana
        // pun (tinker, seeder, kode lain). Dipasang di level model supaya tidak ada
        // pintu belakang. Nama role INTI sengaja TIDAK diblokir di sini — RoleSeeder
        // membuatnya lewat Role::findOrCreate(), dan duplikatnya sudah ditahan unique
        // (name, guard_name) di database.
        Role::creating(function (Role $role): void {
            $this->guardRetiredRoleName((string) $role->name);
        });

        // Kelima role platform dirujuk Policy/middleware/navigasi. Tidak ada CRUD
        // peran di panel, jadi nama dan guard-nya dikunci mati.
        Role::updating(function (Role $role): void {
            if ($role->isDirty('name') || $role->isDirty('guard_name')) {
                throw ValidationException::withMessages([
                    'name' => 'Nama dan guard role tidak dapat diubah.',
                ]);
            }
        });

        Role::deleting(function (Role $role): void {
            throw ValidationException::withMessages([
                'name' => 'Kelima role platform tidak dapat dihapus.',
            ]);
        });

        // Tombol perkecil/perlebar sidebar: pakai ikon hamburger (toggle menu) — bukan
        // chevron-ganda default yang mudah disalahartikan sebagai tombol "kembali".
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'heroicon-m-bars-3',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'heroicon-m-bars-3',
        ]);

        // Tombol footer modal se-panel admin (DIPERBARUI 2026-07-13, keputusan user):
        // dua pola beda menurut jenis modal —
        //  • Dialog konfirmasi SEDERHANA (tanpa form, mis. Hapus/Pulihkan/Setujui) →
        //    DIPUSATKAN (lihat override .fi-align-center di theme.css: bawaan Filament
        //    men-stack vertikal utk Center, di sini dipaksa jadi baris horizontal).
        //  • Modal BERFORM (Buat/Ubah, atau modal dgn field spt Tolak yg punya alasan)
        //    → rata KANAN + dibalik: "Batal" KIRI, aksi utama PALING KANAN
        //    (Alignment::End Filament otomatis flex-row-reverse, lihat .fi-align-end).
        // Alignment dilewatkan sbg CLOSURE (bukan nilai langsung) karena configureUsing
        // berjalan SEBELUM chain ->requiresConfirmation() sendiri dieksekusi (termasuk
        // punya bawaan Delete/Restore/ForceDeleteAction) — closure baru dievaluasi saat
        // modal benar-benar dirender, setelah semua chain selesai, jadi hasilnya akurat.
        // Array TETAP [utama, ...tambahan, Batal] tanpa dibalik manual.
        Action::configureUsing(function (Action $action): void {
            $action
                ->modalFooterActionsAlignment(fn (Action $action): Alignment => $action->isConfirmationRequired()
                    ? Alignment::Center
                    : Alignment::End)
                ->modalFooterActions(fn (Action $action): array => array_values(array_filter([
                    $action->getModalSubmitAction(),
                    ...$action->getExtraModalFooterActions(),
                    $action->getModalCancelAction(),
                ])));
        });

        // Tombol Buat/Simpan + Batal di halaman Create/Edit penuh (2026-07-13): rata
        // KANAN + dibalik (Batal kiri, aksi utama kanan) — mekanisme sama seperti di
        // atas (Alignment::End = flex-row-reverse), berlaku ke SEMUA halaman panel.
        Page::formActionsAlignment(Alignment::End);

        // Batasi ukuran halaman agar tabel tetap stabil saat volume data bertambah.
        // Kolom aksi gabungan (⋮) diberi judul "Aksi" se-panel admin.
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([5, 10, 25, 50, 100])
                ->recordActionsColumnLabel('Aksi')
                ->emptyStateHeading(fn (Table $table): string => 'Belum ada '.$table->getPluralModelLabel())
                ->emptyStateDescription('Data akan tampil di sini setelah tersedia atau sesuai filter yang dipilih.')
                ->emptyStateIcon(Heroicon::OutlinedInbox);
        });

        // "Buat & buat lainnya" dihilangkan se-panel — cukup Buat, Batal, + aksi lain
        // per halaman (mis. Simpan & Lanjut). Dua jalur: halaman Create penuh (kill-switch
        // statis bawaan Filament) & aksi "Buat" bermodal di halaman ManageRecords
        // (Data Master, Kategori UMKM, dll — beda mekanisme, CreateAction bukan CreateRecord).
        CreateRecord::disableCreateAnother();
        CreateAction::configureUsing(fn (CreateAction $action) => $action->createAnother(false));

    }

    /**
     * Tolak nama role yang sudah dipensiunkan ({@see User::RETIRED_ROLES}).
     * Perbandingan mengabaikan besar-kecil huruf dan spasi tepi, supaya "UMKM",
     * " umkm ", atau "Umkm" tidak lolos lewat celah penulisan.
     */
    private function guardRetiredRoleName(string $name): void
    {
        $normalized = mb_strtolower(trim($name));

        if (! in_array($normalized, User::RETIRED_ROLES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'name' => "Nama role \"{$normalized}\" sudah dipensiunkan dan tidak boleh dipakai lagi. "
                .'Akses UMKM bukan role: berikan lewat tombol "Beri Akses UMKM" pada menu UMKM atau data Warga.',
        ]);
    }

    private function isDpmdRestrictedSubject(mixed $subject): bool
    {
        $subjectClass = is_object($subject) ? $subject::class : $subject;

        return in_array($subjectClass, [
            Role::class,
            Faq::class,
            KontakMasuk::class,
            Activity::class,
            UmkmCategory::class,
            TemaPelatihan::class,
        ], true);
    }
}
