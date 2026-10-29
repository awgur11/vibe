<?php

namespace Database\Seeders;

use App\Models\ProductComment;
use Illuminate\Database\Seeder;

class ProductCommentSeeder extends Seeder
{
    public function run(): void
    {
        ProductComment::factory()->create();
    }
}
