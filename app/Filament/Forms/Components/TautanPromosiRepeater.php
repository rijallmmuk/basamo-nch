<?php

namespace App\Filament\Forms\Components;

use App\Enums\TautanPlatform;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Daftar tautan promosi UMKM, dipakai form Profil Usaha maupun form Produk.
 *
 * Sebelumnya repeater ini ditulis dua kali dengan isi identik, dan keduanya hanya
 * memeriksa bahwa isian berbentuk URL. Platform tidak pernah dicocokkan dengan
 * alamatnya, sehingga pemilik dapat memilih "Instagram" lalu menempel alamat apa
 * pun; di etalase publik pengunjung melihat label Instagram yang membawanya ke
 * tempat lain. Domain sahnya sendiri hidup di {@see TautanPlatform::hosts()}.
 */
class TautanPromosiRepeater extends Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel()
            ->addActionLabel('Tambah tautan')
            ->reorderable()
            ->collapsible()
            ->defaultItems(0)
            ->columns(2)
            ->itemLabel(fn (array $state): ?string => filled($state['platform'] ?? null)
                ? (TautanPlatform::tryFrom($state['platform'])?->getLabel() ?? 'Tautan')
                : null)
            ->schema([
                Select::make('platform')
                    ->label('Platform')
                    ->options(TautanPlatform::groupedOptions())
                    ->required()
                    ->native(false)
                    ->searchable()
                    // Alamat yang sudah diketik ikut diperiksa ulang begitu
                    // platformnya diganti, bukan menunggu tombol Simpan.
                    ->live(),

                TextInput::make('url')
                    ->label('Tautan (URL)')
                    ->url()
                    ->required()
                    ->maxLength(500)
                    ->placeholder(fn (Get $get): string => TautanPlatform::tryFrom((string) $get('platform'))
                        ?->contohUrl() ?? 'https://...')
                    ->rule(fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $platform = TautanPlatform::tryFrom((string) $get('platform'));

                        // Platform kosong sudah ditangani `required` miliknya sendiri;
                        // menambah galat kedua di sini hanya membingungkan.
                        if (! $platform instanceof TautanPlatform || ! is_string($value) || trim($value) === '') {
                            return;
                        }

                        if (! $platform->menerimaUrl($value)) {
                            $fail(sprintf(
                                'Tautan ini bukan alamat %s. Contoh yang benar: %s. Pilih platform "Website" atau "Lainnya" untuk alamat lain.',
                                $platform->getLabel(),
                                $platform->contohUrl(),
                            ));
                        }
                    }),
            ]);
    }
}
