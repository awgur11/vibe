<?php

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Comparison;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Comparison> */
class ComparisonFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['client_id' => (string) Str::uuid(), 'product_id' => Product::factory()->for(Catalog::factory()->state(['public' => true]))->state(['public' => true])];
    }
}
