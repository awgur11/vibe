<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductComment> */
class ProductCommentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['product_id' => Product::factory()->state(['public' => true, 'available' => 0]), 'name' => fake()->name(), 'content' => fake()->paragraph(), 'status' => 'pending'];
    }
}
