<?php

namespace App\Observers;

use App\Models\Module;
use App\Models\User;
use App\Notifications\NewModulePublished;
use Illuminate\Support\Facades\Notification;

class ModuleObserver
{
    public function creating(Module $module): void
    {
        // Auto-urut: modul baru ditaruh di urutan terakhir.
        if (empty($module->order)) {
            $module->order = (Module::max('order') ?? 0) + 1;
        }
    }

    public function created(Module $module): void
    {
        if ($module->status === 'published') {
            $this->notifyWarga($module);
        }
    }

    public function updated(Module $module): void
    {
        // Hanya saat status berubah menjadi published.
        if ($module->wasChanged('status') && $module->status === 'published') {
            $this->notifyWarga($module);
        }
    }

    /**
     * Kirim notifikasi ke warga terkait: modul global → semua warga,
     * modul lokal → warga nagari tsb saja.
     */
    private function notifyWarga(Module $module): void
    {
        $query = User::query()
            ->whereIn('role', ['warga', 'umkm_owner'])
            ->where('status', 'active');

        if ($module->nagari_id !== null) {
            $query->where('nagari_id', $module->nagari_id);
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new NewModulePublished($module));
        }
    }
}
