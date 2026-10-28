<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Brand> */
class BrandFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name' => fake()->unique()->company(), 'position' => 0, 'public' => false];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Brand $brand): void {
            $brand->translations()->create([
                'lacale' => Language::ensureBase()->locale,
                'title' => fake()->sentence(3),
            ]);
        });
    }
}
