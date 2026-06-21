<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\JenisDesa;
use App\Models\JenisSubUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Desa>
 */
class DesaFactory extends Factory
{
    protected $model = Desa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->city(),
            'jenis_desa_id' => JenisDesa::firstOrCreate(['nama' => 'Nagari'])->id,
            'kode' => 'NCH-'.Str::upper(Str::random(5)),
            'provinsi' => 'Sumatera Barat',
            'kabupaten' => fake()->city(),
            'kecamatan' => fake()->city(),
            'kontak' => fake()->numerify('08##########'),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }

    /** Setel penyebutan setingkat desa (mis. "Kelurahan"); dibuat bila belum ada. */
    public function jenis(string $nama): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_desa_id' => JenisDesa::firstOrCreate(['nama' => $nama])->id,
        ]);
    }

    /** Setel sebutan sub-unit (mis. "Jorong"); null = belum diatur. */
    public function subUnit(?string $nama): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_sub_unit_id' => $nama ? JenisSubUnit::firstOrCreate(['nama' => $nama])->id : null,
        ]);
    }
}
