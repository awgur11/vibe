<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_id' => (string) Str::uuid(),
            'customer_type' => 'individual',
            'name' => fake()->name(),
            'phone' => '+380501234567',
            'email' => fake()->safeEmail(),
            'payment_method' => 'cash',
            'total' => '100.00',
            'status' => 'new',
        ];
    }
}
