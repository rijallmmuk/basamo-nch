<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\ActiveStatus;
use App\Enums\JenisKelamin;
use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\StatusPerkawinan;
use App\Models\User;
use App\Support\PhoneNumber;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Form khusus WARGA. Akun admin (super_admin/desa_admin) dikelola lewat alur lain —
 * admin desa via form Desa, super_admin via seeder — BUKAN di resource ini. Maka tak
 * ada pilihan peran: setiap akun yang dibuat di sini adalah warga (dipaksa di server).
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Identitas pribadi (KTP). Sebagian disimpan ke `users` (name, nik),
                // sebagian ke `penduduk` (jenis_kelamin, tempat/tanggal lahir) — pemisahan
                // dilakukan WargaProvisioningService berdasar nama field, jadi field
                // demografi boleh berada di section mana pun.
                Section::make('Identitas')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->rules(['digits:16'])
                            ->helperText('NIK 16 digit — dipakai warga untuk login portal.')
                            ->unique(User::class, 'nik', ignoreRecord: true)
                            // NIK boleh dikoreksi saat edit, tapi tak boleh bentrok dengan
                            // identitas penduduk lain (1 NIK = 1 orang). Saat create, NIK yang
                            // sama dengan penduduk tanpa akun sengaja dipakai-ulang (lihat
                            // PendudukService) — jadi aturan ini hanya berlaku ketika mengedit.
                            ->rule(
                                fn (?User $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                    $exists = Penduduk::where('nik', $value)
                                        ->when($record?->penduduk_id, fn ($q) => $q->whereKeyNot($record->penduduk_id))
                                        ->exists();

                                    if ($exists) {
                                        $fail('NIK ini sudah terpakai oleh identitas warga lain.');
                                    }
                                },
                                fn (string $operation): bool => $operation === 'edit',
                            ),

                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(100)
                            ->required(static::requiredOnCreate()),

                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->maxDate(now())
                            ->required(static::requiredOnCreate()),

                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->native(false)
                            ->required(static::requiredOnCreate()),
                    ]),

                // Data sosial → disimpan ke tabel `penduduk`. Wajib lengkap saat create;
                // longgar saat edit (record lama boleh belum lengkap).
                Section::make('Data Sosial')
                    ->icon(Heroicon::OutlinedUsers)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('agama_id')
                            ->label('Agama')
                            ->options(fn (): array => Agama::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(static::requiredOnCreate()),

                        Select::make('status_perkawinan_id')
                            ->label('Status Perkawinan')
                            ->options(fn (): array => StatusPerkawinan::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->native(false)
                            ->required(static::requiredOnCreate()),

                        Select::make('pekerjaan_id')
                            ->label('Pekerjaan')
                            ->options(fn (): array => Pekerjaan::where('aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(static::requiredOnCreate()),
                    ]),

                Section::make('Alamat')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        // Desa konteks dipaksa: desa_admin → desanya; super admin yang
                        // mengelola lewat aksi "Kelola Warga" → desa yang dikelola. Field
                        // hanya tampil bila tak ada desa konteks (mis. super tanpa context).
                        Select::make('desa_id')
                            ->label('Desa')
                            ->relationship('desa', 'nama')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, callable $set) => $set('desa_unit_id', null))
                            ->placeholder('— Pilih desa —')
                            ->visible(fn (): bool => auth()->user()?->managedDesaId() === null)
                            ->required(fn (): bool => auth()->user()?->managedDesaId() === null)
                            ->columnSpanFull(),

                        Select::make('desa_unit_id')
                            ->label(fn (Get $get): string => static::wilayahLabel($get))
                            ->options(fn (Get $get): array => static::wilayahOptions($get))
                            ->searchable()
                            ->placeholder('— Pilih —')
                            // Arahkan admin bila wilayah desanya belum dikonfigurasi (cegah kebingungan
                            // dropdown kosong; pembuatan warga memang menuntut alamat lebih dulu).
                            ->helperText(function (Get $get): string {
                                if (! static::resolveDesaId($get)) {
                                    return 'Pilih desa terlebih dahulu.';
                                }

                                return empty(static::wilayahOptions($get))
                                    ? '⚠️ Belum ada wilayah untuk desa ini — tambahkan dulu di menu Wilayah sebelum membuat warga.'
                                    : 'Atur daftarnya di menu Wilayah.';
                            })
                            ->required(static::requiredOnCreate())
                            // Pertahanan server-side: wilayah harus milik desa warga.
                            ->rule(fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if (! $value) {
                                    return;
                                }

                                $desaId = static::resolveDesaId($get);

                                if (! DesaUnit::whereKey($value)->where('desa_id', $desaId)->exists()) {
                                    $fail('Wilayah tidak sesuai dengan desa.');
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Kontak')
                    ->icon(Heroicon::OutlinedPhone)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            // Simpan selalu huruf kecil agar konsisten & cegah duplikat beda kapital.
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null)
                            ->unique(User::class, 'email', ignoreRecord: true),

                        TextInput::make('phone')
                            ->label('No. HP')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Opsional. Boleh tulis 0812…, +62…, atau 62… — disimpan sebagai 62…')
                            // Normalisasi ke format internasional 62xxxx saat simpan.
                            ->dehydrateStateUsing(fn (?string $state): ?string => PhoneNumber::normalize($state)),
                    ]),

                Section::make('Akun & Status')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ActiveStatus::class)
                            ->default('active')
                            ->required()
                            ->native(false),

                        // Hanya saat Create. Di Edit, OTP diubah lewat aksi "Reset OTP"
                        // (punya efek samping: set password + flag wajib-ganti), bukan form ini.
                        TextInput::make('initial_otp')
                            ->label('Kode OTP awal')
                            ->minLength(4)
                            ->maxLength(12)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Opsional. Isi bila ingin menetapkan OTP sekarang; kosongkan dan terbitkan nanti lewat aksi "Reset OTP" saat warga siap login. Wajib diganti saat login pertama, lalu terhapus.'),
                    ]),
            ]);
    }

    /**
     * Data warga wajib lengkap saat dibuat (kecuali email & no. HP). Saat edit
     * tidak dipaksa, agar record lama yang datanya belum lengkap tetap bisa disunting.
     */
    protected static function requiredOnCreate(): Closure
    {
        return fn (string $operation): bool => $operation === 'create';
    }

    /**
     * Desa konteks: desa_admin → miliknya; super admin yang sedang mengelola sebuah
     * desa → desa itu (managedDesaId); jika tak ada konteks → pilihan di form.
     */
    protected static function resolveDesaId(Get $get): ?int
    {
        return auth()->user()?->managedDesaId()
            ?? ($get('desa_id') ? (int) $get('desa_id') : null);
    }

    /** Daftar wilayah untuk desa konteks (untuk Select alamat warga). */
    protected static function wilayahOptions(Get $get): array
    {
        $desaId = static::resolveDesaId($get);

        if (! $desaId) {
            return [];
        }

        return DesaUnit::where('desa_id', $desaId)
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /** Label field mengikuti sebutan wilayah desa (Jorong/Korong/Dusun/…). */
    protected static function wilayahLabel(Get $get): string
    {
        $desaId = static::resolveDesaId($get);

        return $desaId
            ? (Desa::find($desaId)?->jenisSubUnit?->nama ?: 'Wilayah')
            : 'Wilayah';
    }
}
