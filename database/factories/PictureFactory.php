<?php

namespace Database\Factories;

use App\Models\Picture;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Picture> */
class PictureFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'path' => 'products/img-'.fake()->unique()->numerify('##################').'.webp', 'position' => 0];
    }
}
