<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Filament\Resources\Concerns\RedirectsToView;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\User;
use App\Services\UmkmService;
use App\Support\NagariContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateUmkmProfile extends CreateRecord
{
    use RedirectsToView;

    protected static string $resource = UmkmProfileResource::class;

    /**
     * Warga yang belum layak memiliki lapak (mis. akunnya belum tertaut data
     * kependudukan) tidak dibiarkan mengisi form yang pasti gagal disimpan.
     * Alasannya disampaikan lebih dulu, lalu dikembalikan ke halaman awal.
     */
    public function mount(): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            NagariContext::ensureDefault(NagariContext::UMKM_PROFIL);
        }

        /** @var User|null $owner */
        $owner = auth()->user();
        $alasan = $owner && $owner->usesUmkmSelfService()
            ? app(UmkmService::class)->eligibilityError($owner)
            : null;

        if ($alasan !== null) {
            $this->beriTahuGagal($alasan);

            $this->redirect(filament()->getUrl());

            return;
        }

        parent::mount();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        $ownerId = $data['user_id'] ?? null;
        unset($data['user_id'], $data['nagari_id']);

        try {
            if (UmkmProfileResource::isSelfService()) {
                return app(UmkmService::class)->createProfileForGrantedOwner($actor, $data);
            }

            $nagariId = $actor?->managedNagariId(NagariContext::UMKM_PROFIL);

            if ($nagariId === null) {
                abort(403, 'Pilih nagari yang akan dikelola terlebih dahulu.');
            }

            $owner = User::query()
                ->role('warga')
                ->whereKey($ownerId)
                ->where('nagari_id', $nagariId)
                ->first();

            if (! $owner) {
                throw ValidationException::withMessages([
                    'user_id' => 'Warga tidak valid atau berada di luar nagari yang Anda kelola.',
                ]);
            }

            return app(UmkmService::class)->createProfileForManagedOwner($owner, $data);
        } catch (ValidationException $exception) {
            // Penjaga kelayakan melaporkan kesalahannya pada `user_id`, isian yang
            // sengaja tidak ditampilkan kepada pemilik lapak. Tanpa ditangkap di
            // sini, tombol Simpan tidak memberi reaksi apa pun.
            $this->beriTahuGagal(collect($exception->errors())->flatten()->implode(' '));

            $this->halt();
        }
    }

    private function beriTahuGagal(string $pesan): void
    {
        Notification::make()
            ->title('Profil usaha belum dapat disimpan')
            ->body($pesan)
            ->danger()
            ->persistent()
            ->send();
    }
}
