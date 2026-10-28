<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Slide> */
class SlideFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'preview' => null,
            'link' => fake()->url(),
            'position' => 0,
            'public' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Slide $slide): void {
            $slide->translations()->create([
                'lacale' => Language::ensureBase()->locale,
                'title' => fake()->sentence(3),
                'subtitle' => fake()->sentence(),
            ]);
        });
    }
}
