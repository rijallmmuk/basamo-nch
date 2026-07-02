<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\UmkmCategory;
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
            'desa_id' => Desa::factory(),
            'user_id' => User::factory()->umkmOwner(),
            'umkm_category_id' => UmkmCategory::query()->inRandomOrder()->value('id'),
            'nama_usaha' => fake()->unique()->company(),
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
