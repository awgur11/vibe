<?php

namespace Database\Factories;

use App\Models\Filter;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Filter> */
class FilterFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['slag' => fake()->unique()->slug(), 'position' => 0];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Filter $filter): void {
            $filter->translations()->create(['lacale' => Language::ensureBase()->locale, 'title' => fake()->sentence(3)]);
        });
    }
}
