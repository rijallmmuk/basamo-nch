<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Enums\KategoriKontak;
use App\Models\KontakMasuk;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class KirimLaporan extends Page implements HasForms, HasTable
{
    use HasPanelBreadcrumbs;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Bantuan & Laporan';

    protected static ?string $title = 'Bantuan & Laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kirim-laporan';

    public static function getNavigationGroup(): ?string
    {
        return 'Bantuan';
    }

    public ?array $data = [];

    /** @var array<int> */
    public array $unreadReplyIds = [];

    public function mount(): void
    {
        $userId = auth()->id();

        $this->unreadReplyIds = KontakMasuk::query()
            ->where('user_id', $userId)
            ->whereNotNull('balasan')
            ->whereNull('balasan_dibaca_at')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($this->unreadReplyIds !== []) {
            KontakMasuk::query()
                ->where('user_id', $userId)
                ->whereKey($this->unreadReplyIds)
                ->update(['balasan_dibaca_at' => now()]);
        }

        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isOperator() || $user->isPengajar() || $user->isDpmd());
    }

    /** Badge = jumlah balasan Superadmin yang belum dibuka oleh pelapor. */
    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $count = KontakMasuk::query()
            ->where('user_id', $userId)
            ->whereNotNull('balasan')
            ->whereNull('balasan_dibaca_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kategori')
                    ->label('Jenis Laporan/Saran')
                    ->options([
                        KategoriKontak::KeluhanSaran->value => KategoriKontak::KeluhanSaran->getLabel(),
                        KategoriKontak::LaporanError->value => KategoriKontak::LaporanError->getLabel(),
                        KategoriKontak::Lainnya->value => KategoriKontak::Lainnya->getLabel(),
                    ])
                    ->required(),
                Textarea::make('isi')
                    ->label('Deskripsi')
                    ->placeholder('Jelaskan laporan, kendala, atau saran Anda secara rinci...')
                    ->required()
                    ->rows(5),
                FileUpload::make('file_pendukung')
                    ->label('File Pendukung (Opsional)')
                    ->disk('public')
                    ->directory('laporan-pendukung')
                    ->storeFileNamesIn('file_pendukung_nama')
                    ->acceptedFileTypes(['image/*', 'application/pdf', 'application/zip'])
                    ->maxSize(10240) // 10MB
                    ->helperText('Maks. 10MB (Gambar, PDF, atau ZIP)'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $laporan = KontakMasuk::create([
            'user_id' => $user->id,
            'nama' => $user->name,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
            'kategori' => $data['kategori'],
            'isi' => $data['isi'],
        ]);

        if (! empty($data['file_pendukung'])) {
            $path = is_array($data['file_pendukung']) ? array_values($data['file_pendukung'])[0] : $data['file_pendukung'];

            if (is_string($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $namaTersimpan = $data['file_pendukung_nama'] ?? null;
                $namaAsli = is_array($namaTersimpan) ? array_values($namaTersimpan)[0] ?? null : $namaTersimpan;

                $laporan
                    ->addMediaFromDisk($path, 'public')
                    ->usingFileName($this->namaLampiranAman($namaAsli, $path))
                    ->toMediaCollection('file_pendukung');
            }
        }

        Notification::make()
            ->title('Laporan terkirim')
            ->body('Terima kasih! Pesan Anda telah dikirimkan ke Superadmin.')
            ->success()
            ->send();

        $this->form->fill();
    }

    private function namaLampiranAman(mixed $namaAsli, string $path): string
    {
        $nama = is_string($namaAsli) && $namaAsli !== '' ? basename($namaAsli) : basename($path);
        $ekstensi = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
        $dasar = Str::of(pathinfo($nama, PATHINFO_FILENAME))->slug('-')->limit(100, '')->value();

        return ($dasar !== '' ? $dasar : 'lampiran').($ekstensi !== '' ? ".{$ekstensi}" : '');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(KontakMasuk::query()->where('user_id', auth()->id()))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('isi')
                    ->label('Isi Pesan')
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (KontakMasuk $record): string => $record->balasan ? 'Dibalas' : 'Menunggu')
                    ->color(fn (string $state): string => match ($state) {
                        'Dibalas' => 'success',
                        'Menunggu' => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (KontakMasuk $record): ?string => in_array($record->id, $this->unreadReplyIds, true)
                ? 'nch-row-new'
                : null)
            ->recordActions([
                Action::make('lihat')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Laporan & Balasan')
                    ->modalContent(fn (KontakMasuk $record) => view('filament.kontak-masuk-detail', ['kontak' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ]);
    }
}
