<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_id' => (string) Str::uuid(),
            'product_id' => Product::factory()->state(['public' => true]),
            'quantity' => 1,
            'expires_at' => now()->addMonthsNoOverflow(3),
        ];
    }
}
