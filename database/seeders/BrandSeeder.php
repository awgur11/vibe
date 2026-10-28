<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $locales = Language::pluck('locale')->push(config('app.locale'))->push('ru')->push('uk')->unique();
        $names = ['BOSCH', "MANN\nFILTER", 'KYB', 'TRW', 'CONTINENTAL', 'Valeo', 'NGK', 'MAHLE'];

        DB::transaction(function () use ($names, $locales): void {
            foreach ($names as $position => $name) {
                $brand = Brand::firstOrCreate(['name' => $name], ['position' => $position, 'public' => true]);
                foreach ($locales as $locale) {
                    $brand->translations()->firstOrCreate(['lacale' => $locale], ['title' => str_replace("\n", ' ', $name)]);
                }
            }
        });
    }
}
