<?php

namespace App\Filament\Resources\Penduduks\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\Penduduks\Concerns\BelongsToNagariContext;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Models\Nagari;
use App\Services\WargaProvisioningService;
use App\Support\NagariContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePenduduk extends CreateRecord
{
    use BelongsToNagariContext, RedirectsToView {
        RedirectsToView::getRedirectUrl insteadof BelongsToNagariContext;
    }

    protected static string $resource = PendudukResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $nagariId = auth()->user()?->managedNagariId(NagariContext::WARGA)
            ?? (int) ($data['nagari_id'] ?? 0);

        if (! Nagari::query()->whereKey($nagariId)->exists()) {
            throw ValidationException::withMessages([
                'nagari_id' => 'Pilih nagari yang valid sebelum membuat warga.',
            ]);
        }

        return app(WargaProvisioningService::class)->create($data, $nagariId);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Warga dan akun login dibuat')
            ->body("NIK {$this->record->nik}. Akun role warga aktif dan wajib mengganti password setelah login pertama.")
            ->success()
            ->persistent()
            ->send();
    }
}
