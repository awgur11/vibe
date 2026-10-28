<?php

namespace Database\Factories;

use App\Models\Fvalue;
use App\Models\Filter;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Fvalue> */
class FvalueFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['filter_id' => Filter::factory(), 'slag' => fake()->unique()->slug(), 'position' => 0];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Fvalue $fvalue): void {
            $fvalue->translations()->create(['lacale' => Language::ensureBase()->locale, 'title' => fake()->sentence(3)]);
        });
    }
}
