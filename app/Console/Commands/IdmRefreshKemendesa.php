<?php

namespace App\Console\Commands;

use App\Models\Nagari;
use App\Services\Idm\IdmRefreshService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('idm:refresh-kemendesa {--nagari= : ID nagari tunggal (default: semua nagari)} {--tahun= : Tahun spesifik (default: cari otomatis tahun terbaru)}')]
#[Description('Tarik status IDM dari API Kemendesa dan simpan ke idm_statuses')]
class IdmRefreshKemendesa extends Command
{
    public function handle(IdmRefreshService $service): int
    {
        if (!app()->environment('local')) {
            $this->error('Pembaruan data otomatis dari Kemendesa hanya dapat dilakukan di Localhost karena pemblokiran WAF pada server produksi.');
            return self::FAILURE;
        }

        $tahun = $this->option('tahun') ? (int) $this->option('tahun') : null;

        $nagaris = $this->option('nagari')
            ? Nagari::query()->whereKey($this->option('nagari'))->get()
            : Nagari::query()->whereNotNull('wilayah_kode')->get();

        if ($nagaris->isEmpty()) {
            $this->error('Tidak ada nagari yang cocok.');

            return self::FAILURE;
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($nagaris as $index => $nagari) {
            $hasil = $service->refreshNagari($nagari, $tahun);

            if ($hasil['status'] === 'tanpa_kode') {
                $this->warn("Lewati {$nagari->nama}: wilayah_kode kosong.");
                $gagal++;

                continue;
            }

            if ($hasil['status'] === 'gagal') {
                $this->warn("Gagal ambil IDM: {$nagari->nama}");
                $gagal++;

                continue;
            }

            $this->info("Berhasil: {$nagari->nama} (IDM {$hasil['tahun']})");
            $berhasil++;

            if ($index < $nagaris->count() - 1) {
                sleep(1);
            }
        }

        $this->info("Selesai: {$berhasil} nagari berhasil, {$gagal} gagal.");

        return $berhasil > 0 ? self::SUCCESS : self::FAILURE;
    }
}
