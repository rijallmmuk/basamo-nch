<?php

namespace App\Filament\Auth;

use App\Support\PhoneNumber;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Profil admin desa. Nama admin FIX ("Admin {nama desa}", dikelola super admin lewat
 * form Desa) → field Nama sengaja dihilangkan agar tak bisa diubah. Tersedia Email,
 * No. HP (opsional), dan ganti sandi — termasuk paksa-ganti saat login pertama.
 * No. HP dinormalkan 62xxx & email huruf kecil, konsisten dgn warga.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null),

                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel()
                    ->maxLength(20)
                    ->helperText('Opsional. Boleh tulis 0812…, +62…, atau 62… — disimpan sebagai 62…')
                    ->dehydrateStateUsing(fn (?string $state): ?string => PhoneNumber::normalize($state)),

                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
