<?php

namespace App\Filament\Resources\UmkmProducts\Schemas;

use App\Models\UmkmCategory;
use App\Services\UmkmService;
use Filament\Forms\Components\Select;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use App\Filament\Forms\Components\TautanPromosiRepeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Field produk UMKM untuk admin/operator dan self-service pemilik: kategori,
 * nama, deskripsi, harga, serta foto wajib. Dipakai bersama oleh aksi Tambah dan
 * Ubah di ProductsRelationManager.
 *
 * TANPA field status: produk yang disimpan langsung tampil di etalase selama
 * lapaknya aktif.
 */
class UmkmProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    /**
     * Komponen mentah (bukan Schema) — dipakai ProductsRelationManager::form() lewat
     * configure(), dan langsung oleh Action::schema() (mis. EditAction "Ubah" di
     * UmkmProductsTable) yang tak menerima instance Schema.
     *
     * @return array<int, Section>
     */
    public static function components(): array
    {
        return [
            Section::make('Produk')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('umkm_category_id')
                        ->label('Kategori')
                        ->options(fn () => UmkmCategory::options())
                        ->searchable()
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('nama_produk')
                        ->label('Nama produk')
                        ->placeholder('Isi nama produk')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    // Harga disimpan sebagai bilangan bulat rupiah dan ditampilkan
                    // berpemisah ribuan di etalase. Tanpa contoh, pemilik mengetik
                    // "25.000" atau "Rp 25rb" lalu ditolak validasi tanpa tahu sebabnya.
                    TextInput::make('harga')
                        ->label('Harga')
                        ->placeholder('25000')
                        ->integer()
                        ->required()
                        ->minValue(0)
                        ->maxValue(999999999)
                        ->prefix('Rp')
                        ->helperText('Angka saja, tanpa titik atau koma. Contoh: 25000 akan tampil Rp 25.000.'),

                    Textarea::make('deskripsi')
                        ->label('Deskripsi')
                        ->placeholder('Isi deskripsi produk')
                        ->required()
                        // Batas bawah saja. Panjangnya TIDAK dibatasi: pemilik boleh
                        // menjelaskan produknya sedetail yang ia mau, dan tampilanlah
                        // yang menahan lewat pelipatan teks di halaman produk.
                        ->minLength(10)
                        ->rows(4)
                        // Tanpa teks bantuan bawaan. Yang muncul HANYA panduan yang
                        // benar-benar ditulis admin pada kategori terpilih; tanpa itu
                        // panduan tersebut tersimpan tapi tak pernah sampai ke pemilik.
                        ->helperText(fn (Get $get): ?string => static::panduanKategori($get('umkm_category_id')))
                        ->columnSpanFull(),

                    OptimizedSpatieMediaLibraryFileUpload::make('photos')
                        ->label('Foto produk')
                        ->collection('photos')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->panelLayout('grid')
                        ->minFiles(1)
                        ->maxFiles(UmkmService::MAX_PHOTOS)
                        ->maxSize(UmkmService::MAX_PHOTO_SIZE_KB)
                        // Batasnya sudah lama 5 foto dan 10 MB, tetapi tak pernah
                        // diberitahukan: pemilik baru tahu setelah unggahannya ditolak.
                        ->helperText(sprintf(
                            'JPG, PNG, atau WEBP. Maksimal %d foto, %d MB per foto.',
                            UmkmService::MAX_PHOTOS,
                            (int) (UmkmService::MAX_PHOTO_SIZE_KB / 1024),
                        ))
                        ->columnSpanFull(),
                ]),

            Section::make('Tautan Promosi')
                ->description('Tautkan tempat produk ini dijual/dipromosikan (marketplace, sosial media).')
                ->icon(Heroicon::OutlinedLink)
                ->columnSpanFull()
                ->schema([
                    TautanPromosiRepeater::make('tautan'),
                ]),
        ];
    }

    /**
     * Panduan deskripsi milik kategori terpilih, atau null bila kategori belum
     * dipilih maupun belum diberi panduan oleh admin.
     */
    public static function panduanKategori(mixed $categoryId): ?string
    {
        if (blank($categoryId)) {
            return null;
        }

        $panduan = trim((string) UmkmCategory::query()->whereKey($categoryId)->value('panduan_produk'));

        return $panduan !== '' ? $panduan : null;
    }
}
