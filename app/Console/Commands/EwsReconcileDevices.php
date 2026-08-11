<?php

namespace App\Console\Commands;

use App\Models\EwsDevice;
use App\Models\Nagari;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Selaraskan ikatan perangkat EWS ke nagari menurut `config/ews.php`.
 *
 * Bedanya dengan EwsDeviceSeeder, dan inilah sebabnya perintah ini ada: seeder
 * memperbaiki pemetaan dengan MENIMPA token pada baris perangkat yang ada,
 * sedangkan perintah ini MEMINDAHKAN ikatan nagarinya dan tidak pernah menyentuh
 * token. Perbedaannya menentukan nasib riwayat.
 *
 * `ews_readings` menempel pada `ews_device_id`, dan yang menghasilkan pembacaan
 * itu alat fisik, bukan nagarinya. Kalau tokennya yang ditimpa, seluruh riwayat
 * lama tetap tergantung pada baris yang sekarang mewakili alat lain, yaitu
 * tinggi air satu sungai terbaca sebagai sungai nagari sebelah. Kalau ikatan
 * nagarinya yang dipindah, baris tetap mewakili alat yang sama sepanjang
 * hidupnya dan riwayatnya ikut benar dengan sendirinya, tanpa satu pun baris
 * pembacaan disentuh.
 *
 * Dipakai saat alat terpasang tertukar antar nagari di lapangan: tukar nilai
 * EWS_TOKEN_* di environment sesuai kenyataan, lalu jalankan perintah ini.
 *
 * IDEMPOTEN. Ia membandingkan keadaan basis data dengan environment lalu
 * memperbaiki selisihnya, jadi menjalankannya dua kali tidak menukar balik.
 */
