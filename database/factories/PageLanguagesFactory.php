<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Page;
use App\Models\Page_languages;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page_languages> */
class PageLanguagesFactory extends Factory
{
    protected $model = Page_languages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'lacale' => fn (): string => Language::factory()->create()->locale,
            'menu' => fake()->words(2, true),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'short_content' => fake()->sentence(),
            'tag_title' => fake()->sentence(),
            'tag_description' => fake()->sentence(),
        ];
    }
}
