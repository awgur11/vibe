<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'locale' => fake()->unique()->lexify('q??'),
            'short' => strtoupper(fake()->lexify('???')),
            'title' => fake()->word(),
            'active' => true,
        ];
    }
}
