<?php

namespace Database\Factories;

use App\Models\Nagari;
use App\Models\UmkmProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UmkmProfile>
 */
class UmkmProfileFactory extends Factory
{
    protected $model = UmkmProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nagari_id' => Nagari::factory(),
            'user_id' => User::factory()->umkmOwner(),
            'nama_usaha' => fake()->unique()->company(),
            'kategori' => fake()->randomElement(['Kuliner', 'Kerajinan', 'Fashion', 'Pertanian', 'Jasa']),
            'deskripsi' => fake()->sentence(12),
            'alamat' => fake()->address(),
            'whatsapp' => fake()->numerify('08##########'),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
}
