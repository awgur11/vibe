<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_method', 20)->nullable();
            $table->uuid('delivery_city_ref')->nullable();
            $table->string('delivery_city')->nullable();
            $table->uuid('delivery_warehouse_ref')->nullable();
            $table->string('delivery_warehouse', 500)->nullable();
            $table->text('pickup_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_method', 'delivery_city_ref', 'delivery_city', 'delivery_warehouse_ref', 'delivery_warehouse', 'pickup_address']);
        });
    }
};
