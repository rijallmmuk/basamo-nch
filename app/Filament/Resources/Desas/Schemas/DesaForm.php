<?php

namespace App\Filament\Resources\Desas\Schemas;

use App\Enums\ActiveStatus;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\RefWilayah;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class DesaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data desa')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('wilayah_kode')
                            ->label('Nama desa')
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->getSearchResultsUsing(fn (string $search): array => self::searchDesa($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => self::desaLabel($value))
                            // Bentrok hanya dgn desa AKTIF; kode desa yang sudah diarsipkan
                            // boleh dipakai ulang (lihat migrasi unique wilayah_kode + deleted_at).
                            ->unique(Desa::class, 'wilayah_kode', ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->withoutTrashed())
                            ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                            ->helperText('Mulai ketik nama desa untuk mencari.')
                            ->columnSpanFull()
                            // Pilih desa → isi otomatis nama, kode internal, wilayah, koordinat.
                            ->afterStateUpdated(fn (Set $set, ?string $state) => self::applyWilayah($set, $state)),

                        Select::make('jenis_desa_id')
                            ->label('Penyebutan desa')
                            ->options(JenisDesa::orderBy('urutan')->pluck('nama', 'id'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            // Live agar pratinjau "Nama admin (otomatis)" ikut berubah.
                            ->live()
                            ->helperText('Sebutan administratif setingkat desa — mis. Desa / Kelurahan / Nagari.'),

                        Select::make('jenis_sub_unit_id')
                            ->label('Sebutan sub-unit')
                            ->options(JenisSubUnit::orderBy('urutan')->pluck('nama', 'id'))
                            ->searchable()
                            ->native(false)
                            ->helperText('Bagian dalam desa — mis. Jorong / Dusun / Lingkungan. Boleh dikosongkan; admin desa dapat mengaturnya sendiri.'),

                        // Diisi otomatis dari pilihan desa (disimpan denormalized untuk display cepat).
                        Hidden::make('nama'),
                        Hidden::make('provinsi'),
                        Hidden::make('kabupaten'),
                        Hidden::make('kecamatan'),
                        Hidden::make('koordinat_lat'),
                        Hidden::make('koordinat_lng'),
                    ]),

                Section::make('Akun admin desa')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columnSpanFull()
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        // Username & Nama admin FIX & OTOMATIS (tak bisa diubah): username =
                        // kode nagari (analog warga login pakai NIK), nama = "Admin {nama desa}".
                        // Read-only, ikut pilihan "Nama desa" di atas (live).
                        Placeholder::make('admin_username_display')
                            ->label('Username admin (otomatis)')
                            ->content(function (?Model $record, Get $get): string {
                                $kode = $record instanceof Desa ? $record->wilayah_kode : $get('wilayah_kode');

                                return Desa::usernameFromKode($kode) ?: '— pilih nama desa dulu —';
                            }),

                        Placeholder::make('admin_name_display')
                            ->label('Nama admin (otomatis)')
                            ->content(function (?Model $record, Get $get): string {
                                if ($record instanceof Desa) {
                                    return 'Admin '.$record->nama_lengkap;
                                }

                                $nama = $get('nama');
                                if (! $nama) {
                                    return '— pilih nama desa dulu —';
                                }

                                // Susun nama_lengkap untuk pratinjau saat create (jenis + nama).
                                $jenis = JenisDesa::find($get('jenis_desa_id'))?->nama;

                                return 'Admin '.trim(($jenis ? $jenis.' ' : '').$nama);
                            }),

                        TextInput::make('admin_email')
                            ->label('Email admin')
                            ->email()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            // Unik lintas users; abaikan akun admin desa ini sendiri saat edit.
                            ->rule(fn (?Model $record) => Rule::unique('users', 'email')
                                ->ignore($record instanceof Desa ? $record->desaAdmin()->value('id') : null))
                            ->helperText('Opsional. Disimpan huruf kecil.'),

                        TextInput::make('admin_kontak')
                            ->label('No. HP admin')
                            ->tel()
                            ->maxLength(20)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->helperText('Opsional. Boleh tulis 0812…, +62…, atau 62… — disimpan sebagai 62…'),

                        // Hanya saat akun admin BELUM ada (buat desa, atau edit desa yang
                        // belum punya admin). Admin yang sudah ada → reset lewat aksi
                        // "Reset OTP Admin". Logika identik dgn "Kode OTP awal" warga.
                        TextInput::make('admin_otp')
                            ->label('Kode OTP awal')
                            ->minLength(4)
                            ->maxLength(12)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedKey)
                            ->visible(fn (?Model $record): bool => ! ($record instanceof Desa && $record->desaAdmin()->exists()))
                            ->helperText('Opsional. Isi bila ingin menetapkan OTP sekarang; kosongkan dan terbitkan nanti lewat aksi "Reset OTP Admin" saat admin siap login. Wajib diganti saat login pertama.'),
                    ]),

                Section::make('Status')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false)
                            ->helperText('Nonaktifkan untuk menyembunyikan desa tanpa menghapus.'),
                    ]),

                Section::make('Logo')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo desa')
                            ->collection('logo')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Opsional. Logo kabupaten/kota otomatis dari data wilayah.'),
                    ]),
            ]);
    }

    /**
     * Hasil pencarian desa: "Nama · Kecamatan, Kabupaten" agar tak ambigu.
     *
     * @return array<string, string>
     */
    protected static function searchDesa(string $search): array
    {
        return DB::table('ref_wilayah as d')
            ->where('d.level', RefWilayah::LEVEL_DESA)
            ->where('d.nama', 'like', "%{$search}%")
            ->leftJoin('ref_wilayah as kec', 'kec.kode', '=', 'd.parent_kode')
            ->leftJoin('ref_wilayah as kab', 'kab.kode', '=', DB::raw("SUBSTRING_INDEX(d.kode, '.', 2)"))
            ->orderBy('d.nama')
            ->limit(50)
            ->get(['d.kode', 'd.nama', 'kec.nama as kec_nama', 'kab.nama as kab_nama'])
            ->mapWithKeys(fn (object $r): array => [
                $r->kode => "{$r->nama} · {$r->kec_nama}, {$r->kab_nama}",
            ])
            ->all();
    }

    /** Label desa terpilih (untuk hidrasi saat edit). */
    protected static function desaLabel(?string $kode): ?string
    {
        if (! $kode) {
            return null;
        }

        $desa = RefWilayah::find($kode);

        if (! $desa) {
            return null;
        }

        $kec = RefWilayah::find(self::ancestor($kode, 3))?->nama;
        $kab = RefWilayah::find(self::ancestor($kode, 2))?->nama;

        return "{$desa->nama} · {$kec}, {$kab}";
    }

    /** Isi field tersembunyi dari desa terpilih: nama, wilayah, koordinat. */
    protected static function applyWilayah(Set $set, ?string $kode): void
    {
        if (! $kode) {
            return;
        }

        $set('nama', RefWilayah::find($kode)?->nama);
        $set('provinsi', RefWilayah::find(self::ancestor($kode, 1))?->nama);
        $set('kabupaten', RefWilayah::find(self::ancestor($kode, 2))?->nama);
        $set('kecamatan', RefWilayah::find(self::ancestor($kode, 3))?->nama);

        $geo = DB::table('wilayah_boundaries')->where('kode', $kode)->first(['lat', 'lng']);
        $set('koordinat_lat', $geo->lat ?? null);
        $set('koordinat_lng', $geo->lng ?? null);
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
