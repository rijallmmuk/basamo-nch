<?php

namespace App\Filament\Resources\UmkmProfiles\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UmkmProfileForm
{
    /** Kategori usaha umum di nagari. */
    public const KATEGORI = [
        'Kuliner' => 'Kuliner',
        'Kerajinan' => 'Kerajinan',
        'Fashion' => 'Fashion & Tekstil',
        'Pertanian' => 'Pertanian & Perkebunan',
        'Peternakan' => 'Peternakan & Perikanan',
        'Jasa' => 'Jasa',
        'Lainnya' => 'Lainnya',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pemilik')
                    ->description('UMKM ditautkan ke akun Pemilik UMKM. Nagari mengikuti nagari pemilik.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Pemilik UMKM')
                            ->options(fn () => static::ownerOptions())
                            ->searchable()
                            ->required()
                            ->helperText('Hanya warga yang sudah diberi akses UMKM (role Pemilik UMKM).')
                            ->columnSpanFull(),
                    ]),

                Section::make('Profil Usaha')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_usaha')
                            ->label('Nama usaha')
                            ->required()
                            ->maxLength(255),

                        Select::make('kategori')
                            ->label('Kategori')
                            ->options(self::KATEGORI)
                            ->required()
                            ->native(false),

                        TextInput::make('whatsapp')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->helperText('Nomor untuk pembeli menghubungi via WhatsApp.'),

                        Select::make('status')
                            ->label('Status')
                            ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])
                            ->default('active')
                            ->required()
                            ->native(false),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('alamat')
                            ->label('Alamat usaha')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Opsi pemilik = akun Pemilik UMKM. nagari_admin hanya nagarinya.
     *
     * @return array<int, string>
     */
    protected static function ownerOptions(): array
    {
        $actor = auth()->user();

        return User::query()
            ->where('role', 'umkm_owner')
            ->when($actor?->isNagariAdmin(), fn ($q) => $q->where('nagari_id', $actor->nagari_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
