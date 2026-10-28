<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('popular')->default(false);
        });

        $productIds = DB::table('products')
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')->from('pictures')
                    ->whereColumn('pictures.product_id', 'products.id')
                    ->whereNotNull('pictures.path')->where('pictures.path', '!=', '');
            })
            ->orderBy('id')->limit(50)->pluck('id');

        DB::table('products')->whereIn('id', $productIds)->update(['popular' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('popular');
        });
    }
};
