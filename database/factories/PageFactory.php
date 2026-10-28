<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['type' => 'page', 'slag' => fake()->unique()->slug(), 'position' => 0, 'public' => false, 'is_menu' => false];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Page $page): void {
            $page->translations()->create([
                'lacale' => Language::ensureBase()->locale,
                'title' => fake()->sentence(),
                'content' => fake()->paragraph(),
                'tag_title' => fake()->sentence(),
                'tag_description' => fake()->sentence(),
            ]);
        });
    }
}
