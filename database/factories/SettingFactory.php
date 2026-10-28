<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Setting> */
class SettingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['key' => fake()->unique()->slug(2), 'value' => json_encode(['title' => fake()->word()], JSON_THROW_ON_ERROR)];
    }
}
