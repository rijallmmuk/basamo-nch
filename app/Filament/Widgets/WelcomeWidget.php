<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Kartu sambutan ringkas & profesional di dasbor (pengganti AccountWidget bawaan
 * yang memuat tombol Sign out redundan — keluar sudah tersedia di menu avatar).
 */
class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $super = (bool) $user?->isSuperAdmin();
        $now = now()->timezone('Asia/Jakarta');
        $hour = (int) $now->format('H');

        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return [
            'greeting' => $greeting,
            'name' => $user?->name ?? '',
            'roleLabel' => $super
                ? 'Super Admin · akses seluruh desa'
                : 'Admin '.($user?->desa?->nama_lengkap ?? 'Desa'),
            'dateLabel' => $now->translatedFormat('l, d F Y'),
            'accent' => $super ? '#6366f1' : '#14b8a6', // indigo-500 / teal-500
        ];
    }
}
