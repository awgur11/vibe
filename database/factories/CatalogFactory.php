<?php

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Catalog> */
class CatalogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'preview' => null,
            'parent_id' => null,
            'slag' => fake()->unique()->slug(),
            'position' => 0,
            'public' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Catalog $catalog): void {
            $catalog->translations()->create([
                'lacale' => Language::ensureBase()->locale,
                'title' => fake()->sentence(3),
                'short_content' => fake()->sentence(),
                'content' => fake()->paragraph(),
            ]);
        });
    }
}
