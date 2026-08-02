<?php

namespace App\Filament\Resources\Nagaris\Schemas;

use App\Models\Nagari;
use App\Models\RefWilayah;
use App\Services\WilayahLookupService;
use App\Support\PhoneNumber;
use Filament\Forms\Components\Select;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class NagariForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Nagari & Operator')
                    ->columns(2)
                    ->schema([
                        Select::make('wilayah_kode')
                            ->label('Nama Nagari')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->getSearchResultsUsing(fn (string $search): array => self::searchNagari($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => self::nagariLabel($value))
                            ->unique(Nagari::class, 'wilayah_kode', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->withoutTrashed())
                            ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                            ->placeholder('Ketik nama atau kode wilayah')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (Set $set, ?string $state) => self::applyWilayah($set, $state)),

                        TextInput::make('slug')
                            ->label('Alamat subdomain')
                            // Slug adalah alamat DNS produksi. Operator hanya boleh
                            // mengelola konten visual nagarinya, bukan alamat situs.
                            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                            ->helperText('Kosongkan agar dibuat otomatis dari nama nagari. Mengubahnya setelah dibagikan akan mematikan tautan lama.')
                            ->suffix('.'.config('app.public_base_domain'))
                            ->placeholder('Terisi otomatis')
                            ->maxLength(63)
                            // Satu kata tanpa simbol: nilainya dipakai apa adanya
                            // sebagai label DNS, yang tak boleh mengandung titik
                            // dan tak boleh diawali/diakhiri tanda hubung.
                            ->rule('regex:/^[a-z0-9]+$/')
                            ->validationMessages([
                                'regex' => 'Hanya huruf kecil dan angka, tanpa spasi, titik, atau tanda hubung.',
                            ])
                            ->unique(Nagari::class, 'slug', ignoreRecord: true)
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                    if (Nagari::subdomainTerlarang(is_string($value) ? $value : null)) {
                                        $fail('Alamat ini dipakai layanan hosting atau surel, pilih yang lain.');
                                    }
                                },
                            ])
                            ->columnSpanFull(),

                        TextInput::make('operator_username_display')
                            ->label('Username operator')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedAtSymbol)
                            ->placeholder('Terisi otomatis')
                            ->columnSpanFull(),

                        TextInput::make('operator_email')
                            ->label('Email operator')
                            ->email()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            ->rule(fn (?Model $record) => Rule::unique('users', 'email')
                                ->ignore($record instanceof Nagari ? $record->operator()->value('id') : null))
                            ->placeholder('email@contoh.id (opsional)'),

                        TextInput::make('operator_kontak')
                            ->label('No. HP operator')
                            ->tel()
                            ->maxLength(20)
                            ->regex(PhoneNumber::REGEX)
                            ->validationMessages(['regex' => 'Nomor HP tidak valid (mis. 08123456789).'])
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->placeholder('08xxxxxxxxxx (opsional)'),

                        OptimizedSpatieMediaLibraryFileUpload::make('sampul')
                            ->label('Foto Sampul Beranda')
                            ->helperText('Rasio 16:9. Maksimal 5 foto, 10 MB per foto.')
                            ->collection('sampul')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->panelLayout('grid')
                            ->imageEditor()
                            ->maxSize(10240)
                            ->maxFiles(5)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Hasil pencarian nagari: "Nama · Kecamatan, Kabupaten" agar tak ambigu.
     *
     * @return array<string, string>
     */
    protected static function searchNagari(string $search): array
    {
        return app(WilayahLookupService::class)->search($search);
    }

    /** Label nagari terpilih (untuk hidrasi saat edit) — termasuk kode wilayah. */
    protected static function nagariLabel(?string $kode): ?string
    {
        if (! $kode) {
            return null;
        }

        $nagari = RefWilayah::find($kode);

        if (! $nagari) {
            return null;
        }

        $kec = RefWilayah::find(self::ancestor($kode, 3))?->nama;
        $kab = RefWilayah::find(self::ancestor($kode, 2))?->nama;

        return "{$nagari->nama} · {$kec}, {$kab} — {$kode}";
    }

    /** Tampilkan pratinjau username dari kode resmi yang dipilih. */
    protected static function applyWilayah(Set $set, ?string $kode): void
    {
        if (! $kode) {
            return;
        }

        // Pratinjau username admin (read-only) ikut kode terpilih.
        $set('operator_username_display', Nagari::usernameFromKode($kode));
    }

    /** Kode leluhur pada `n` segmen pertama (1=prov, 2=kab, 3=kec). */
    protected static function ancestor(?string $kode, int $segments): ?string
    {
        if (! $kode) {
            return null;
        }

        $parts = explode('.', $kode);

        return count($parts) >= $segments ? implode('.', array_slice($parts, 0, $segments)) : null;
    }
}
