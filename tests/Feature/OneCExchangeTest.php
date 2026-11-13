<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OneCService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OneCExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['onec.api_key' => 'test-key']);
        Storage::fake('local');
    }

    public function test_products_are_imported_idempotently_and_prices_update_the_same_product(): void
    {
        $body = '<request><api_key>test-key</api_key><КоммерческаяИнформация><Каталог><Товары><Товар><Ид>external-1</Ид><Наименование>Масло &amp; фильтр</Наименование><Артикул>ART</Артикул><Цены><Цена>120.50</Цена></Цены><Остатки><Остаток>3</Остаток></Остатки></Товар></Товары></Каталог></КоммерческаяИнформация></request>';
        $this->xml('/api/products', $body)->assertOk()->assertJsonPath('imported', 1);
        $this->xml('/api/products', $body)->assertOk();
        $product = Product::sole();
        $this->assertSame('external-1', $product->one_c_id);
        $this->assertSame('120.50', $product->price);
        $this->assertSame('Масло & фильтр', $product->translations()->sole()->title);
        $prices = '<request><api_key>test-key</api_key><КоммерческаяИнформация><Товары><Товар><Ид>external-1</Ид><Цены><Цена>0</Цена></Цены><Остатки><Остаток>0</Остаток></Остатки></Товар></Товары></КоммерческаяИнформация></request>';
        $this->xml('/api/prices', $prices)->assertOk()->assertJsonPath('imported', 1);
        $this->assertSame('0.00', $product->fresh()->price);
        $this->assertSame(2, $product->fresh()->available);
    }

    public function test_prices_update_all_matching_products_and_recalculate_discounts(): void
    {
        $first = Product::factory()->create(['one_c_id' => 'shared-id', 'price' => 100, 'discount' => 10]);
        $second = Product::factory()->create(['one_c_id' => 'shared-id', 'price' => 200, 'discount' => 25]);
        $unrelated = Product::factory()->create(['one_c_id' => 'unrelated', 'price' => 300]);
        $body = '<Data><api_key>test-key</api_key><КоммерческаяИнформация><Товары><Товар><Ид>shared-id</Ид><Цены><Цена>120.50</Цена></Цены><Остатки><Остаток>3</Остаток></Остатки></Товар><Товар><Ид>unknown</Ид><Цены><Цена>999</Цена></Цены></Товар></Товары></КоммерческаяИнформация></Data>';

        $this->xml('/api/prices', $body)->assertOk()->assertJsonPath('imported', 2);

        $this->assertSame('120.50', $first->fresh()->price);
        $this->assertSame('108.45', $first->fresh()->final_price);
        $this->assertSame('120.50', $second->fresh()->price);
        $this->assertSame('90.38', $second->fresh()->final_price);
        $this->assertSame(0, $first->fresh()->available);
        $this->assertSame(0, $second->fresh()->available);
        $this->assertSame('300.00', $unrelated->fresh()->price);
        $this->assertDatabaseCount('products', 3);
    }

    public function test_catalog_import_updates_every_product_with_the_same_external_id(): void
    {
        $products = Product::factory()->count(2)->create(['one_c_id' => 'shared-id']);
        $body = '<Data><api_key>test-key</api_key><КоммерческаяИнформация><Каталог><Товары><Товар><Ид>shared-id</Ид><Наименование>Updated title</Наименование><Цены><Цена>80</Цена></Цены></Товар></Товары></Каталог></КоммерческаяИнформация></Data>';

        $this->xml('/api/products', $body)->assertOk()->assertJsonPath('imported', 2);

        foreach ($products as $product) {
            $this->assertSame('80.00', $product->fresh()->price);
            $this->assertSame('Updated title', $product->translations()->sole()->title);
        }
        $this->assertDatabaseCount('products', 2);
    }

    public function test_prices_preserve_missing_fields_and_reject_invalid_batches(): void
    {
        $product = Product::factory()->create(['one_c_id' => 'external-1', 'price' => 100, 'available' => 0]);
        $body = '<Data><api_key>test-key</api_key><КоммерческаяИнформация><Товары><Товар><Ид>external-1</Ид><Остатки><Остаток>0</Остаток></Остатки></Товар></Товары></КоммерческаяИнформация></Data>';
        $this->xml('/api/prices', $body)->assertOk();
        $this->assertSame('100.00', $product->fresh()->price);
        $this->assertSame(2, $product->fresh()->available);

        $body = '<Data><api_key>test-key</api_key><КоммерческаяИнформация><Товары><Товар><Ид>external-1</Ид><Цены><Цена>200</Цена></Цены></Товар><Товар><Ид>other</Ид><Цены><Цена>-1</Цена></Цены></Товар></Товары></КоммерческаяИнформация></Data>';
        $this->xml('/api/prices', $body)->assertUnprocessable();
        $this->assertSame('100.00', $product->fresh()->price);
    }

    public function test_invalid_credentials_xml_and_entities_are_rejected(): void
    {
        $this->xml('/api/products', '<request><api_key>wrong</api_key></request>')->assertUnauthorized();
        $this->xml('/api/products', '<request/>')->assertUnauthorized()->assertJsonPath('code', 1002);
        $this->xml('/api/products', '<broken>')->assertUnprocessable();
        $this->xml('/api/products', '<!DOCTYPE request [<!ENTITY x SYSTEM "file:///etc/passwd">]><request>&x;</request>')->assertUnprocessable();
        config(['onec.api_key' => '']);
        $this->xml('/api/products', '<request><api_key>test-key</api_key></request>')->assertUnauthorized();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_invalid_batch_does_not_partially_import(): void
    {
        $body = '<request><api_key>test-key</api_key><КоммерческаяИнформация><Каталог><Товары><Товар><Ид>valid</Ид><Наименование>Valid</Наименование></Товар><Товар><Наименование>Missing ID</Наименование></Товар></Товары></Каталог></КоммерческаяИнформация></request>';
        $this->xml('/api/products', $body)->assertUnprocessable();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_order_xml_preserves_external_ids_amounts_and_escapes_text(): void
    {
        $order = Order::factory()->for(User::factory()->state(['name' => 'Іван & син']))->create(['total' => '240.50']);
        $item = OrderItem::factory()->for($order)->create(['quantity' => 2, 'price' => '120.25', 'total' => '240.50']);
        $item->product->forceFill(['one_c_id' => 'external-1'])->save();
        $path = app(OneCService::class)->exportOrder($order);
        Storage::disk('local')->assertExists('onec/orders/M'.$order->id.'.xml');
        $xml = simplexml_load_string(Storage::disk('local')->get($path));
        $this->assertSame('external-1', (string) $xml->products->product->id);
        $this->assertSame('240.50', (string) $xml->products->product->subtotal);
        $this->assertSame('Іван & син', (string) $xml->customer->first_name);
    }

    public function test_order_xml_can_be_downloaded_from_public_route(): void
    {
        $order = Order::factory()->create();
        $url = route('onec.orders.xml', $order);
        $this->get($url)->assertOk()->assertDownload('M'.$order->id.'.xml')
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_additional_prices_import_preserve_missing_values_and_accept_zero(): void
    {
        $product = Product::factory()->create(['one_c_id' => 'prices', 'price' => 100, 'min_price' => 60, 'opt_price' => 80]);
        foreach (['/api/prices', '/api/products'] as $url) {
            $catalog = $url === '/api/products';
            $wrap = fn (string $prices): string => '<request><api_key>test-key</api_key><КоммерческаяИнформация>'.($catalog ? '<Каталог>' : '').'<Товары><Товар><Ид>prices</Ид><Наименование>Product</Наименование><Цены>'.$prices.'</Цены></Товар></Товары>'.($catalog ? '</Каталог>' : '').'</КоммерческаяИнформация></request>';
            $this->xml($url, $wrap('<ЦенаМин>65.25</ЦенаМин><ЦенаОпт>85.50</ЦенаОпт>'))->assertOk();
            $this->assertSame('65.25', $product->fresh()->min_price);
            $this->assertSame('85.50', $product->fresh()->opt_price);
            $this->xml($url, $wrap('<Цена>110</Цена>'))->assertOk();
            $this->assertSame('65.25', $product->fresh()->min_price);
            $this->assertSame('85.50', $product->fresh()->opt_price);
            foreach (['ЦенаМин', 'ЦенаОпт'] as $field) {
                foreach (['-1', 'invalid'] as $value) {
                    $this->xml($url, $wrap('<'.$field.'>'.$value.'</'.$field.'>'))->assertUnprocessable();
                    $this->assertSame('65.25', $product->fresh()->min_price);
                    $this->assertSame('85.50', $product->fresh()->opt_price);
                }
            }
            $this->xml($url, $wrap('<ЦенаМин>0</ЦенаМин><ЦенаОпт>0</ЦенаОпт>'))->assertOk();
            $this->assertSame('0.00', $product->fresh()->min_price);
            $this->assertSame('0.00', $product->fresh()->opt_price);
        }
    }

    public function test_import_normalizes_numbers_and_preserves_empty_fields(): void
    {
        foreach (['/api/prices', '/api/products'] as $url) {
            $product = Product::factory()->create(['one_c_id' => $url, 'price' => 100, 'min_price' => 60, 'opt_price' => 80, 'available' => 0]);
            $catalog = $url === '/api/products';
            $wrap = fn (string $fields): string => '<request><api_key>test-key</api_key><КоммерческаяИнформация>'.($catalog ? '<Каталог>' : '').'<Товары><Товар><Ид>'.$url.'</Ид><Наименование>Product</Наименование>'.$fields.'</Товар></Товары>'.($catalog ? '</Каталог>' : '').'</КоммерческаяИнформация></request>';
            $this->xml($url, $wrap('<Цены><Цена> 1 170,00 </Цена><ЦенаМин>1&#160;050,25</ЦенаМин><ЦенаОпт>1&#8239;100,50</ЦенаОпт></Цены><Остатки><Остаток>2,00</Остаток></Остатки>'))->assertOk();
            $this->assertSame('1170.00', $product->fresh()->price);
            $this->assertSame('1050.25', $product->fresh()->min_price);
            $this->assertSame('1100.50', $product->fresh()->opt_price);
            $this->assertSame(0, $product->fresh()->available);
            $this->xml($url, $wrap('<Цены><Цена/><ЦенаМин> </ЦенаМин><ЦенаОпт/></Цены><Остатки><Остаток/></Остатки>'))->assertOk();
            $this->assertSame('1170.00', $product->fresh()->price);
            $this->assertSame('1050.25', $product->fresh()->min_price);
            $this->assertSame('1100.50', $product->fresh()->opt_price);
            $this->assertSame(0, $product->fresh()->available);
            $this->xml($url, $wrap('<Остатки><Остаток>-4,00</Остаток></Остатки>'))->assertOk();
            $this->assertSame(2, $product->fresh()->available);
        }
    }

    private function xml(string $url, string $content): TestResponse
    {
        return $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/xml', 'HTTP_ACCEPT' => 'application/json'], $content);
    }
}
