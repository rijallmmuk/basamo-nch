<?php

namespace App\Console\Commands;

use App\Models\Nagari;
use App\Services\Sdg\SdgRefreshService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sdgs:refresh-kemendesa {--nagari= : ID nagari tunggal (default: semua nagari)} {--kode-bps= : Override kode BPS rujukan (misal untuk nagari pemekaran)}')]
#[Description('Tarik skor SDGs 18 poin dari API Kemendesa dan simpan ke sdg_achievements')]
class SdgsRefreshKemendesa extends Command
{
    public function handle(SdgRefreshService $service): int
    {
        if (!app()->environment('local')) {
            $this->error('Pembaruan data otomatis dari Kemendesa hanya dapat dilakukan di Localhost karena pemblokiran WAF pada server produksi.');
            return self::FAILURE;
        }

        $nagaris = $this->option('nagari')
            ? Nagari::query()->whereKey($this->option('nagari'))->get()
            : Nagari::query()->whereNotNull('wilayah_kode')->get();

        if ($nagaris->isEmpty()) {
            $this->error('Tidak ada nagari yang cocok.');

            return self::FAILURE;
        }

        $overrideBps = $this->option('kode-bps') ? (string) $this->option('kode-bps') : null;
        $berhasil = 0;
        $gagal = 0;

        foreach ($nagaris as $index => $nagari) {
            $hasil = $service->refreshNagari($nagari, $overrideBps);

            if ($hasil['status'] === 'tanpa_bps') {
                $this->warn("Lewati {$nagari->nama}: kode BPS tidak ditemukan.");
                $gagal++;

                continue;
            }

            if ($hasil['status'] === 'gagal') {
                $this->warn("Gagal ambil skor SDGs: {$nagari->nama}");
                $gagal++;

                continue;
            }

            $this->info("Berhasil: {$nagari->nama} (rata-rata {$hasil['average']})");
            $berhasil++;

            if ($index < $nagaris->count() - 1) {
                sleep(1);
            }
        }

        $this->info("Selesai: {$berhasil} nagari berhasil, {$gagal} gagal.");

        // Sukses parsial tetap SUCCESS (skor lama nagari yang gagal tetap tersimpan,
        // akan dicoba lagi refresh berikutnya) — tapi GAGAL TOTAL wajib tercermin di
        // exit code, bukan diam-diam dianggap sukses.
        return $berhasil > 0 ? self::SUCCESS : self::FAILURE;
    }
}
