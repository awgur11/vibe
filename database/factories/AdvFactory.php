<?php

namespace Database\Factories;

use App\Models\Adv;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Adv> */
class AdvFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['icon' => 'i-truck', 'position' => 0, 'public' => false];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Adv $adv): void {
            $adv->translations()->create([
                'lacale' => Language::ensureBase()->locale,
                'title' => fake()->sentence(3),
                'description' => fake()->sentence(),
            ]);
        });
    }
}
