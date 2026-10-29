<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array DELIVERY_COLUMNS = ['delivery_method', 'delivery_city_ref', 'delivery_city', 'delivery_warehouse_ref', 'delivery_warehouse', 'pickup_address'];

    public function up(): void
    {
        Schema::create('order_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $this->deliveryColumns($table);
            $table->timestamps();
        });

        DB::table('orders')->orderBy('id')->chunkById(500, function ($orders): void {
            foreach ($orders as $order) {
                $data = array_intersect_key((array) $order, array_flip(self::DELIVERY_COLUMNS));
                if (count(array_filter($data, fn ($value): bool => $value !== null)) === 0) {
                    continue;
                }
                DB::table('order_deliveries')->insert([
                    ...$data,
                    'order_id' => $order->id,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(self::DELIVERY_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $this->deliveryColumns($table);
        });

        DB::table('order_deliveries')->orderBy('id')->chunkById(500, function ($deliveries): void {
            foreach ($deliveries as $delivery) {
                DB::table('orders')->where('id', $delivery->order_id)->update(
                    array_intersect_key((array) $delivery, array_flip(self::DELIVERY_COLUMNS))
                );
            }
        });

        Schema::dropIfExists('order_deliveries');
    }

    private function deliveryColumns(Blueprint $table): void
    {
        $table->string('delivery_method', 20)->nullable();
        $table->uuid('delivery_city_ref')->nullable();
        $table->string('delivery_city')->nullable();
        $table->uuid('delivery_warehouse_ref')->nullable();
        $table->string('delivery_warehouse', 500)->nullable();
        $table->text('pickup_address')->nullable();
    }
};
