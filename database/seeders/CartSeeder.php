<?php

namespace Database\Seeders;

use App\Models\Cart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        Cart::factory()->count(3)->create(['client_id' => (string) Str::uuid()]);
    }
}
