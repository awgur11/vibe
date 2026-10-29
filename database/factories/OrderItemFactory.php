<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory()->state(['public' => true, 'price' => 100]),
            'title' => fake()->sentence(3),
            'quantity' => 1,
            'price' => '100.00',
            'total' => '100.00',
        ];
    }
}
