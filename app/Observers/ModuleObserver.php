<?php

namespace App\Observers;

use App\Enums\ModuleStatus;
use App\Models\Module;
use App\Models\User;
use App\Notifications\NewModulePublished;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ModuleObserver
{
    public function creating(Module $module): void
    {
        // Auto-urut: modul baru ditaruh di urutan terakhir.
        if (empty($module->sort_order)) {
            $module->sort_order = (Module::max('sort_order') ?? 0) + 1;
        }
    }

    public function deleting(Module $module): void
    {
        // Force-delete men-cascade module_pages di level DB (lewati event Eloquent),
        // jadi bersihkan file PDF-nya di sini sebelum baris terhapus.
        if ($module->isForceDeleting()) {
            $module->pages()->whereNotNull('file_path')->pluck('file_path')
                ->each(fn ($path) => Storage::disk('public')->delete($path));
        }
    }

    public function created(Module $module): void
    {
        if ($module->status === ModuleStatus::Published) {
            $this->notifyWarga($module);
        }
    }

    public function updated(Module $module): void
    {
        // Hanya saat status berubah menjadi published.
        if ($module->wasChanged('status') && $module->status === ModuleStatus::Published) {
            $this->notifyWarga($module);
        }
    }

    /**
     * Kirim notifikasi ke warga terkait: modul global → semua warga,
     * modul lokal → warga desa tsb saja.
     */
    private function notifyWarga(Module $module): void
    {
        $query = User::query()
            ->where('role', 'warga')
            ->where('status', 'active');

        if ($module->desa_id !== null) {
            $query->where('desa_id', $module->desa_id);
        }

        // Kirim bertahap (notifikasi sudah ShouldQueue): modul global bisa
        // menyasar ribuan warga — jangan muat semua ke memori sekaligus.
        $query->chunkById(500, function ($users) use ($module) {
            Notification::send($users, new NewModulePublished($module));
        });
    }
}