#[Signature('ews:reconcile {--pura-pura : Tampilkan rencana perubahan tanpa menyimpannya}')]
#[Description('Selaraskan ikatan perangkat EWS ke nagari menurut environment, tanpa mengubah token maupun riwayat')]
class EwsReconcileDevices extends Command
{
    public function handle(): int
    {
        $tujuan = $this->tujuanDariConfig();

        if ($tujuan->isEmpty()) {
            $this->warn('EWS: tidak ada token pada environment (EWS_TOKEN_*), tidak ada yang bisa diselaraskan.');

            return self::SUCCESS;
        }

        $devices = EwsDevice::query()->with('nagari')->orderBy('id')->get();

        if ($devices->isEmpty()) {
            $this->warn('EWS: belum ada perangkat di basis data. Jalankan db:seed --class=EwsDeviceSeeder lebih dahulu.');

            return self::SUCCESS;
        }

        $pindah = $this->rencanaPindah($devices, $tujuan);

        if ($pindah->isEmpty()) {
            $this->info('EWS: ikatan perangkat sudah selaras dengan environment.');

            return self::SUCCESS;
        }

        foreach ($pindah as ['device' => $device, 'dari' => $dari, 'ke' => $ke]) {
            $this->line(sprintf(
                'Perangkat #%d: %s  ->  %s',
                $device->getKey(),
                $dari?->nama ?? '(tanpa nagari)',
                $ke->nama,
            ));
        }

        if ($this->option('pura-pura')) {
            $this->info('Pura-pura: tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        $singgahan = $this->nagariSinggahan($devices);

        if (! $singgahan instanceof Nagari) {
            $this->error(
                'EWS: butuh satu nagari tanpa perangkat sebagai tempat singgah sementara, '
                .'karena ews_devices.nagari_id unique dan pertukaran melingkar tidak bisa '
                .'dikerjakan langsung. Semua nagari saat ini sudah punya perangkat.',
            );

            return self::FAILURE;
        }

        DB::transaction(fn () => $this->jalankanPindah($pindah, $singgahan));

        $this->info("EWS: {$pindah->count()} ikatan perangkat diselaraskan. Riwayat pembacaan tidak disentuh.");
        $this->comment('Jalankan `php artisan ews:record` agar cache pembacaan ikut segar.');

        return self::SUCCESS;
    }

    /**
     * Token yang dikonfigurasi -> nagari yang seharusnya memegangnya.
     *
     * Kuncinya token dan bukan kode wilayah, karena pertanyaan yang mau dijawab
     * adalah "alat yang memegang token ini sebenarnya milik nagari mana".
     *
     * @return Collection<string, Nagari>
     */
    private function tujuanDariConfig(): Collection
    {
        $tujuan = collect();

        foreach (config('ews.devices', []) as $wilayahKode => $token) {
            if (! is_string($token) || trim($token) === '') {
                continue;
            }

            $nagari = Nagari::query()->where('wilayah_kode', $wilayahKode)->first();

            if (! $nagari) {
                $this->warn("EWS: nagari dengan kode {$wilayahKode} belum ada, tokennya dilewati.");

                continue;
            }

            $tujuan->put(trim($token), $nagari);
        }

        return $tujuan;
    }

    /**
     * Perangkat yang ikatan nagarinya meleset dari environment.
     *
     * Perangkat yang tokennya tidak dikenali environment DILEWATI, bukan
     * dianggap salah tempat. Token yang dipasang manual di luar EWS_TOKEN_*
     * adalah keadaan sah, dan perintah ini tidak berhak memindahkannya.
     *
     * @param  Collection<int, EwsDevice>  $devices
     * @param  Collection<string, Nagari>  $tujuan
     * @return Collection<int, array{device: EwsDevice, dari: ?Nagari, ke: Nagari}>
     */
    private function rencanaPindah(Collection $devices, Collection $tujuan): Collection
    {
        return $devices
            ->map(function (EwsDevice $device) use ($tujuan): ?array {
                $token = trim((string) $device->blynk_token);
                $ke = $tujuan->get($token);

                if (! $ke instanceof Nagari) {
                    $this->warn(
                        "EWS: token perangkat #{$device->getKey()} tidak ada di environment, dilewati.",
                    );

                    return null;
                }

                if ($ke->getKey() === $device->nagari_id) {
                    return null;
                }

                return ['device' => $device, 'dari' => $device->nagari, 'ke' => $ke];
            })
            ->filter()
            ->values();
    }

    /**
     * Nagari yang belum punya perangkat, dipakai sebagai tempat singgah.
     *
     * `nagari_id` unique membuat pertukaran melingkar (A ke B sementara B masih
     * di B) mustahil dikerjakan langsung, dan kolomnya tidak boleh null. Satu
     * slot kosong sudah cukup untuk permutasi sepanjang apa pun: satu perangkat
     * disingirkan ke sana, sisanya bergeser mengisi tempat yang terbuka.
     *
     * @param  Collection<int, EwsDevice>  $devices
     */
    private function nagariSinggahan(Collection $devices): ?Nagari
    {
        return Nagari::query()
            ->whereNotIn('id', $devices->pluck('nagari_id')->all())
            ->orderBy('id')
            ->first();
    }

    /**
     * Geser ikatan satu per satu tanpa pernah melanggar unique `nagari_id`:
     * singkirkan perangkat pertama ke nagari singgahan, pindahkan setiap
     * perangkat yang tujuannya sudah kosong, lalu tarik yang disingkirkan ke
     * tempat semestinya.
     *
     * @param  Collection<int, array{device: EwsDevice, dari: ?Nagari, ke: Nagari}>  $pindah
     */
    private function jalankanPindah(Collection $pindah, Nagari $singgahan): void
    {
        $antre = $pindah->all();
        $disingkirkan = array_shift($antre);

        $this->ikat($disingkirkan['device'], $singgahan->getKey());

        while ($antre !== []) {
            $bergerak = false;

            foreach ($antre as $i => $langkah) {
                $ditempati = EwsDevice::query()
                    ->where('nagari_id', $langkah['ke']->getKey())
                    ->exists();

                if ($ditempati) {
                    continue;
                }

                $this->ikat($langkah['device'], $langkah['ke']->getKey());
                unset($antre[$i]);
                $bergerak = true;
            }

            // Jaga-jaga: tanpa ini, rencana yang entah bagaimana saling mengunci
            // akan berputar selamanya alih-alih menggagalkan transaksi.
            if (! $bergerak) {
                throw new \RuntimeException('EWS: rencana pemindahan saling mengunci, tidak ada perubahan yang disimpan.');
            }
        }

        $this->ikat($disingkirkan['device'], $disingkirkan['ke']->getKey());

        // Satu catatan per perangkat, ditulis setelah semua langkah selesai,
        // supaya log aktivitas memuat perpindahan yang sebenarnya terjadi dan
        // bukan mampir sesaat ke nagari singgahan yang tidak berarti apa-apa.
        foreach ($pindah as ['device' => $device, 'dari' => $dari, 'ke' => $ke]) {
            activity('ews')
                ->performedOn($device)
                ->withProperties([
                    'attributes' => ['nagari_id' => $ke->getKey(), 'nagari' => $ke->nama],
                    'old' => ['nagari_id' => $dari?->getKey(), 'nagari' => $dari?->nama],
                ])
                ->log('Ikatan nagari perangkat EWS diselaraskan dengan environment');
        }
    }

    /**
     * Simpan tanpa membangunkan LogsActivity: langkah mekanis menuju dan dari
     * nagari singgahan bukan peristiwa yang berarti bagi pembaca log.
     */
    private function ikat(EwsDevice $device, int $nagariId): void
    {
        $device->forceFill(['nagari_id' => $nagariId])->saveQuietly();
    }
}
