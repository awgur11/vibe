<?php

namespace Database\Seeders;

use App\Models\OrderDelivery;
use Illuminate\Database\Seeder;

class OrderDeliverySeeder extends Seeder
{
    public function run(): void
    {
        OrderDelivery::factory()->create();
    }
}
