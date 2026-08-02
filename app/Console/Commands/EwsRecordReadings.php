<?php

namespace App\Console\Commands;

use App\Models\EwsDevice;
use App\Services\Ews\EwsRecorderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ews:record {--device= : ID perangkat tunggal (default: semua yang aktif)} {--tanpa-pangkas : Lewati pemangkasan riwayat lama}')]
#[Description('Rekam pembacaan sensor EWS ke riwayat dan hangatkan cache halaman publik')]
class EwsRecordReadings extends Command
{
    public function handle(EwsRecorderService $recorder): int
    {
        $devices = $this->option('device')
            ? EwsDevice::query()->whereKey($this->option('device'))->with('nagari')->get()
            : EwsDevice::query()->siapPakai()->with('nagari')->get();

        if ($devices->isEmpty()) {
            $this->warn('Tidak ada perangkat EWS aktif.');

            // Bukan kegagalan: lingkungan yang memang belum memasang sensor tidak
            // boleh membuat penjadwal melaporkan error tiap beberapa menit.
            return self::SUCCESS;
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($devices as $device) {
            $reading = $recorder->rekam($device);

            if ($reading === null) {
                $this->warn("Gagal membaca: {$device->namaTampil()}");
                $gagal++;

                continue;
            }

            $this->info(sprintf(
                'Terekam: %s (air %s cm, status %s, alat %s)',
                $device->namaTampil(),
                $reading->tinggi_air ?? '-',
                $reading->status()->getLabel(),
                $reading->terhubung ? 'terhubung' : 'tidak terhubung',
            ));
            $berhasil++;
        }

        if (! $this->option('tanpa-pangkas')) {
            $terhapus = $recorder->pangkas((int) config('ews.retensi_hari'));

            if ($terhapus > 0) {
                $this->info("Riwayat lama dipangkas: {$terhapus} baris.");
            }
        }

        $this->info("Selesai: {$berhasil} terekam, {$gagal} gagal.");

        // Gagal membaca sensor BUKAN kegagalan perintah: alat mati atau jaringan
        // terputus adalah keadaan normal di lapangan, dan menjadikannya exit code
        // bukan-nol hanya akan membuat pemantau penjadwal berisik terus-menerus.
        return self::SUCCESS;
    }
}
