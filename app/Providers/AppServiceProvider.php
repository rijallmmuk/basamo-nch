<?php

namespace App\Providers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Sumber kebenaran otoritas = kolom `role`. super_admin lolos semua ability.
        // Return null (bukan false) agar role lain diteruskan ke policy.
        Gate::before(fn (?User $user, string $ability) => $user?->isSuperAdmin() ? true : null);

        // Tombol perkecil/perlebar sidebar: pakai ikon hamburger (toggle menu) — bukan
        // chevron-ganda default yang mudah disalahartikan sebagai tombol "kembali".
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'heroicon-m-bars-3',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'heroicon-m-bars-3',
        ]);

        // Urutan tombol footer modal DIBALIK se-aplikasi (keputusan user):
        // Batal di kiri, aksi utama paling kanan — konsisten dgn modal portal
        // warga (confirm-dialog & profil). Bawaan Filament: submit dulu.
        Action::configureUsing(function (Action $action): void {
            $action->modalFooterActions(fn (Action $action): array => array_values(array_filter([
                $action->getModalCancelAction(),
                ...$action->getExtraModalFooterActions(),
                $action->getModalSubmitAction(),
            ])));
        });
    }
}
