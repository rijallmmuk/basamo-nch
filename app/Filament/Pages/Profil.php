<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Profil admin & super admin — pola "read-only dulu, ubah lewat modal" (seperti profil
 * warga). Bagian data ditampilkan read-only; penyuntingan lewat aksi header:
 *  - "Ubah Profil"   : nama (super admin saja — admin desa namanya fix), email, No. HP.
 *  - "Ubah Keamanan" : username (super admin saja — admin desa dikunci dari kode nagari)
 *                      & kata sandi; wajib verifikasi sandi lama.
 * Login pertama (OTP) tetap ditangani modal pemblokir ForcePasswordChange (bukan halaman ini).
 */
class Profil extends Page
{
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

    private function isSuper(): bool
    {
        return $this->getUser()->isSuperAdmin();
    }

    protected function getHeaderActions(): array
    {
        $user = $this->getUser();
        $isSuper = $this->isSuper();

        return [
            Action::make('ubahProfil')
                ->label('Ubah Profil')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('warning')
                ->modalHeading('Ubah Profil')
                ->modalSubmitActionLabel('Simpan')
                ->fillForm(fn (): array => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ])
                ->schema(array_values(array_filter([
                    $isSuper
                        ? TextInput::make('name')->label('Nama')->required()->maxLength(255)
                        : null,
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255)
                        ->unique('users', 'email', ignorable: $user)
                        ->helperText('Opsional.'),
                    TextInput::make('phone')
                        ->label('No. HP')
                        ->tel()
                        ->maxLength(20)
                        ->helperText('Opsional. Boleh 0812…, +62…, atau 62… — disimpan sebagai 62…'),
                ])))
                ->action(function (array $data) use ($user, $isSuper): void {
                    $attributes = [
                        'email' => filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null,
                        'phone' => PhoneNumber::normalize($data['phone'] ?? null),
                    ];

                    if ($isSuper) {
                        $attributes['name'] = $data['name'];
                    }

                    $user->forceFill($attributes)->save();

                    Notification::make()->title('Profil diperbarui.')->success()->send();
                }),

            Action::make('ubahKeamanan')
                ->label('Ubah Keamanan')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->modalHeading('Ubah Keamanan')
                ->modalSubmitActionLabel('Simpan')
                ->fillForm(fn (): array => $isSuper ? ['username' => $user->username] : [])
                ->schema(array_values(array_filter([
                    TextInput::make('current_password')
                        ->label('Sandi lama')
                        ->password()
                        ->revealable()
                        ->required()
                        ->currentPassword()
                        ->helperText('Wajib untuk mengonfirmasi perubahan.'),
                    $isSuper
                        ? TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique('users', 'username', ignorable: $user)
                        : null,
                    TextInput::make('password')
                        ->label('Sandi baru')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->confirmed()
                        ->required(! $isSuper) // admin desa: modal ini khusus ganti sandi
                        ->helperText($isSuper ? 'Kosongkan bila hanya mengubah username.' : 'Minimal 8 karakter.'),
                    TextInput::make('password_confirmation')
                        ->label('Ulangi sandi baru')
                        ->password()
                        ->revealable(),
                ])))
                ->action(function (array $data) use ($user, $isSuper): void {
                    $attributes = [];

                    if ($isSuper && filled($data['username'] ?? null)) {
                        $attributes['username'] = $data['username'];
                    }

                    if (filled($data['password'] ?? null)) {
                        $attributes['password'] = $data['password'];
                    }

                    if ($attributes !== []) {
                        $user->forceFill($attributes)->save();
                    }

                    Notification::make()->title('Keamanan diperbarui.')->success()->send();
                }),
        ];
    }
}
