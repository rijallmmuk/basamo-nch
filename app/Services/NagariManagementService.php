<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Nagari;
use Illuminate\Validation\ValidationException;

class NagariManagementService
{
    public function __construct(private readonly WilayahLookupService $wilayah) {}

    public function setStatus(Nagari $nagari, ActiveStatus $status): void
    {
        $nagari->update(['status' => $status]);
    }

    /**
     * Resolve ulang atribut wilayah di server agar state tersembunyi dari Livewire
     * tidak pernah menjadi sumber kebenaran data Nagari. Melempar bila kode tidak resmi
     * (dipakai saat Create, yang wajib memilih nagari resmi).
     *
     * @return array{wilayah_kode: string, nama: string, provinsi: ?string, kabupaten: ?string, kecamatan: ?string, koordinat_lat: ?float, koordinat_lng: ?float}
     */
    public function officialWilayahAttributes(string $wilayahKode): array
    {
        return $this->officialWilayahAttributesOrNull($wilayahKode)
            ?? throw ValidationException::withMessages(['wilayah_kode' => 'Pilih nagari resmi dari hasil pencarian.']);
    }

    /**
     * Varian non-fatal: kembalikan null (bukan melempar) bila kode tak dapat diresolve.
     * Dipakai saat Edit agar hilangnya entri referensi wilayah tidak memblokir penyimpanan
     * data lain (mis. hanya menambah foto sampul).
     *
     * @return array{wilayah_kode: string, nama: string, provinsi: ?string, kabupaten: ?string, kecamatan: ?string, koordinat_lat: ?float, koordinat_lng: ?float}|null
     */
    public function officialWilayahAttributesOrNull(string $wilayahKode): ?array
    {
        $wilayahData = $this->wilayah->resolve($wilayahKode);

        if ($wilayahData === null) {
            return null;
        }

        return [
            'wilayah_kode' => $wilayahKode,
            ...$wilayahData,
        ];
    }
}
