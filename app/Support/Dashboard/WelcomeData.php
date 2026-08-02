<?php

namespace App\Support\Dashboard;

use App\Models\User;
use App\Support\Filament\PanelIdentity;

/** Sapaan dasbor: salam sesuai jam WIB + inisial nama untuk avatar. */
class WelcomeData
{
    /**
     * @return array{
     *     greeting: string,
     *     name: string,
     *     initials: string,
     *     dateLabel: string,
     *     timeLabel: string,
     *     roleLabel: string,
     *     contextLabel: string,
     *     contextDescription: string
     * }
     */
    public static function forUser(?User $user): array
    {
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
            'greeting' => $greeting,
            'name' => $name,
            'initials' => self::initials($name),
            'dateLabel' => $now->translatedFormat('l, d F Y'),
            'timeLabel' => $now->format('H:i').' WIB',
            ...PanelIdentity::forUser($user),
        ];
    }

    /** Inisial dari nama (maks 2 huruf) untuk avatar. */
    private static function initials(string $name): string
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
