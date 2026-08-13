<?php

namespace App\Filament\Resources\Pelatihans\Schemas;

use App\Enums\ActiveStatus;
use App\Enums\ModeSertifikat;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\TemaPelatihan;
use App\Models\User;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Forms\Components\OptimizedSpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Form pelatihan: tema dan sasaran wajib, deskripsi & cover opsional (sama seperti modul).
 * Status selalu mulai Terkunci dan diubah lewat aksi Buka/Kunci tersendiri.
 *
 * Status SENGAJA tidak ada di form. Mengubahnya wajib lewat aksi Buka/Kunci
 * ({@see PelatihanResource::setStatus()}) karena di sana ada penjaga kesiapan dan
 * pengumuman ke warga; kalau status bisa diubah dari form, keduanya terlewat.
 *
 * Tidak ada field nama terpisah. Nama tampil dirakit dari tema dan sasaran.
 * Deskripsi serta cover tetap opsional.
 */
class PelatihanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Pelatihan')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->columns(2)
                    ->schema([
                        self::temaField(),

                        Select::make('pengajars')
                            ->label('Kolaborasi (Jika ada)')
                            ->multiple()
                            ->relationship(
                                name: 'pengajars',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, ?Pelatihan $record): Builder => self::scopePengajarOptions($query)
                                    // Pemilik tidak ikut didaftar: ia pemegang pelatihan,
                                    // bukan salah satu kolaboratornya.
                                    //
                                    // Saat CREATE, $record masih null sehingga created_by
                                    // belum ada. Pemiliknya adalah pengguna yang sedang
                                    // membuat, jadi dialah yang harus dikecualikan.
                                    // Tanpa fallback ini, pembuat muncul sebagai calon
                                    // kolaborator atas pelatihannya sendiri.
                                    ->when(
                                        $record?->created_by ?? auth()->id(),
                                        fn (Builder $q, int $pemilik) => $q->whereKeyNot($pemilik),
                                    ),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->name
                                .($record->lembaga ? ' · '.$record->lembaga : ''))
                            ->searchable(['name', 'email', 'username'])
                            ->preload()
                            ->prefixIcon(Heroicon::OutlinedUserGroup)
                            ->rule(fn (): Closure => self::validPengajarSelection())
                            ->visible(fn (?Pelatihan $record): bool => self::bolehAturPengajar($record))
                            ->dehydrated(fn (?Pelatihan $record): bool => self::bolehAturPengajar($record))
                            ->placeholder('Pilih pengajar pembantu')
                            ->helperText('Pengajar lain yang membantu mengisi materi. Pelatihan tetap milik Anda.')
                            ->columnSpanFull(),
                    ]),

                self::sasaranSection(),

                Section::make('Deskripsi Pelatihan')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description('Opsional. Tulis ringkasan singkat yang membantu warga memahami pelatihan.')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Isi deskripsi pelatihan')
                            ->extraInputAttributes(['class' => 'slc-resizable-rich-editor'])
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Cover Pelatihan')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->description('Opsional. Tanpa unggahan, sistem membuat cover dari nama tema.')
                    ->columnSpanFull()
                    ->schema([
                        OptimizedSpatieMediaLibraryFileUpload::make('cover')
                            ->label('Cover Pelatihan')
                            ->collection('cover')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('4:3')
                            ->imageEditorAspectRatios(['4:3', '16:9'])
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('900')
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),

                Section::make('Sertifikat')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->description('Sertifikat baru dapat diambil warga setelah seluruh modul selesai dan seluruh Evaluasi Kegiatan lulus.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('sertifikat_mode')
                            ->label('Asal sertifikat')
                            ->options(ModeSertifikat::class)
                            ->default(ModeSertifikat::Tidak->value)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(fn (Get $get): string => self::modeSertifikat($get('sertifikat_mode'))->keterangan())
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('sertifikat')
                            ->label('Berkas sertifikat')
                            ->collection('sertifikat')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10240)
                            ->helperText('PDF, JPG, atau PNG. Maksimal 10 MB.')
                            ->required(fn (Get $get): bool => self::modeSertifikat($get('sertifikat_mode')) === ModeSertifikat::Unggah)
                            ->visible(fn (Get $get): bool => self::modeSertifikat($get('sertifikat_mode')) === ModeSertifikat::Unggah)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Mode sertifikat dari state form.
     *
     * Saat Tambah nilainya string dari Select, saat Ubah sudah berupa enum karena
     * modelnya meng-cast kolom itu. Keduanya harus diterima.
     */
    private static function modeSertifikat(mixed $state): ModeSertifikat
    {
        if ($state instanceof ModeSertifikat) {
            return $state;
        }

        return ModeSertifikat::tryFrom((string) $state) ?? ModeSertifikat::Tidak;
    }

    /**
     * SATU isian bebas. Pengajar cukup mengetik temanya:
     *   - sudah pernah dipakai  → memakai baris tema yang sudah ada (ejaan lama dipertahankan)
     *   - belum ada             → langsung dicatat saat pelatihan disimpan
     *
     * Tidak ada modal, tidak ada menu Data Master yang harus dibuka lebih dulu. Daftar
     * tema yang sudah ada muncul sebagai saran ketik (datalist) supaya pengajar cenderung
     * memakai yang sudah ada. Pencocokannya tetap di server lewat TemaNormalizer, jadi
     * beda kapital/spasi tidak melahirkan tema kembar.
     */
    private static function temaField(): TextInput
    {
        return TextInput::make('tema_nama')
            ->label('Tema Pelatihan')
            ->required()
            ->maxLength(200)
            ->prefixIcon(Heroicon::OutlinedBookOpen)
            ->columnSpanFull()
            ->datalist(fn (): array => TemaPelatihan::query()
                ->orderBy('nama')
                ->pluck('nama')
                ->all())
            ->placeholder('Isi tema pelatihan');
    }

    private static function sasaranSection(): Section
    {
        $operator = auth()->user()?->isOperator() ?? false;

        return Section::make('Sasaran Pelatihan')
            ->icon(Heroicon::OutlinedMapPin)
            ->description('Pilih warga nagari yang menerima pelatihan.')
            ->columnSpanFull()
            ->visible(! $operator)
            ->schema([
                Toggle::make('semua_nagari')
                    ->label('Berlaku untuk semua nagari')
                    ->helperText('Nagari yang ditambahkan kemudian otomatis ikut menjadi sasaran.')
                    ->default(true)
                    ->live(),

                Select::make('sasaran')
                    ->label('Nagari Sasaran')
                    ->multiple()
                    ->options(fn (): array => Nagari::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->prefixIcon(Heroicon::OutlinedHomeModern)
                    ->placeholder('Pilih nagari sasaran')
                    ->required(fn (Get $get): bool => ! $get('semua_nagari'))
                    ->visible(fn (Get $get): bool => ! $get('semua_nagari'))
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Boleh mengatur daftar kolaborator? Hanya PEMILIK pelatihan, yaitu pembuatnya,
     * serta superadmin. Kolaborator tidak dapat mengundang kolaborator lain: pengajar
     * yang ingin ikut menggarap harus meminta akses kepada pemiliknya.
     */
    private static function bolehAturPengajar(?Pelatihan $record): bool
    {
        $actor = auth()->user();

        if ($actor?->isSuperAdmin() ?? false) {
            return true;
        }

        if (! $actor?->isPengajar()) {
            return false;
        }

        return $record === null || $actor->can('kelolaKolaborator', $record);
    }

    /** @param Builder<User> $query */
    private static function scopePengajarOptions(Builder $query): Builder
    {
        return $query
            ->role('pengajar')
            ->where('status', ActiveStatus::Active);
    }

    private static function validPengajarSelection(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $ids = collect($value)->filter()->map(fn (mixed $id): int => (int) $id)->unique();

            if ($ids->isEmpty()) {
                return;
            }

            $validCount = self::scopePengajarOptions(User::query())
                ->whereKey($ids->all())
                ->count();

            if ($validCount !== $ids->count()) {
                $fail('Pilihan pengajar tidak valid atau berada di luar nagari yang boleh dikelola.');
            }
        };
    }
}
