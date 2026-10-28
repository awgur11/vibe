<?php

namespace Database\Seeders;

use App\Models\Filter;
use Illuminate\Database\Seeder;

class FilterSeeder extends Seeder
{
    public function run(): void
    {
        Filter::factory()->count(3)->sequence(['position' => 0], ['position' => 1], ['position' => 2])->create();
    }
}
