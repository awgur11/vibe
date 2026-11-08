<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
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
            'user_id' => User::factory(),
            'payment_method' => 'cash',
            'total' => '100.00',
            'status' => 'new',
        ];
    }
}
