<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Services\NovaPoshtaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class NovaPoshtaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.nova_poshta.key' => 'private-test-api-key']);
        Http::preventStrayRequests();
    }

    public function test_city_autocomplete_returns_at_most_ten_options_and_keeps_the_key_on_the_server(): void
    {
        $cities = array_map(fn (int $index): array => ['Ref' => (string) Str::uuid(), 'Present' => 'Місто '.$index], range(1, 15));
        Http::fake([NovaPoshtaService::ENDPOINT => Http::response(['success' => true, 'data' => [['Addresses' => $cities]]])]);
        $url = route('delivery.nova_poshta.cities', ['q' => 'Ки']);
        $this->getJson($url)->assertOk()->assertJsonCount(10, 'cities')
            ->assertJsonPath('cities.0.label', 'Місто 1')->assertDontSee('private-test-api-key');
        $this->getJson($url)->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request): bool => $request['apiKey'] === 'private-test-api-key'
            && $request['calledMethod'] === 'searchSettlements' && $request['methodProperties']['Limit'] === 10
            && $request['methodProperties']['CityName'] === 'Ки');
    }

    public function test_warehouses_are_loaded_by_settlement_ref_and_pagination_is_not_truncated(): void
    {
        $cityRef = (string) Str::uuid();
        $firstPage = array_map(fn (int $index): array => $this->warehouseData($cityRef, (string) Str::uuid(), 'Відділення №'.$index), range(1, 500));
        $last = $this->warehouseData($cityRef, (string) Str::uuid(), 'Відділення №501');
        Http::fake(fn (ClientRequest $request) => Http::response([
            'success' => true, 'data' => $request['methodProperties']['Page'] === 1 ? $firstPage : [$last],
        ]));
        $url = route('delivery.nova_poshta.warehouses', ['city_ref' => $cityRef]);
        $this->getJson($url)->assertOk()->assertJsonCount(501, 'warehouses')->assertJsonPath('warehouses.500.label', 'Відділення №501');
        $this->getJson($url)->assertOk();
        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request): bool => $request['calledMethod'] === 'getWarehouses'
            && $request['methodProperties']['SettlementRef'] === $cityRef && $request['methodProperties']['Language'] === 'UA');
    }

    public function test_invalid_lookup_parameters_are_rejected_without_external_requests(): void
    {
        $this->getJson(route('delivery.nova_poshta.cities', ['q' => 'К']))->assertUnprocessable();
        $this->getJson(route('delivery.nova_poshta.warehouses', ['city_ref' => 'invalid']))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_missing_key_and_provider_errors_have_safe_responses(): void
    {
        config(['services.nova_poshta.key' => null]);
        $url = route('delivery.nova_poshta.cities', ['q' => 'Ки']);
        $this->getJson($url)->assertStatus(503)->assertJsonStructure(['message']);
        Http::assertNothingSent();
        config(['services.nova_poshta.key' => 'private-test-api-key']);
        Http::fake([NovaPoshtaService::ENDPOINT => Http::response(['success' => false, 'errors' => ['private-test-api-key']])]);
        $this->getJson($url)->assertStatus(503)->assertDontSee('private-test-api-key');
    }

    public function test_network_failure_has_a_safe_response_and_can_be_retried(): void
    {
        $fails = true;
        Http::fake(function () use (&$fails) {
            if ($fails) {
                throw new ConnectionException('Network failure');
            }

            return Http::response(['success' => true, 'data' => [['Addresses' => []]]]);
        });
        $this->getJson(route('delivery.nova_poshta.cities', ['q' => 'Ки']))->assertStatus(503)->assertDontSee('Network failure');
        $fails = false;
        $this->getJson(route('delivery.nova_poshta.cities', ['q' => 'Ки']))->assertOk()->assertJson(['cities' => []]);
    }

    public function test_pickup_shows_and_saves_the_shop_address_without_calling_nova_poshta(): void
    {
        Setting::factory()->create(['key' => 'address', 'value' => json_encode([config('app.locale') => 'Львів, Тестова 42'])]);
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->get(route('order.checkout'))->assertOk()
            ->assertSee('data-pickup-address', false)->assertSee('Львів, Тестова 42')->assertDontSee('private-test-api-key');
        $this->post(route('order.store'), $this->customerData([
            'delivery_method' => 'pickup', 'pickup_address' => 'Forged address', 'delivery_city_ref' => 'invalid', 'delivery_warehouse_ref' => 'invalid',
        ]))->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('pickup', $order->delivery->delivery_method);
        $this->assertSame('Львів, Тестова 42', $order->delivery->pickup_address);
        $this->assertNull($order->delivery->delivery_city_ref);
        $this->assertNull($order->delivery->delivery_warehouse_ref);
        Http::assertNothingSent();
    }

    public function test_nova_poshta_order_saves_only_canonical_city_and_warehouse_data(): void
    {
        $cityRef = (string) Str::uuid();
        $warehouseRef = (string) Str::uuid();
        Http::fake([NovaPoshtaService::ENDPOINT => Http::response([
            'success' => true, 'data' => [$this->warehouseData($cityRef, $warehouseRef, 'Відділення №1: Тестова 12')],
        ])]);
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), $this->customerData([
            'delivery_method' => 'nova_poshta', 'delivery_city_ref' => $cityRef, 'delivery_warehouse_ref' => $warehouseRef,
            'delivery_city' => 'Forged city', 'delivery_warehouse' => 'Forged warehouse', 'pickup_address' => 'Forged address',
        ]))->assertRedirect(route('order.checkout'))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('nova_poshta', $order->delivery->delivery_method);
        $this->assertSame($cityRef, $order->delivery->delivery_city_ref);
        $this->assertSame('Київ', $order->delivery->delivery_city);
        $this->assertSame($warehouseRef, $order->delivery->delivery_warehouse_ref);
        $this->assertSame('Відділення №1: Тестова 12', $order->delivery->delivery_warehouse);
        $this->assertNull($order->delivery->pickup_address);
        $this->assertModelMissing($cart);
    }

    public function test_a_warehouse_from_another_city_is_rejected_without_clearing_the_cart(): void
    {
        $cityRef = (string) Str::uuid();
        $warehouseRef = (string) Str::uuid();
        Http::fake([NovaPoshtaService::ENDPOINT => Http::response([
            'success' => true, 'data' => [$this->warehouseData((string) Str::uuid(), $warehouseRef, 'Відділення іншого міста')],
        ])]);
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), $this->customerData([
            'delivery_method' => 'nova_poshta', 'delivery_city_ref' => $cityRef, 'delivery_warehouse_ref' => $warehouseRef,
        ]))->assertSessionHasErrors('delivery_warehouse_ref');
        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($cart);
    }

    public function test_manual_city_text_without_a_selected_city_and_warehouse_cannot_be_submitted(): void
    {
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), $this->customerData([
            'delivery_method' => 'nova_poshta', 'delivery_city' => 'Київ',
        ]))->assertSessionHasErrors(['delivery_city_ref', 'delivery_warehouse_ref']);
        Http::assertNothingSent();
        $this->assertModelExists($cart);
    }

    public function test_nova_poshta_outage_preserves_the_cart_and_does_not_create_an_order(): void
    {
        config(['services.nova_poshta.key' => null]);
        $cart = Cart::factory()->create();
        $this->withCookie(Cart::CLIENT_COOKIE, $cart->client_id)->post(route('order.store'), $this->customerData([
            'delivery_method' => 'nova_poshta', 'delivery_city_ref' => (string) Str::uuid(), 'delivery_warehouse_ref' => (string) Str::uuid(),
        ]))->assertSessionHasErrors('delivery_warehouse_ref');
        $this->assertDatabaseCount('orders', 0);
        $this->assertModelExists($cart);
    }

    /** @return array{Ref: string, Description: string, SettlementRef: string, SettlementDescription: string} */
    private function warehouseData(string $cityRef, string $ref, string $label): array
    {
        return ['Ref' => $ref, 'Description' => $label, 'SettlementRef' => $cityRef, 'SettlementDescription' => 'Київ'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function customerData(array $overrides = []): array
    {
        return array_replace([
            'customer_type' => 'individual', 'name' => 'Олена', 'phone' => '+380501234567', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ], $overrides);
    }
}
