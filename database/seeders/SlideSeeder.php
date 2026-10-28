<?php

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Seeder;

class SlideSeeder extends Seeder
{
    public function run(): void
    {
        Slide::factory()->count(3)->sequence(
            ['position' => 0], ['position' => 1], ['position' => 2],
        )->create();
    }
}
