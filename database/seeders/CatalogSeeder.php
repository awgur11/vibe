<?php

namespace Database\Seeders;

use App\Models\Catalog;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        Catalog::factory()->count(3)->sequence(
            ['position' => 0], ['position' => 1], ['position' => 2],
        )->create();
    }
}
