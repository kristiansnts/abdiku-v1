<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyLocationFactory extends Factory
{
    protected $model = CompanyLocation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement(['Kantor Pusat', 'Kantor Cabang', 'Gudang']),
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude(-7.5, -6.0),
            'longitude' => $this->faker->longitude(106.5, 107.5),
            'geofence_radius_meters' => $this->faker->randomElement([50, 100, 150, 200]),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function atLocation(float $lat, float $lng): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }
}
