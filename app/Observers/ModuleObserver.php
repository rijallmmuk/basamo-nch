<?php

namespace App\Observers;

use App\Models\Module;
use App\Notifications\NewModulePublished;
use Illuminate\Support\Facades\Notification;

class ModuleObserver
{
    public function creating(Module $module): void
    {
        // Urutan berlaku di dalam satu pelaksanaan pelatihan.
        if (empty($module->urutan)) {
            $module->urutan = (Module::query()
                ->where('pelatihan_id', $module->pelatihan_id)
                ->max('urutan') ?? 0) + 1;
        }
    }

    /**
     * Modul TIDAK punya saklar publish lagi. Ia menjadi terlihat warga begitu berisi
     * materi pertamanya, jadi pengumumannya dipicu dari sana ({@see Materi::booted()}),
     * bukan dari perubahan status modul.
     *
     * Kirim ke warga di nagari SASARAN EFEKTIF modul, HANYA bila pelaksanaannya sedang
     * TERBUKA. Saat terkunci, warga diberi tahu nanti ketika pengelola membukanya
     * (lihat PelatihanResource::setStatus), jadi di sini diam.
     */
    public function notifyWarga(Module $module): void
    {
        $module->loadMissing('pelatihan');

        if (! $module->pelatihan?->dapatDimasuki()) {
            return;
        }

        // Kirim bertahap (notifikasi sudah ShouldQueue): bisa menyasar ribuan
        // warga lintas nagari — jangan muat semua ke memori sekaligus.
        $module->wargaSasaran()->chunkById(500, function ($users) use ($module) {
            Notification::send($users, new NewModulePublished($module));
        });
    }
}
