<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Slide;
use App\Models\Slide_languages;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Slide_languages> */
class SlideLanguagesFactory extends Factory
{
    protected $model = Slide_languages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slide_id' => Slide::factory()->withoutAfterCreating(),
            'lacale' => Language::ensureBase()->locale,
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(),
        ];
    }
}
