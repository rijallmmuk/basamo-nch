<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use App\Models\Pelatihan;
use App\Services\SlcModuleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Gate;

class CreateModule extends CreateRecord
{
    use RedirectsToView;

    protected static string $resource = ModuleResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public ?int $lockedPelatihanId = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    public function mount(): void
    {
        $actor = auth()->user();

        // Setiap modul WAJIB bernaung pada satu pelatihan. Pengelola yang belum punya
        // pelatihan sebelumnya hanya disodori pilihan kosong tanpa penjelasan, jadi
        // ia diarahkan membuat pelatihannya lebih dulu.
        //
        // DPMD/warga tetap jatuh ke parent::mount() agar penolakan otorisasinya
        // berjalan seperti biasa, bukan berubah menjadi pengalihan.
        if (($actor?->isPengajar() || $actor?->isOperator() || $actor?->isSuperAdmin())
            && ! request()->integer('pelatihan')
            && Pelatihan::query()->manageableBy($actor)->doesntExist()) {
            Notification::make()
                ->title('Buat pelatihan terlebih dahulu')
                ->body('Modul selalu berada di dalam sebuah pelatihan. Buat pelatihannya dulu, lalu tambahkan modul dari halaman pelatihan tersebut.')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(PelatihanResource::getUrl('create'));

            return;
        }

        parent::mount();

        if ($pelatihanId = request()->integer('pelatihan')) {
            $pelatihan = Pelatihan::query()
                ->manageableBy(auth()->user())
                ->findOrFail($pelatihanId);

            Gate::authorize('kelolaKonten', $pelatihan);

            $this->lockedPelatihanId = $pelatihan->getKey();
            $this->data['pelatihan_id'] = $pelatihan->getKey();
        }
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->lockedPelatihanId !== null) {
            $data['pelatihan_id'] = $this->lockedPelatihanId;
        }

        return app(SlcModuleService::class)->authoringData($data, auth()->user());
    }
}
