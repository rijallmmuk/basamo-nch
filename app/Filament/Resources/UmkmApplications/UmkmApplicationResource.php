<?php

namespace App\Filament\Resources\UmkmApplications;

use App\Enums\PengajuanUmkmStatus;
use App\Filament\Resources\UmkmApplications\Pages\ListUmkmApplications;
use App\Models\UmkmProfile;
use App\Services\UmkmService;
use App\Support\DesaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Antrean pengajuan akses UMKM mandiri dari warga (profil + 1 produk lengkap).
 * Setujui → akses UMKM aktif, lapak tayang, produk bawaan ikut disetujui
 * (satu tinjauan cukup). Tolak → alasan wajib; warga boleh mengajukan ulang.
 * Baris = UmkmProfile ber-`status_pengajuan` (menunggu/ditolak).
 */
class UmkmApplicationResource extends Resource
{
    protected static ?string $model = UmkmProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'UMKM';
    }

    // Tampil di sidebar untuk admin desa; super admin lewat "Kelola › UMKM" (DesaContext).
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isDesaAdmin()) {
            return true;
        }

        return $user?->isSuperAdmin() && DesaContext::id() !== null;
    }

    public static function getModelLabel(): string
    {
        return 'Pengajuan UMKM';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengajuan UMKM';
    }

    /** Badge navigasi = jumlah pengajuan menunggu tinjauan (ter-scope aktor). */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status_pengajuan', PengajuanUmkmStatus::Menunggu)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereNotNull('status_pengajuan')
            ->with(['owner', 'products.media', 'products.category']);

        $desaId = auth()->user()?->managedDesaId();

        if ($desaId !== null) {
            $query->forDesa($desaId);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            // Antrean tinjauan: baris tak diklik — aksi lewat tombol Tinjau/Tolak.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama_usaha')
                    ->label('Nama Usaha')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('owner.name')
                    ->label('Pengaju')
                    ->searchable(),

                // Hanya PRODUK UNGGULAN pengajuan (produk pertama) — lapak lama
                // yang diajukan ulang bisa membawa banyak produk lain.
                TextColumn::make('kategori_unggulan')
                    ->label('Kategori')
                    ->getStateUsing(fn (UmkmProfile $record): ?string => $record->products->first()?->category?->nama)
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('produk_unggulan')
                    ->label('Produk')
                    ->getStateUsing(fn (UmkmProfile $record): ?string => $record->products->first()?->nama_produk)
                    ->limit(30)
                    ->wrap(),

                TextColumn::make('status_pengajuan')
                    ->label('Status')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('diajukan_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status_pengajuan')
                    ->label('Status')
                    ->options(PengajuanUmkmStatus::class),
            ])
            ->recordActions([
                // SATU aksi "Tinjau" (keputusan user): modal detail lengkap dulu,
                // keputusan Setujui/Tolak baru dipilih DI DALAM modal.
                Action::make('tinjau')
                    ->label('Tinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (UmkmProfile $record): bool => $record->status_pengajuan === PengajuanUmkmStatus::Menunggu
                        && auth()->user()->can('update', $record))
                    ->modalHeading(fn (UmkmProfile $record): string => 'Pengajuan: '.$record->nama_usaha)
                    ->modalContent(fn (UmkmProfile $record) => view('filament.umkm-application-detail', ['profile' => $record]))
                    ->modalWidth('2xl')
                    // Keputusan diambil DI DALAM modal (tanpa submit bawaan):
                    // Tolak & Setujui masing-masing minta konfirmasi dulu.
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Action::make('tolak')
                            ->label('Tolak…')
                            ->color('danger')
                            ->modalHeading('Tolak pengajuan')
                            ->modalDescription('Alasan ditampilkan ke warga — ia dapat memperbaiki lalu mengajukan ulang.')
                            ->modalSubmitActionLabel('Ya, Tolak')
                            ->schema([
                                Textarea::make('alasan')
                                    ->label('Alasan penolakan')
                                    ->required()
                                    ->minLength(5)
                                    ->maxLength(1000)
                                    ->rows(3),
                            ])
                            ->action(function (UmkmProfile $record, array $data): void {
                                app(UmkmService::class)->rejectApplication($record, $data['alasan']);

                                Notification::make()
                                    ->title('Pengajuan ditolak')
                                    ->body('Warga diberi tahu beserta alasannya.')
                                    ->success()
                                    ->send();
                            })
                            ->cancelParentActions(),

                        Action::make('setujui')
                            ->label('Setujui Pengajuan')
                            ->color('success')
                            // Pengaju hilang (akun diarsipkan): tak ada yang bisa
                            // diberi akses — hanya tersisa Tolak.
                            ->visible(fn (UmkmProfile $record): bool => $record->owner !== null)
                            ->requiresConfirmation()
                            ->modalHeading('Setujui pengajuan ini?')
                            ->modalDescription('Akses UMKM warga langsung aktif; lapak & produknya tayang di katalog publik.')
                            ->modalSubmitActionLabel('Ya, Setujui')
                            ->action(function (UmkmProfile $record): void {
                                app(UmkmService::class)->approveApplication($record, auth()->user());

                                Notification::make()
                                    ->title('Pengajuan disetujui')
                                    ->body("{$record->owner?->name} kini Pemilik UMKM — lapak & produknya tayang di katalog.")
                                    ->success()
                                    ->send();
                            })
                            ->cancelParentActions(),
                    ]),
            ])
            ->defaultSort('diajukan_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUmkmApplications::route('/'),
        ];
    }
}
