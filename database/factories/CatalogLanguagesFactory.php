<?php

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Catalog_languages;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Catalog_languages> */
class CatalogLanguagesFactory extends Factory
{
    protected $model = Catalog_languages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'catalog_id' => Catalog::factory()->withoutAfterCreating(),
            'lacale' => Language::ensureBase()->locale,
            'title' => fake()->sentence(3),
            'short_content' => fake()->sentence(),
            'content' => fake()->paragraph(),
        ];
    }
}
