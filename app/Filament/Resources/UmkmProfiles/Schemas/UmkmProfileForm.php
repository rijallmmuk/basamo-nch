<?php

namespace App\Filament\Resources\UmkmProfiles\Schemas;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Support\NagariContext;
use App\Support\PhoneNumber;
use Filament\Forms\Components\Select;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use App\Filament\Forms\Components\TautanPromosiRepeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;

/**
 * Form profil usaha. Dipakai dua pihak dengan kebutuhan berbeda:
 *
 *  - PEMILIK (self-service) hanya melihat isian yang memang datanya milik dia:
 *    identitas usaha, kontak, alamat, foto etalase, dan tautan promosi.
 *  - OPERATOR/superadmin melihat tambahan Pemilik dan Status tayang, dua hal
 *    yang bukan wewenang pemilik.
 */
class UmkmProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Peringatan bagi pemilik: lapaknya nonaktif (tak tampil publik) — tetap
                // bisa dikelola, tapi reaktivasi lewat Operator Nagari.
                View::make('filament.components.umkm-lapak-nonaktif-banner')
                    ->columnSpanFull()
                    ->visible(fn (?UmkmProfile $record): bool => UmkmProfileResource::isSelfService()
                        && $record?->status === ActiveStatus::Inactive),

                Section::make('Pemilik')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columnSpanFull()
                    ->hidden(fn (): bool => UmkmProfileResource::isSelfService())
                    ->schema([
                        Select::make('user_id')
                            ->label('Pemilik UMKM')
                            ->options(fn (?UmkmProfile $record): array => static::ownerOptions($record))
                            ->searchable()
                            // Pemilik berasal dari akun warga yang mengisi profilnya
                            // sendiri setelah akses diberikan. Saat edit dikunci agar
                            // kepemilikan tidak dapat dipindahkan lewat perubahan mentah.
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            // 1 warga = 1 lapak (umkm_profiles.user_id UNIQUE):
                            // beri pesan validasi alih-alih error DB.
                            ->unique(UmkmProfile::class, 'user_id', ignoreRecord: true)
                            ->helperText('Pilih warga yang usahanya sedang didaftarkan. Hanya warga yang belum memiliki profil usaha.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Profil Usaha')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_usaha')
                            ->label('Nama usaha')
                            ->placeholder('Isi nama usaha')
                            ->required()
                            ->maxLength(255)
                            // Tanpa field Status di sebelahnya, nama usaha memakai
                            // lebar penuh agar barisnya tidak menggantung setengah.
                            ->columnSpan(fn (): int|string => UmkmProfileResource::isSelfService() ? 'full' : 1),

                        // Status tayang = wewenang Operator Nagari, jadi tidak
                        // ditampilkan sama sekali kepada pemilik lapak.
                        Select::make('status')
                            ->label('Status tayang')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false)
                            ->visible(fn (): bool => ! UmkmProfileResource::isSelfService())
                            ->dehydrated(fn (): bool => ! UmkmProfileResource::isSelfService()),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi usaha')
                            ->placeholder('Isi deskripsi usaha')
                            // Opsional, tetapi kalau diisi harus menjelaskan. Batas
                            // yang sama dipakai deskripsi produk.
                            ->minLength(10)
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),

                        TextInput::make('whatsapp')
                            ->label('No. WhatsApp')
                            ->placeholder('Isi nomor WhatsApp')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            // Satu aturan format untuk admin dan self-service pemilik.
                            ->regex(PhoneNumber::REGEX)
                            ->validationMessages(['regex' => 'Isi nomor WhatsApp yang valid, contoh 08123456789.'])
                            ->helperText('Nomor tujuan pesanan pembeli.'),


                        Textarea::make('alamat')
                            // Pembeli memakai isian ini untuk benar-benar datang, jadi
                            // labelnya menyebut "lengkap" agar tidak diisi nama pasar saja.
                            ->label('Alamat lengkap usaha')
                            ->placeholder('Isikan alamat lengkap usaha')
                            ->helperText('Sertakan nama jalan atau patokan yang mudah dikenali pembeli.')
                            ->required()
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Etalase')
                    ->description('Tampilan usaha Anda di halaman publik. Semuanya opsional, tetapi lapak berfoto jauh lebih menarik.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        OptimizedSpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo usaha')
                            ->collection('logo')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1:1'])
                            ->automaticallyCropAndResizeTo(800, 800)
                            ->helperText('Dipotong otomatis menjadi persegi 800 × 800 px; gunakan editor untuk menggeser titik fokus.')
                            ->maxSize(10240),

                        OptimizedSpatieMediaLibraryFileUpload::make('sampul')
                            ->label('Sampul etalase')
                            ->collection('sampul')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['3:1'])
                            ->automaticallyCropAndResizeTo(1800, 600)
                            ->imagePreviewHeight('160')
                            ->helperText('Rasio banner 3:1. Foto portrait atau landscape dipotong otomatis menjadi 1800 × 600 px; gunakan editor untuk menggeser titik fokus.')
                            ->maxSize(10240),

                        OptimizedSpatieMediaLibraryFileUpload::make('qr')
                            ->label('Gambar QR')
                            ->collection('qr')
                            ->image()
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tautan Promosi')
                    ->description('Opsional. Tautkan media sosial atau lapak daring yang sudah Anda miliki.')
                    ->icon(Heroicon::OutlinedLink)
                    ->columnSpanFull()
                    ->schema([
                        TautanPromosiRepeater::make('tautan'),
                    ]),
            ]);
    }


    /**
     * Opsi pemilik = warga yang BELUM punya profil usaha (aktif maupun terarsip —
     * user_id unik di umkm_profiles, jadi warga ber-lapak-terarsip juga dikecualikan),
     * ter-scope ke nagari yang sedang dikelola (operator → nagarinya; super admin →
     * nagari konteks). Saat edit, pemilik record ini sendiri tetap disertakan agar
     * tidak lenyap dari daftar opsi.
     *
     * @return array<int, string>
     */
    protected static function ownerOptions(?UmkmProfile $record): array
    {
        $nagariId = auth()->user()?->managedNagariId(NagariContext::UMKM_PROFIL);

        return User::query()
            ->role('warga')
            ->where(function ($query) use ($record) {
                $query->whereNotIn('id', UmkmProfile::withTrashed()->pluck('user_id'));

                if ($record?->user_id) {
                    $query->orWhere('id', $record->user_id);
                }
            })
            ->when($nagariId, fn ($q) => $q->where('nagari_id', $nagariId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
