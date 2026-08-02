<?php

namespace App\Filament\Resources\BackofficeUsers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class BackofficeUserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Akun & Identitas')
                ->icon(Heroicon::OutlinedUser)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama Lengkap')
                        ->weight(FontWeight::Bold)
                        ->icon('heroicon-m-user')
                        ->iconColor('primary'),

                    TextEntry::make('username')
                        ->label('Username')
                        ->icon('heroicon-m-identification')
                        ->iconColor('gray')
                        ->copyable(),

                    TextEntry::make('email')
                        ->label('Email')
                        ->icon('heroicon-m-envelope')
                        ->iconColor('gray')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('phone')
                        ->label('No. HP / WhatsApp')
                        ->icon('heroicon-m-phone')
                        ->iconColor('success')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('lembaga')
                        ->label('Lembaga / Instansi')
                        ->icon('heroicon-m-building-office-2')
                        ->placeholder('—'),

                    TextEntry::make('status')
                        ->label('Status Akun')
                        ->badge(),
                ]),

            Section::make('Hak Akses & Lingkup Otorisasi')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ])
                ->schema([
                    TextEntry::make('roles.name')
                        ->label('Peran')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d M Y, H:i')
                        ->icon('heroicon-m-calendar'),

                    TextEntry::make('updated_at')
                        ->label('Diperbarui Pada')
                        ->dateTime('d M Y, H:i')
                        ->icon('heroicon-m-clock'),
                ]),
        ]);
    }
}
