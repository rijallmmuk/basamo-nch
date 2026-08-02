<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Models\User;
use App\Rules\NotInitialPassword;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Halaman Profil Pengguna Panel Filament — mendukung semua role (superadmin, operator, pengajar, dpmd, warga, umkm).
 * Menampilkan informasi identitas, lembaga, nagari, role badge, serta modal ubah profil & keamanan.
 */
class Profil extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static bool $shouldRegisterNavigation = false; // diakses lewat menu pengguna

    protected string $view = 'filament.pages.profil';

    public function getTitle(): string
    {
        return 'Profil Saya';
    }

    public function getUser(): User
    {
        return auth()->user();
    }

    private function canEditName(): bool
    {
        $user = $this->getUser();

        return $user->hasAnyRole(['superadmin', 'pengajar', 'dpmd']) && $user->penduduk_id === null;
    }

    private function canEditLembaga(): bool
    {
        return $this->getUser()->hasRole('pengajar');
    }

    private function canEditUsername(): bool
    {
        $user = $this->getUser();

        return $user->hasAnyRole(['superadmin', 'pengajar', 'dpmd']) && $user->penduduk_id === null;
    }

    /** Aksi "Ubah Profil" — dirender di header blok Profil */
    public function ubahProfilAction(): Action
    {
        $user = $this->getUser();
        $canEditName = $this->canEditName();
        $canEditLembaga = $this->canEditLembaga();

        return Action::make('ubahProfil')
            ->label('Ubah Profil')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->modalHeading('Ubah Informasi Profil')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->fillForm(fn (): array => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'lembaga' => $user->lembaga,
            ])
            ->schema(array_values(array_filter([
                $canEditName
                    ? TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->prefixIcon('heroicon-m-user')
                        ->required()
                        ->maxLength(255)
                    : null,
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->prefixIcon('heroicon-m-envelope')
                    ->email()
                    ->maxLength(255)
                    ->unique('users', 'email', ignorable: $user)
                    ->helperText('Opsional. Digunakan untuk notifikasi & pemulihan akun.'),
                TextInput::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->prefixIcon('heroicon-m-phone')
                    ->tel()
                    ->maxLength(20)
                    ->regex(PhoneNumber::REGEX)
                    ->validationMessages(['regex' => 'Isi nomor HP yang valid, mis. 08123456789.'])
                    ->helperText('Opsional. Boleh 0812…, +62…, atau 62… — disimpan sebagai 62…'),
                $canEditLembaga
                    ? TextInput::make('lembaga')
                        ->label('Asal Lembaga / Instansi')
                        ->prefixIcon('heroicon-m-building-office-2')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('mis. Universitas Andalas / Dinas Pendidikan')
                        ->helperText('Wajib untuk pengajar program LMS.')
                    : null,
            ])))
            ->action(function (array $data) use ($user, $canEditName, $canEditLembaga): void {
                $attributes = [
                    'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null,
                    'phone' => PhoneNumber::normalize($data['phone'] ?? null),
                ];

                if ($canEditName && filled($data['name'] ?? null)) {
                    $attributes['name'] = trim($data['name']);
                }

                if ($canEditLembaga && filled($data['lembaga'] ?? null)) {
                    $attributes['lembaga'] = trim($data['lembaga']);
                }

                $user->forceFill($attributes)->save();

                Notification::make()->title('Profil berhasil diperbarui.')->success()->send();
            });
    }

    /** Aksi "Ubah Keamanan" — dirender di header blok Keamanan */
    public function ubahKeamananAction(): Action
    {
        $user = $this->getUser();
        $canEditUsername = $this->canEditUsername();

        return Action::make('ubahKeamanan')
            ->label('Ubah Keamanan')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->modalHeading('Ubah Keamanan & Akses')
            ->modalSubmitActionLabel('Simpan')
            ->fillForm(fn (): array => $canEditUsername ? ['username' => $user->username] : [])
            ->schema(array_values(array_filter([
                TextInput::make('current_password')
                    ->label('Kata Sandi Saat Ini')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword()
                    ->helperText('Wajib diisi untuk verifikasi identitas Anda.'),
                $canEditUsername
                    ? TextInput::make('username')
                        ->label('Username Baru')
                        ->prefixIcon('heroicon-m-identification')
                        ->required()
                        ->alphaDash()
                        ->maxLength(255)
                        ->unique('users', 'username', ignorable: $user)
                        ->helperText('Username unik untuk autentikasi masuk.')
                    : null,
                TextInput::make('password')
                    ->label('Kata Sandi Baru')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->rules([new NotInitialPassword])
                    ->confirmed()
                    ->required(! $canEditUsername)
                    ->helperText($canEditUsername ? 'Kosongkan jika hanya ingin mengubah username.' : 'Minimal 8 karakter.'),
                TextInput::make('password_confirmation')
                    ->label('Ulangi Kata Sandi Baru')
                    ->password()
                    ->revealable(),
            ])))
            ->action(function (array $data) use ($user, $canEditUsername): void {
                $attributes = [];

                if ($canEditUsername && filled($data['username'] ?? null)) {
                    $attributes['username'] = trim($data['username']);
                }

                if (filled($data['password'] ?? null)) {
                    $attributes['password'] = $data['password'];
                }

                if ($attributes !== []) {
                    $user->forceFill($attributes)->save();

                    if (filled($data['password'] ?? null)) {
                        session()->put('password_hash_'.Auth::getDefaultDriver(), $user->getAuthPassword());
                    }
                }

                Notification::make()->title('Pengaturan keamanan berhasil diperbarui.')->success()->send();
            });
    }
}
