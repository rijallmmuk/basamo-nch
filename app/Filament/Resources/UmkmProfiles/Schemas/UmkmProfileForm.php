<?php

namespace App\Filament\Resources\UmkmProfiles\Schemas;

use App\Models\UmkmProfile;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UmkmProfileForm
{
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
                            // 1 warga = 1 lapak (umkm_profiles.user_id UNIQUE):
                            // beri pesan validasi alih-alih error DB.
                            ->unique(UmkmProfile::class, 'user_id', ignoreRecord: true)
                            ->helperText('Hanya warga yang sudah diberi akses UMKM. Satu warga hanya boleh punya satu profil usaha.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Profil Usaha')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_usaha')
                            ->label('Nama usaha')
                            ->required()
                            ->maxLength(255),

                        Select::make('umkm_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'nama')
                            ->required()
                            ->native(false)
                            ->preload(),

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
     * Opsi pemilik = warga yang sudah diberi akses UMKM. nagari_admin hanya nagarinya.
     *
     * @return array<int, string>
     */
    protected static function ownerOptions(): array
    {
        $actor = auth()->user();

        return User::query()
            ->where('role', 'warga')
            ->whereNotNull('umkm_access_granted_at')
            ->when($actor?->isNagariAdmin(), fn ($q) => $q->where('nagari_id', $actor->nagari_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
