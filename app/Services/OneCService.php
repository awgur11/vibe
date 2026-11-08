<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Order;
use App\Models\Product;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

class OneCService
{
    public function parse(string $content): SimpleXMLElement
    {
        if (trim($content) === '') {
            throw ValidationException::withMessages(['xml' => 'Empty XML.']);
        }
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $content)) {
            throw ValidationException::withMessages(['xml' => 'DTD and entities are not supported.']);
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
            if ($xml === false) {
                throw ValidationException::withMessages(['xml' => 'Invalid XML.']);
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function import(SimpleXMLElement $xml, bool $pricesOnly = false): int
    {
        $nodes = $pricesOnly
            ? $xml->xpath('./КоммерческаяИнформация/Товары/Товар')
            : $xml->xpath('./КоммерческаяИнформация/Каталог/Товары/Товар');
        if (empty($nodes)) {
            throw ValidationException::withMessages(['xml' => 'Missing products.']);
        }
        foreach ($nodes as $node) {
            if (trim((string) $node->{'Ид'}) === '' || (! $pricesOnly && trim((string) $node->{'Наименование'}) === '')) {
                throw ValidationException::withMessages(['xml' => 'Product ID and name are required.']);
            }
            foreach ([$node->{'Цены'}->{'Цена'}, $node->{'Остатки'}->{'Остаток'}] as $number) {
                if (isset($number[0]) && (! is_numeric((string) $number) || (float) $number < 0)) {
                    throw ValidationException::withMessages(['xml' => 'Invalid price or stock.']);
                }
            }
        }

        return DB::transaction(function () use ($nodes, $pricesOnly): int {
            $count = 0;
            foreach ($nodes as $node) {
                $id = trim((string) $node->{'Ид'});
                $products = Product::where('one_c_id', $id)->get();
                if ($products->isEmpty() && $pricesOnly) {
                    continue;
                }
                if ($products->isEmpty()) {
                    $product = new Product;
                    $product->one_c_id = $id;
                    $product->slag = 'onec-'.Str::uuid();
                    $product->public = true;
                    $product->available = 2;
                    $products->push($product);
                }
                foreach ($products as $product) {
                    if (isset($node->{'Цены'}->{'Цена'})) {
                        $product->price = (string) $node->{'Цены'}->{'Цена'};
                    }
                    if (isset($node->{'Остатки'}->{'Остаток'})) {
                        $product->available = (float) $node->{'Остатки'}->{'Остаток'} > 0 ? 0 : 2;
                    }
                    if ((float) $product->price <= 0) {
                        $product->available = 2;
                    }
                    if (! $pricesOnly && isset($node->{'Артикул'})) {
                        $product->articule = (string) $node->{'Артикул'};
                    }
                    $product->save();
                    if (! $pricesOnly) {
                        foreach (Language::pluck('locale') as $locale) {
                            $values = ['title' => (string) $node->{'Наименование'}];
                            if (isset($node->{'Описание'})) {
                                $values['description'] = (string) $node->{'Описание'};
                            }
                            $product->translations()->updateOrCreate(['lacale' => $locale], $values);
                        }
                    }
                    $count++;
                }
            }

            return $count;
        });
    }

    public function exportOrder(Order $order): string
    {
        $order->loadMissing('items.product', 'delivery', 'user');
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->appendChild($document->createElement('order'));
        $products = $root->appendChild($document->createElement('products'));
        foreach ($order->items as $item) {
            $product = $products->appendChild($document->createElement('product'));
            $this->append($document, $product, [
                'id' => $item->product?->one_c_id ?? '', 'item_id' => $item->product_id,
                'code' => $item->articule, 'qty' => $item->quantity,
                'price' => $item->price, 'subtotal' => $item->total,
            ]);
        }
        $this->append($document, $root, [
            'order_id' => $order->id, 'order_number' => $order->id,
            'total_amount' => $order->total, 'type' => 'retail',
            'customer' => ['first_name' => $order->user->name, 'last_name' => '', 'phone' => $order->user->phone,
                'type' => $order->user->customer_type, 'email' => $order->user->email,
                'company_name' => $order->user->company_name, 'legal_address' => $order->user->legal_address,
                'tax_id' => $order->user->tax_id, 'kpp' => $order->user->kpp],
            'delivery' => ['id' => $order->delivery?->delivery_method,
                'title' => Order::DELIVERY_METHODS[$order->delivery?->delivery_method] ?? '',
                'city' => $order->delivery?->delivery_city,
                'street' => $order->delivery?->delivery_warehouse ?? $order->delivery?->pickup_address,
                'house' => '', 'apartment' => ''],
            'payment' => ['id' => $order->payment_method, 'title' => Order::PAYMENT_METHODS[$order->payment_method] ?? ''],
            'user_comment' => '', 'admin_comment' => '',
            'create_date' => $order->created_at?->format('Y-m-d H:i:s'),
            'edit_date' => $order->updated_at?->format('Y-m-d H:i:s'),
        ]);
        $path = trim(config('onec.orders_directory'), '/').'/M'.$order->id.'.xml';
        $disk = Storage::disk(config('onec.orders_disk'));
        if (! $disk->put($path, $document->saveXML())) {
            throw new \RuntimeException('Cannot write order XML.');
        }

        return $path;
    }

    /** @param array<string, mixed> $values */
    private function append(DOMDocument $document, DOMElement $parent, array $values): void
    {
        foreach ($values as $key => $value) {
            $element = $parent->appendChild($document->createElement($key));
            if (is_array($value)) {
                $this->append($document, $element, $value);
            } else {
                $element->appendChild($document->createTextNode((string) $value));
            }
        }
    }
}
