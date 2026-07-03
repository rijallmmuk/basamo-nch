<?php

namespace Database\Factories;

use App\Models\UmkmCategory;
use App\Models\UmkmProduct;
use App\Models\UmkmProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UmkmProduct>
 */
class UmkmProductFactory extends Factory
{
    protected $model = UmkmProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'umkm_profile_id' => UmkmProfile::factory(),
            'umkm_category_id' => UmkmCategory::query()->inRandomOrder()->value('id'),
            'nama_produk' => fake()->unique()->words(3, true),
            'deskripsi' => fake()->sentence(10),
            'harga' => fake()->numberBetween(5, 500) * 1000,
            'status' => 'pending',
            'jumlah_dilihat' => fake()->numberBetween(0, 200),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'alasan_penolakan' => fake()->sentence(8),
        ]);
    }
}
