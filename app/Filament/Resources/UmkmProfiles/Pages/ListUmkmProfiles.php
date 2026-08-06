<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Enums\ActiveStatus;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\User;
use App\Services\UmkmService;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ListUmkmProfiles extends ListRecords
{
    protected static string $resource = UmkmProfileResource::class;

    use HasListTitle;

    // Super admin selalu tampil di sidebar (2026-07-14) — tanpa konteks, otomatis
    // ke nagari pertama (urut nama) supaya tak buntu.
    public ?int $nagariId = null;

    public function mount(): void
    {
        parent::mount();

        if (UmkmProfileResource::isSelfService()) {
            $profile = auth()->user()?->umkmProfile;

            if ($profile) {
                $this->redirect(UmkmProfileResource::getUrl('view', ['record' => $profile]));

                return;
            }

            $this->redirect(UmkmProfileResource::getUrl('create'));

            return;
        }

        if (auth()->user()?->hasAnyRole(['superadmin', 'dpmd']) ?? false) {
            NagariContext::ensureDefault(NagariContext::UMKM_PROFIL);
            $this->nagariId = NagariContext::id(NagariContext::UMKM_PROFIL);
        }
    }

    // Pemilih nagari inline (2026-07-14, setara SDGs/Cuaca — koreksi dari pola lama
    // "auto nagari pertama + tombol Kembali ke Nagari"): ganti nagariId → NagariContext
    // (namespace UMKM_PROFIL, independen dari menu lain) ikut disetel, tabel
    // (query statis resource, baca NagariContext) langsung ter-render ulang, tanpa reload.
    public function updatedNagariId(): void
    {
        if ((auth()->user()?->hasAnyRole(['superadmin', 'dpmd']) ?? false) && $this->nagariId !== null) {
            NagariContext::set(NagariContext::UMKM_PROFIL, $this->nagariId);
        }
    }

    public function content(Schema $schema): Schema
    {
        $user = auth()->user();

        if (! ($user?->hasAnyRole(['superadmin', 'operator', 'dpmd']) ?? false)) {
            return parent::content($schema);
        }

        $components = parent::content($schema)->getComponents();
        $tambahan = [View::make('filament.components.umkm-access-pending')];

        if ($user->hasAnyRole(['superadmin', 'dpmd'])) {
            array_unshift($tambahan, View::make('filament.components.nagari-picker-banner'));
        }

        array_splice($components, 1, 0, $tambahan);

        return $schema->components($components);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('tambahUmkm')
                ->label('Tambah UMKM')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['superadmin', 'operator']) ?? false),
            Action::make('beriAksesUmkm')
                ->label('Beri Akses UMKM')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn (): bool => $this->canGrantUmkmAccess())
                ->modalHeading('Beri akses kelola UMKM')
                ->modalDescription('Pilih warga yang akan diberi akses mengelola sendiri profil usahanya melalui portal.')
                ->modalSubmitActionLabel('Beri Akses')
                ->schema([
                    Select::make('user_id')
                        ->label('Warga')
                        ->placeholder('Cari nama atau NIK warga')
                        ->options(fn (): array => $this->candidateOptions())
                        ->getSearchResultsUsing(fn (string $search): array => $this->candidateOptions($search))
                        ->getOptionLabelUsing(fn ($value): ?string => $this->candidateLabel((int) $value))
                        ->searchable()
                        ->required()
                        ->helperText('Hanya warga aktif pada nagari yang sedang dipilih dan belum memiliki akses UMKM.'),
                ])
                ->action(function (array $data): void {
                    $owner = $this->candidateQuery()->find($data['user_id'] ?? null);

                    if (! $owner) {
                        throw ValidationException::withMessages([
                            'user_id' => 'Warga tidak valid, tidak memenuhi syarat, atau berada di luar nagari yang Anda kelola.',
                        ]);
                    }

                    app(UmkmService::class)->grantAccess($owner);

                    Notification::make()
                        ->title('Akses UMKM diberikan')
                        ->body("{$owner->name} masuk ke daftar menunggu profil dan dapat mengisi profil usahanya setelah masuk.")
                        ->success()
                        ->send();
                }),
        ];
    }

    /** Warga yang sudah mendapat akses, tetapi belum menyelesaikan profil usaha. */
    public function getPemilikMenungguProperty(): Collection
    {
        return $this->pendingOwnerQuery()
            ->with('penduduk')
            ->orderByDesc('umkm_access_granted_at')
            ->get();
    }

    public function cabutAksesTertundaAction(): Action
    {
        return Action::make('cabutAksesTertunda')
            ->label('Cabut akses')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->size('sm')
            ->visible(fn (): bool => $this->canManageUmkmAccess())
            ->requiresConfirmation()
            ->modalHeading('Cabut akses UMKM?')
            ->modalDescription('Warga tidak lagi dapat membuka menu Kelola Usaha. Belum ada profil atau produk yang perlu dihapus.')
            ->modalSubmitActionLabel('Ya, cabut akses')
            ->action(function (array $arguments): void {
                $owner = $this->canManageUmkmAccess()
                    ? $this->pendingOwnerQuery()->find($arguments['user'] ?? null)
                    : null;

                if (! $owner) {
                    Notification::make()
                        ->title('Akses tidak dapat dicabut')
                        ->body('Warga tidak ditemukan atau berada di luar nagari yang Anda kelola.')
                        ->danger()
                        ->send();

                    return;
                }

                app(UmkmService::class)->revokeAccess($owner);

                Notification::make()
                    ->title('Akses UMKM dicabut')
                    ->body("{$owner->name} dikeluarkan dari daftar menunggu profil.")
                    ->success()
                    ->send();
            });
    }

    public function canManageUmkmAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'operator'])
            && $this->managedNagariId() !== null;
    }

    private function canGrantUmkmAccess(): bool
    {
        return $this->canManageUmkmAccess();
    }

    private function managedNagariId(): ?int
    {
        return auth()->user()?->managedNagariId(NagariContext::UMKM_PROFIL);
    }

    /** @return Builder<User> */
    private function candidateQuery(): Builder
    {
        $nagariId = $this->managedNagariId();

        if ($nagariId === null || ! $this->canGrantUmkmAccess()) {
            return User::query()->whereKey([]);
        }

        return User::query()
            ->role('warga')
            ->where('status', ActiveStatus::Active)
            ->where('nagari_id', $nagariId)
            ->whereNull('umkm_access_granted_at')
            ->whereHas('penduduk', fn (Builder $penduduk) => $penduduk->where('nagari_id', $nagariId))
            ->whereDoesntHave('umkmProfile', fn (Builder $profiles) => $profiles->withTrashed());
    }

    /** @return Builder<User> */
    private function pendingOwnerQuery(): Builder
    {
        $nagariId = $this->managedNagariId();

        if ($nagariId === null || UmkmProfileResource::isSelfService()) {
            return User::query()->whereKey([]);
        }

        return User::query()
            ->where('nagari_id', $nagariId)
            ->whereNotNull('umkm_access_granted_at')
            ->whereDoesntHave('umkmProfile', fn (Builder $profiles) => $profiles->withTrashed());
    }

    /** @return array<int, string> */
    private function candidateOptions(?string $search = null): array
    {
        return $this->candidateQuery()
            ->when(filled($search), fn (Builder $query) => $query->where(fn (Builder $filter) => $filter
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'nik'])
            ->mapWithKeys(fn (User $user): array => [$user->id => self::userLabel($user)])
            ->all();
    }

    private function candidateLabel(int $userId): ?string
    {
        $user = $this->candidateQuery()->find($userId, ['id', 'name', 'nik']);

        return $user ? self::userLabel($user) : null;
    }

    private static function userLabel(User $user): string
    {
        return trim($user->name.' · NIK '.($user->nik ?: 'belum tersedia'));
    }
}
