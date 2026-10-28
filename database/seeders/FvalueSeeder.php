<?php

namespace Database\Seeders;

use App\Models\Fvalue;
use Illuminate\Database\Seeder;

class FvalueSeeder extends Seeder
{
    public function run(): void
    {
        Fvalue::factory()->count(3)->sequence(['position' => 0], ['position' => 1], ['position' => 2])->create();
    }
}
