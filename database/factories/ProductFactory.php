<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['slag' => fake()->unique()->slug(), 'price' => 0, 'discount' => 0, 'public' => false, 'position' => 0];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $product->translations()->create(['lacale' => Language::ensureBase()->locale, 'title' => fake()->sentence(3)]);
        });
    }
}
