<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Favorite> */
class FavoriteFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['client_id' => (string) Str::uuid(), 'product_id' => Product::factory()->state(['public' => true])];
    }
}
