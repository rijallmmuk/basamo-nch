<?php

namespace Database\Factories;

use App\Models\Desa;
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
            'jenis' => 'Nagari',
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
}
