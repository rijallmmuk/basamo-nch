<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Nagari;
use App\Support\NagariContext;
use Livewire\Attributes\On;

/**
 * Cakupan nagari untuk widget dasbor yang menganalisis SATU nagari.
 *
 * Operator terkunci ke nagarinya sendiri. Superadmin dan DPMD memilih nagari lewat
 * pemilih di atas dasbor (keputusan user 2026-07-31): dasbor mereka TIDAK lagi
 * menyajikan rata-rata seluruh nagari, karena rata-rata lintas nagari mengaburkan
 * nagari yang tertinggal dan bukan angka yang bisa ditindaklanjuti.
 *
 * Perbandingan antar nagari tetap ada, tetapi pada widget yang memang menyandingkan
 * nagari satu per satu (Rekap Performa, Sebaran IDM), bukan dengan meratakannya.
 */
trait ScopedToNagari
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['operator', 'superadmin', 'dpmd']);
    }

    /**
     * Widget dimuat sebagai komponen Livewire sendiri, jadi ia tidak ikut tergambar
     * ulang saat properti halaman berubah. Event dari pemilih nagari-lah yang
     * memicunya.
     */
    #[On(NagariContext::DASHBOARD_EVENT)]
    public function nagariDasborDiganti(): void
    {
        // Grafik menyimpan opsinya di properti, jadi harus dihitung ulang; tabel dan
        // kartu statistik cukup tergambar ulang oleh event ini sendiri.
        if (method_exists($this, 'updateOptions')) {
            $this->updateOptions();
        }
    }

    protected function nagariId(): ?int
    {
        $user = auth()->user();

        if ($user?->isOperator()) {
            return $user->nagari_id;
        }

        if (! $user?->hasAnyRole(['superadmin', 'dpmd'])) {
            return null;
        }

        // Konteks dipastikan terisi DI SINI juga, bukan hanya di mount() halaman.
        // Widget dimuat lewat request Livewire sendiri; bila konteksnya kebetulan
        // kosong, mengembalikan null berarti widget diam-diam menampilkan seluruh
        // nagari — persis angka lintas nagari yang justru sedang dihapus.
        NagariContext::ensureDefault(NagariContext::DASHBOARD);

        return NagariContext::id(NagariContext::DASHBOARD);
    }

    protected function nagari(): ?Nagari
    {
        $id = $this->nagariId();

        return $id !== null ? Nagari::find($id) : null;
    }

    /** Nama nagari yang sedang dilihat, untuk keterangan di bawah judul widget. */
    protected function namaNagari(): string
    {
        return $this->nagari()?->nama ?? 'nagari yang dipilih';
    }
}
