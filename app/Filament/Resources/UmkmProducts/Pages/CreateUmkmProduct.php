<?php

namespace App\Filament\Resources\UmkmProducts\Pages;

use App\Filament\Resources\UmkmProducts\Schemas\UmkmProductForm;
use App\Filament\Resources\UmkmProducts\UmkmProductResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use App\Services\UmkmService;
use App\Support\NagariContext;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * Tambah produk sebagai HALAMAN, bukan modal.
 *
 * Formulirnya berisi dua bagian penuh berikut pengunggah lima foto; di dalam modal
 * semua itu berdesak-desakan dan panel unggahannya tergulung dalam kotak sempit,
 * padahal justru foto yang paling menentukan tampilan produk di etalase. Ubah
 * produk tetap memakai modal karena umumnya hanya menyentuh satu isian.
 */
class CreateUmkmProduct extends CreateRecord
{
    protected static string $resource = UmkmProductResource::class;

    public function getTitle(): string
    {
        return 'Tambah Produk';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Produk WAJIB ber-induk lapak. Pemilik hanya punya satu, jadi isian ini
            // hanya muncul untuk admin dan operator yang mengurus banyak lapak.
            Select::make('umkm_profile_id')
                ->label('Lapak / Usaha pemilik')
                ->options(fn (): array => static::lapakOptions())
                ->searchable()
                ->required(fn (): bool => ! UmkmProfileResource::isSelfService())
                ->hidden(fn (): bool => UmkmProfileResource::isSelfService())
                // Datang dari halaman sebuah lapak (`?lapak=`)? Lapaknya langsung
                // terpilih. Nilainya tetap diperiksa ulang saat simpan, jadi parameter
                // ini tidak bisa dipakai menembus batas nagari.
                ->default(fn (): ?int => UmkmProfileResource::isSelfService()
                    ? auth()->user()?->umkmProfile?->id
                    : (request()->integer('lapak') ?: null))
                ->helperText('Pilih lapak pemilik produk.')
                ->columnSpanFull(),

            ...UmkmProductForm::components(),
        ]);
    }

    /**
     * Lapak yang boleh dijadikan induk = lapak di nagari yang sedang dikelola
     * (operator → nagarinya; super admin → nagari konteks).
     *
     * @return array<int, string>
     */
    public static function lapakOptions(): array
    {
        $user = auth()->user();
        $nagariId = $user?->managedNagariId(NagariContext::UMKM_PRODUK);

        return UmkmProfile::query()
            ->when($user?->usesUmkmSelfService(), fn ($query) => $query->where('user_id', $user->getKey()))
            ->when($nagariId !== null, fn ($query) => $query->where('nagari_id', $nagariId))
            ->orderBy('nama_usaha')
            ->pluck('nama_usaha', 'id')
            ->all();
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): UmkmProduct
    {
        $user = auth()->user();

        if ($user?->usesUmkmSelfService()) {
            $profile = $user->umkmProfile;

            if (! $profile) {
                abort(403, 'Anda belum memiliki profil UMKM.');
            }

            Gate::authorize('update', $profile);

            return app(UmkmService::class)->createProduct($profile, Arr::except($data, ['umkm_profile_id']));
        }

        $nagariId = $user?->managedNagariId(NagariContext::UMKM_PRODUK);

        // Scope guard: lapak dipaksa berada di nagari yang dikelola (cegah payload
        // umkm_profile_id lintas-nagari) — di luar scope → gagal (firstOrFail).
        $profile = UmkmProfile::query()
            ->whereKey($data['umkm_profile_id'] ?? null)
            ->when($nagariId !== null, fn ($query) => $query->where('nagari_id', $nagariId))
            ->firstOrFail();

        Gate::authorize('update', $profile);

        return app(UmkmService::class)->createProduct($profile, Arr::except($data, ['umkm_profile_id']));
    }

    protected function getRedirectUrl(): string
    {
        return UmkmProductResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Simpan Produk');
    }

    protected function getFormActions(): array
    {
        // Konvensi panel: Batal selalu paling kanan, tanpa "Buat & buat lagi".
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
