<?php

namespace App\Filament\Resources\Desas\Schemas;

use App\Enums\ActiveStatus;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\RefWilayah;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DesaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                // ── Kolom utama ─────────────────────────────────────────────
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Data desa')
                            ->description('Ketik nama desa/kelurahan lalu pilih dari daftar resmi (Kepmendagri). Kode wilayah, wilayah administratif, dan koordinat terisi otomatis.')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->columns(2)
                            ->schema([
                                Select::make('wilayah_kode')
                                    ->label('Nama desa/kelurahan')
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->getSearchResultsUsing(fn (string $search): array => self::searchDesa($search))
                                    ->getOptionLabelUsing(fn (?string $value): ?string => self::desaLabel($value))
                                    ->unique(Desa::class, 'wilayah_kode', ignoreRecord: true)
                                    ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                                    ->helperText('Mulai ketik nama desa untuk mencari.')
                                    ->columnSpanFull()
                                    // Pilih desa → isi otomatis nama, kode internal, wilayah, koordinat.
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => self::applyWilayah($set, $state)),

                                Select::make('jenis_desa_id')
                                    ->label('Penyebutan wilayah')
                                    ->options(JenisDesa::orderBy('urutan')->pluck('nama', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('Sebutan administratif setingkat desa — mis. Desa / Kelurahan / Nagari.'),

                                Select::make('jenis_sub_unit_id')
                                    ->label('Sebutan sub-unit (opsional)')
                                    ->options(JenisSubUnit::orderBy('urutan')->pluck('nama', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('Boleh dikosongkan — admin desa dapat mengaturnya sendiri.'),

                                // Diisi otomatis dari pilihan desa (disimpan denormalized untuk display cepat).
                                Hidden::make('nama'),
                                Hidden::make('provinsi'),
                                Hidden::make('kabupaten'),
                                Hidden::make('kecamatan'),
                                Hidden::make('koordinat_lat'),
                                Hidden::make('koordinat_lng'),
                            ]),

                        Section::make('Akun admin desa')
                            ->description('Opsional. Isi username untuk membuat akun admin sekarang, atau lewati dan tambahkan nanti via Edit Desa / menu Pengguna. Login pakai username + kode OTP; wajib ganti sandi saat login pertama.')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->collapsible()
                            ->columns(2)
                            ->schema([
                                TextInput::make('admin_name')
                                    ->label('Nama admin')
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->helperText('Boleh dikosongkan — otomatis "Admin {nama desa}".'),

                                TextInput::make('admin_username')
                                    ->label('Username admin')
                                    ->maxLength(255)
                                    ->rules(['alpha_dash'])
                                    ->dehydrated(false)
                                    ->prefixIcon(Heroicon::OutlinedAtSymbol)
                                    ->rule(fn (?Model $record) => Rule::unique('users', 'username')->ignore(self::adminId($record)))
                                    ->helperText('Opsional. Tanpa spasi. Kosongkan bila belum ingin membuat akun admin.'),

                                TextInput::make('admin_kontak')
                                    ->label('Kontak admin')
                                    ->tel()
                                    ->maxLength(20)
                                    ->dehydrated(false)
                                    ->prefixIcon(Heroicon::OutlinedPhone)
                                    ->helperText('No. WhatsApp/HP admin (opsional).'),

                                TextInput::make('admin_otp')
                                    ->label('Kode OTP')
                                    ->maxLength(12)
                                    ->dehydrated(false)
                                    ->prefixIcon(Heroicon::OutlinedKey)
                                    ->helperText(fn (?Model $record): string => $record
                                        ? 'Isi untuk menerbitkan OTP baru (reset sandi admin). Kosongkan bila tak ingin mengubah.'
                                        : 'Kosongkan untuk OTP otomatis, atau isi kode sendiri.'),

                                Placeholder::make('admin_otp_current')
                                    ->label('Kode OTP saat ini')
                                    ->columnSpanFull()
                                    ->visible(fn (?Model $record): bool => $record instanceof Desa && $record->desaAdmin()->exists())
                                    ->content(function (?Model $record): string {
                                        $admin = $record instanceof Desa ? $record->desaAdmin()->first() : null;

                                        return filled($admin?->initial_otp)
                                            ? $admin->initial_otp.' — belum diganti admin'
                                            : 'Sudah diganti admin (OTP tak berlaku lagi).';
                                    }),
                            ]),
                    ]),

                // ── Sidebar ─────────────────────────────────────────────────
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->icon(Heroicon::OutlinedCheckBadge)
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
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label('Logo desa')
                                    ->collection('logo')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->helperText('Opsional. Logo kabupaten/kota otomatis dari data wilayah.'),
                            ]),
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

    /** ID akun admin desa saat ini (untuk pengecualian unique username), bila ada. */
    protected static function adminId(?Model $record): ?int
    {
        if (! $record instanceof Desa) {
            return null;
        }

        return User::where('desa_id', $record->id)->where('role', 'desa_admin')->value('id');
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
