<?php

namespace Database\Seeders;

use App\Models\EwsDevice;
use App\Models\Nagari;
use Illuminate\Database\Seeder;

/**
 * Pemasangan awal perangkat EWS ke nagari, dibaca dari `config/ews.php` yang
 * tokennya datang dari environment.
 *
 * Idempotent: dijalankan berulang hanya memperbarui token, tidak menggandakan
 * perangkat (`ews_devices.nagari_id` unique).
 *
 * Nagari yang belum ada di basis data DILEWATI dengan pesan, bukan dibuatkan
 * di sini. Pembuatan nagari menyeret penyiapan operator dan penarikan data
 * eksternal; itu pekerjaan menu Nagari, bukan seeder perangkat sensor.
 */
class EwsDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = collect(config('ews.devices', []))
            ->filter(fn (mixed $token): bool => is_string($token) && trim($token) !== '');

        if ($devices->isEmpty()) {
            $this->command?->warn('EWS: tidak ada token pada environment (EWS_TOKEN_*), tidak ada perangkat yang dipasang.');

            return;
        }

        $dipasang = 0;

        foreach ($devices as $wilayahKode => $token) {
            $nagari = Nagari::query()->where('wilayah_kode', $wilayahKode)->first();

            if (! $nagari) {
                $this->command?->warn("EWS: nagari dengan kode {$wilayahKode} belum ada, perangkatnya dilewati.");

                continue;
            }

            EwsDevice::updateOrCreate(
                ['nagari_id' => $nagari->getKey()],
                ['blynk_token' => trim((string) $token)],
            );

            $dipasang++;
        }

        $this->command?->info("EWS: {$dipasang} perangkat terpasang dari {$devices->count()} token.");
    }
}
