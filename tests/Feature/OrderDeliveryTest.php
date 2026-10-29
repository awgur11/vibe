<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_belongs_to_order_and_is_deleted_with_it(): void
    {
        $delivery = OrderDelivery::factory()->create();
        $order = $delivery->order;

        $this->assertTrue($order->delivery->is($delivery));
        $this->assertFalse(Schema::hasColumn('orders', 'delivery_method'));
        $order->delete();
        $this->assertModelMissing($delivery);
    }

    public function test_migration_preserves_existing_delivery_data_in_both_directions(): void
    {
        $order = Order::factory()->create();
        $legacyOrder = Order::factory()->create();
        $migration = require database_path('migrations/2026_10_28_173858_create_order_deliveries_table.php');
        $migration->down();
        $data = [
            'delivery_method' => 'nova_poshta',
            'delivery_city_ref' => '11111111-1111-4111-8111-111111111111',
            'delivery_city' => 'Київ',
            'delivery_warehouse_ref' => '22222222-2222-4222-8222-222222222222',
            'delivery_warehouse' => 'Відділення №1',
            'pickup_address' => null,
        ];
        DB::table('orders')->where('id', $order->id)->update($data);

        $migration->up();

        $this->assertDatabaseHas('order_deliveries', ['order_id' => $order->id, ...$data]);
        $this->assertDatabaseMissing('order_deliveries', ['order_id' => $legacyOrder->id]);
        $this->assertFalse(Schema::hasColumn('orders', 'delivery_method'));

        $migration->down();

        $this->assertDatabaseHas('orders', ['id' => $order->id, ...$data]);
        $this->assertFalse(Schema::hasTable('order_deliveries'));
        $migration->up();
    }
}
