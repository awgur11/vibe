<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderDelivery> */
class OrderDeliveryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'delivery_method' => 'pickup',
            'pickup_address' => fake()->address(),
        ];
    }
}
