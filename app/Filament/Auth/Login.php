<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    /** Subjudul brand — sejajar dengan login portal ("Portal Pembelajaran Warga"). */
    public function getSubHeading(): ?string
    {
        return 'Panel Administrasi';
    }

    /** Tombol pill + ikon, selaras tombol "Masuk" di login portal. */
    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->icon('heroicon-o-arrow-right-end-on-rectangle');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username atau Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = $data['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
