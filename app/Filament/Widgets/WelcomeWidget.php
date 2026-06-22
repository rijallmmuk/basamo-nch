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

        $name = $user?->name ?? '';

        return [
            'super' => $super,
            'greeting' => $greeting,
            'name' => $name,
            'initials' => $this->initials($name),
            'roleBadge' => $super ? 'Super Admin' : 'Admin Desa',
            'roleLabel' => $super
                ? 'Akses seluruh desa'
                : ($user?->desa?->nama_lengkap ?? 'Desa'),
            'dateLabel' => $now->translatedFormat('l, d F Y'),
            'timeLabel' => $now->format('H:i').' WIB',
        ];
    }

    /** Inisial dari nama (maks 2 huruf) untuk avatar. */
    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_filter($parts);

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr((string) reset($parts), 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
