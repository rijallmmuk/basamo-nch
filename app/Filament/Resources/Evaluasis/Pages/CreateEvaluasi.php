<?php

namespace App\Filament\Resources\Evaluasis\Pages;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\Concerns\RedirectsToView;
use App\Models\Module;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Gate;

abstract class CreateEvaluasi extends CreateRecord
{
    use RedirectsToView;

    protected ?bool $hasDatabaseTransactions = true;

    public ?int $lockedModuleId = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jenis ditentukan MENU tempat pengguna berada, tidak pernah diisi dari form.
        $data['jenis'] = static::getResource()::jenis();

        if ($this->lockedModuleId !== null) {
            $data['module_id'] = $this->lockedModuleId;
        }

        $module = Module::query()
            ->manageableBy(auth()->user())
            ->findOrFail($data['module_id'] ?? null);

        Gate::authorize('update', $module);
        $data['module_id'] = $module->getKey();

        $this->ingatkanBilaPelatihanSudahTerbuka($module);

        return $data;
    }

    /**
     * Pre-test adalah gerbang sebelum materi. Memasangnya pada pelatihan yang sudah
     * terbuka membuat warga yang sedang belajar harus mengerjakannya dulu, jadi
     * pengajar diberi tahu alih-alih dibiarkan menemukannya dari keluhan warga.
     */
    private function ingatkanBilaPelatihanSudahTerbuka(Module $module): void
    {
        if (static::getResource()::jenis() !== JenisEvaluasi::Pretest) {
            return;
        }

        if (! $module->pelatihan?->dapatDimasuki()) {
            return;
        }

        Notification::make()
            ->title('Pelatihan ini sudah terbuka')
            ->body('Warga yang sedang mempelajari modul ini harus mengerjakan pre-test dulu sebelum materinya kembali terbuka.')
            ->warning()
            ->persistent()
            ->send();
    }

    /**
     * Prefill modul saat datang dari tombol di halaman modul, supaya pengajar tidak
     * perlu memilih ulang modulnya. Jenisnya sudah ditentukan oleh menu.
     */
    public function mount(): void
    {
        parent::mount();

        if ($moduleId = request()->integer('module_id')) {
            $module = Module::query()->manageableBy(auth()->user())->findOrFail($moduleId);
            Gate::authorize('update', $module);
            $this->lockedModuleId = $module->getKey();
            $this->data['module_id'] = $module->getKey();
        }
    }
}
