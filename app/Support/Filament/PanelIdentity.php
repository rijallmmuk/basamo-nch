<?php

namespace App\Support\Filament;

use App\Models\User;

class PanelIdentity
{
    /**
     * @return array{roleLabel: string, contextLabel: string, contextDescription: string}
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [
                'roleLabel' => 'Panel Administrasi',
                'contextLabel' => 'Basamo NCH Smart Learning Center',
                'contextDescription' => 'Nagari Creative Hub',
            ];
        }

        $nagari = $user->nagari?->nama_lengkap ?? 'Nagari';

        return match (true) {
            $user->isSuperAdmin() => [
                'roleLabel' => 'Superadmin',
                'contextLabel' => 'Seluruh Nagari',
                'contextDescription' => 'Kendali platform dan data lintas nagari',
            ],
            $user->isDpmd() => [
                'roleLabel' => 'DPMD',
                'contextLabel' => 'Lintas Nagari',
                'contextDescription' => 'Pemantauan data dalam mode baca',
            ],
            $user->isOperator() => [
                'roleLabel' => 'Operator',
                'contextLabel' => $nagari,
                'contextDescription' => 'Ruang kerja dan data nagari Anda',
            ],
            $user->isPengajar() => [
                'roleLabel' => 'Pengajar',
                'contextLabel' => 'Pembelajaran Saya',
                'contextDescription' => 'Kelola pelatihan dan aktivitas belajar warga',
            ],
            $user->usesUmkmSelfService() => [
                'roleLabel' => 'Pelaku UMKM',
                'contextLabel' => $nagari,
                'contextDescription' => 'Kelola profil usaha dan produk Anda',
            ],
            default => [
                'roleLabel' => 'Akun Panel',
                'contextLabel' => $nagari,
                'contextDescription' => 'Ruang kerja Basamo NCH Smart Learning Center',
            ],
        };
    }
}
