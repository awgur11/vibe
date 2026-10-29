<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Mail\OrderPlaced;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\NovaPoshtaService;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private SiteViewData $siteViewData, private NovaPoshtaService $novaPoshta) {}

    public function csrfToken(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function checkout(Request $request): View
    {
        $cartItems = [];
        $totalCents = 0;
        $hasUnavailableItems = false;
        $carts = Cart::where('client_id', $request->attributes->get('cart_client_id'))->where('expires_at', '>', now())
            ->with('product.translation', 'product.pictures')->orderBy('id')->get();
        foreach ($carts as $cart) {
            $product = $cart->product;
            $available = $product !== null && $product->public && in_array($product->available, [0, 1], true);
            $hasUnavailableItems = $hasUnavailableItems || ! $available;
            $price = $product?->final_price ?? '0.00';
            $lineCents = (int) str_replace('.', '', $price) * $cart->quantity;
            $totalCents += $lineCents;
            $cartItems[] = [
                'title' => $product?->title ?: ($product?->slag ?? __('Товар недоступен')),
                'preview' => $product?->preview('mini'),
                'quantity' => $cart->quantity,
                'price' => $price,
                'total' => $this->price($lineCents),
                'available' => $available,
            ];
        }

        return view('site.order', [
            ...$this->siteViewData->get(),
            'cartItems' => $cartItems,
            'cartTotal' => $this->price($totalCents),
            'hasUnavailableItems' => $hasUnavailableItems,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $delivery = $this->deliveryData($data);
        $order = DB::transaction(function () use ($request, $data, $delivery): Order {
            $clientId = $request->attributes->get('cart_client_id');
            $carts = Cart::where('client_id', $clientId)->where('expires_at', '>', now())->orderBy('id')->lockForUpdate()->get();
            if ($carts->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Корзина пуста. Добавьте товары перед оформлением заказа.')]);
            }
            $products = Product::whereIn('id', $carts->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $products->load('translation');
            $items = [];
            $totalCents = 0;
            foreach ($carts as $cart) {
                $product = $products->get($cart->product_id);
                if ($product === null || ! $product->public || ! in_array($product->available, [0, 1], true)
                    || $cart->quantity < 1 || $cart->quantity > Cart::MAX_QUANTITY) {
                    throw ValidationException::withMessages(['cart' => __('В корзине есть недоступные товары. Обновите корзину перед оформлением.')]);
                }
                $lineCents = (int) str_replace('.', '', $product->final_price) * $cart->quantity;
                $totalCents += $lineCents;
                $items[] = [
                    'product_id' => $product->id,
                    'title' => $product->title ?: $product->slag,
                    'articule' => $product->articule,
                    'quantity' => $cart->quantity,
                    'price' => $product->final_price,
                    'total' => $this->price($lineCents),
                ];
            }
            $order = Order::create([
                'client_id' => $clientId,
                'customer_type' => $data['customer_type'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'legal_address' => $data['legal_address'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'kpp' => $data['kpp'] ?? null,
                'payment_method' => $data['payment_method'],
                'total' => $this->price($totalCents),
                'status' => 'new',
            ]);
            $order->delivery()->create($delivery);
            $order->items()->createMany($items);
            Cart::whereIn('id', $carts->modelKeys())->delete();

            return $order;
        }, 3);

        $order->load('items', 'delivery');
        foreach ([['email' => $order->email, 'admin' => false], ['email' => setting('admin_email'), 'admin' => true]] as $recipient) {
            if (! is_string($recipient['email']) || ! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            try {
                Mail::to($recipient['email'])->locale(app()->getLocale())->queue(new OrderPlaced($order, $recipient['admin']));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('order.checkout')->with('placed_order', ['id' => $order->id, 'total' => $order->total]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{delivery_method: string, delivery_city_ref: ?string, delivery_city: ?string, delivery_warehouse_ref: ?string, delivery_warehouse: ?string, pickup_address: ?string}
     */
    private function deliveryData(array $data): array
    {
        $delivery = [
            'delivery_method' => $data['delivery_method'],
            'delivery_city_ref' => null,
            'delivery_city' => null,
            'delivery_warehouse_ref' => null,
            'delivery_warehouse' => null,
            'pickup_address' => null,
        ];
        if ($data['delivery_method'] === 'pickup') {
            $delivery['pickup_address'] = setting('address');

            return $delivery;
        }
        try {
            $warehouse = $this->novaPoshta->warehouse($data['delivery_city_ref'], $data['delivery_warehouse_ref']);
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['delivery_warehouse_ref' => __('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.')]);
        }
        if ($warehouse === null) {
            throw ValidationException::withMessages(['delivery_warehouse_ref' => __('Выберите отделение из списка выбранного города.')]);
        }

        return [
            ...$delivery,
            'delivery_city_ref' => $data['delivery_city_ref'],
            'delivery_city' => $warehouse['city'],
            'delivery_warehouse_ref' => $warehouse['ref'],
            'delivery_warehouse' => $warehouse['label'],
        ];
    }

    private function price(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
