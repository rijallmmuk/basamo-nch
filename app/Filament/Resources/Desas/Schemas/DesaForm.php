<?php

namespace App\Filament\Resources\Desas\Schemas;

use App\Enums\ActiveStatus;
use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use App\Models\RefWilayah;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class DesaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn (Get $get): string => 'Data '.self::jenisName($get))
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('wilayah_kode')
                            ->label(fn (Get $get): string => 'Nama '.self::jenisName($get))
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
                            ->helperText('Ketik nama atau kode wilayah.')
                            ->columnSpanFull()
                            // Pilih desa → isi otomatis nama, kode internal, wilayah, koordinat.
                            ->afterStateUpdated(fn (Set $set, ?string $state) => self::applyWilayah($set, $state)),

                        Select::make('jenis_desa_id')
                            ->label('Penyebutan desa')
                            // Opsi = baris aktif + nilai terpilih (walau sudah dinonaktifkan
                            // lewat Data Master) — desa lama tetap bisa dibuka & disimpan.
                            ->options(fn (Get $get): array => JenisDesa::options((int) $get('jenis_desa_id') ?: null))
                            ->required()
                            ->searchable()
                            ->native(false)
                            // Live agar pratinjau "Nama admin (otomatis)" ikut berubah.
                            ->live()
                            ->helperText('Mis. Nagari, Desa, atau Kelurahan.'),

                        Select::make('jenis_sub_unit_id')
                            ->label('Sebutan sub-unit')
                            ->options(fn (Get $get): array => JenisSubUnit::options((int) $get('jenis_sub_unit_id') ?: null))
                            ->searchable()
                            ->native(false)
                            // Live agar judul & label section sub-unit ikut sebutan terpilih.
                            ->live()
                            ->helperText(fn (Get $get): string => 'Bagian dalam '.mb_strtolower(self::jenisName($get)).' — mis. Jorong, Dusun, Lingkungan. Boleh dikosongkan.'),

                        // Diisi otomatis dari pilihan desa (disimpan denormalized untuk display cepat).
                        Hidden::make('nama'),
                        Hidden::make('provinsi'),
                        Hidden::make('kabupaten'),
                        Hidden::make('kecamatan'),
                        Hidden::make('koordinat_lat'),
                        Hidden::make('koordinat_lng'),
                    ]),

                // Sekalian buat sub-unit awal saat membuat desa. Hanya saat CREATE; setelah
                // itu dikelola via "Kelola Wilayah" (agar guard anti-orphan tetap berlaku).
                // Tak dehidrasi → diproses manual di CreateDesa::handleRecordCreation.
                Section::make(fn (Get $get): string => self::subUnitName($get) ?: 'Wilayah / Sub-unit')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->schema([
                        Repeater::make('sub_units')
                            ->label(fn (Get $get): string => ($s = self::subUnitName($get)) ? "Daftar {$s} (opsional)" : 'Sub-unit awal (opsional)')
                            ->helperText(fn (Get $get): string => 'Daftar awal '.(self::subUnitName($get) ?: 'sub-unit').'. Bisa ditambah atau diubah nanti lewat "Kelola Wilayah".')
                            ->dehydrated(false)
                            ->defaultItems(0) // mulai kosong → create tanpa sub-unit tetap valid
                            ->addActionLabel(fn (Get $get): string => 'Tambah '.(self::subUnitName($get) ?: 'sub-unit'))
                            ->schema([
                                // Di DALAM repeater → pakai path '../../' agar baca sebutan di level form.
                                TextInput::make('nama')
                                    ->label(fn (Get $get): string => 'Nama '.(self::subUnitName($get, '../../jenis_sub_unit_id') ?: 'sub-unit'))
                                    // Prefix sebutan → jelas tak perlu mengetik awalannya lagi.
                                    ->prefix(fn (Get $get): string => self::subUnitName($get, '../../jenis_sub_unit_id') ?: 'Sub-unit')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('mis. Koto Tuo'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(fn (Get $get): string => 'Akun admin '.self::jenisName($get))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columnSpanFull()
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        // Username admin OTOMATIS & FIX dari kode nagari (analog warga login
                        // pakai NIK). Ditampilkan sebagai field read-only (disabled) yang ikut
                        // berubah saat "Nama desa" dipilih (di-set di applyWilayah / saat edit).
                        // Nama admin tak ditampilkan (otomatis "Admin {nama desa}" di server).
                        TextInput::make('admin_username_display')
                            ->label('Username admin')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedAtSymbol)
                            ->placeholder('Terisi otomatis')
                            ->helperText(fn (Get $get): string => 'Dipakai admin untuk login. Terisi otomatis dari kode '.mb_strtolower(self::jenisName($get)).' tanpa titik.'),

                        // Sebelah username (kolom kedua). Saat akun admin BELUM ada → editable
                        // "Kode OTP awal"; logika identik "Kode OTP awal" warga.
                        TextInput::make('admin_otp')
                            ->label('Kode OTP awal')
                            ->minLength(4)
                            ->maxLength(12)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedKey)
                            ->visible(fn (?Model $record): bool => ! ($record instanceof Desa && $record->desaAdmin()->exists()))
                            ->helperText('Opsional. Kosongkan untuk menerbitkannya nanti. Wajib diganti saat login pertama.'),

                        // Saat admin SUDAH ada (edit) → tampil read-only; reset lewat aksi.
                        TextInput::make('admin_otp_current')
                            ->label('Kode OTP')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedKey)
                            ->placeholder('Sudah diganti / belum diterbitkan')
                            ->visible(fn (?Model $record): bool => $record instanceof Desa && $record->desaAdmin()->exists())
                            ->helperText('Read-only. Terbitkan baru lewat aksi "Reset OTP Admin".'),

                        TextInput::make('admin_email')
                            ->label('Email admin')
                            ->email()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedEnvelope)
                            // Unik lintas users; abaikan akun admin desa ini sendiri saat edit.
                            ->rule(fn (?Model $record) => Rule::unique('users', 'email')
                                ->ignore($record instanceof Desa ? $record->desaAdmin()->value('id') : null))
                            ->helperText('Opsional.'),

                        TextInput::make('admin_kontak')
                            ->label('No. HP admin')
                            ->tel()
                            ->maxLength(20)
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->helperText('Opsional.'),
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
                            ->helperText(fn (Get $get): string => 'Nonaktifkan untuk menyembunyikan '.mb_strtolower(self::jenisName($get)).' tanpa menghapus.'),
                    ]),

                Section::make('Logo')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label(fn (Get $get): string => 'Logo '.self::jenisName($get))
                            ->collection('logo')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048),
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
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        // Angka/titik = kode wilayah (mis. "13.06") → jangan cocokkan fonetik,
        // SOUNDEX atas angka hanya menghasilkan derau.
        $isKode = (bool) preg_match('/^[\d.]+$/', $search);

        return DB::table('ref_wilayah as d')
            ->where('d.level', RefWilayah::LEVEL_DESA)
            // Cocok pada NAMA atau KODE; nama juga toleran ejaan mirip/serupa
            // via SOUNDS LIKE (mis. "koto tua" → "Koto Tuo", "balenka" → "Balingka").
            ->where(fn (Builder $q): Builder => $q
                ->where('d.nama', 'like', "%{$search}%")
                ->orWhere('d.kode', 'like', "%{$search}%")
                ->when(! $isKode, fn (Builder $w): Builder => $w->orWhereRaw('d.nama sounds like ?', [$search])))
            ->leftJoin('ref_wilayah as kec', 'kec.kode', '=', 'd.parent_kode')
            ->leftJoin('ref_wilayah as kab', 'kab.kode', '=', DB::raw("SUBSTRING_INDEX(d.kode, '.', 2)"))
            // Kecocokan langsung (diawali teks, lalu mengandung teks) di atas;
            // kemiripan fonetik menyusul di bawah.
            ->orderByRaw('(d.nama like ? or d.kode like ?) desc', ["{$search}%", "{$search}%"])
            ->orderByRaw('(d.nama like ? or d.kode like ?) desc', ["%{$search}%", "%{$search}%"])
            ->orderBy('d.nama')
            ->limit(50)
            ->get(['d.kode', 'd.nama', 'kec.nama as kec_nama', 'kab.nama as kab_nama'])
            ->mapWithKeys(fn (object $r): array => [
                // Sertakan kode wilayah pada label agar ikut tampil di field "Nama desa".
                $r->kode => "{$r->nama} · {$r->kec_nama}, {$r->kab_nama} — {$r->kode}",
            ])
            ->all();
    }

    /** Label desa terpilih (untuk hidrasi saat edit) — termasuk kode wilayah. */
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

        return "{$desa->nama} · {$kec}, {$kab} — {$kode}";
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

        // Pratinjau username admin (read-only) ikut kode terpilih.
        $set('admin_username_display', Desa::usernameFromKode($kode));
    }

    /**
     * Sebutan jenis desa terpilih (mis. Nagari/Desa/Kelurahan) untuk label dinamis.
     * Fallback "desa" bila belum dipilih.
     */
    protected static function jenisName(Get $get, string $fallback = 'desa'): string
    {
        $id = $get('jenis_desa_id');

        return ($id ? JenisDesa::find($id)?->nama : null) ?? $fallback;
    }

    /**
     * Sebutan sub-unit terpilih (Jorong/Dusun/…) untuk label dinamis. `$path` perlu
     * `../../jenis_sub_unit_id` saat dipanggil dari DALAM Repeater (state ter-scope ke item).
     */
    protected static function subUnitName(Get $get, string $path = 'jenis_sub_unit_id'): ?string
    {
        $id = $get($path);

        return $id ? JenisSubUnit::find($id)?->nama : null;
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
