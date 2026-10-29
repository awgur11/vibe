<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->cartData($request));
    }

    public function product(Product $product): JsonResponse
    {
        abort_unless($product->public, 404);
        $product->load('translation', 'pictures');

        return response()->json(['product' => [
            'id' => $product->id,
            'title' => $product->title ?: $product->slag,
            'price' => $product->final_price,
            'preview' => $product->preview('mini'),
            'can_add_to_cart' => $product->available !== 2,
        ]]);
    }

    public function store(StoreCartRequest $request): JsonResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data): void {
            $cart = Cart::firstOrCreate([
                'client_id' => $request->attributes->get('cart_client_id'),
                'product_id' => $data['product_id'],
            ], ['quantity' => 0, 'expires_at' => now()->addMonthsNoOverflow(3)]);
            $cart = Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $quantity = ($cart->expires_at->isPast() ? 0 : $cart->quantity) + (int) $data['quantity'];
            if ($quantity > Cart::MAX_QUANTITY) {
                throw ValidationException::withMessages(['quantity' => __('Максимальное количество товара: :count.', ['count' => Cart::MAX_QUANTITY])]);
            }
            $cart->update(['quantity' => $quantity, 'expires_at' => now()->addMonthsNoOverflow(3)]);
        }, 3);

        return response()->json($this->cartData($request), 201);
    }

    public function update(UpdateCartRequest $request, Cart $cart): JsonResponse
    {
        DB::transaction(function () use ($request, $cart): void {
            $entry = Cart::whereKey($cart->id)->where('client_id', $request->attributes->get('cart_client_id'))
                ->where('expires_at', '>', now())->whereHas('product', fn (Builder $query): Builder => $query->where('public', true)->whereIn('available', [0, 1]))
                ->lockForUpdate()->firstOrFail();
            $entry->update(['quantity' => $request->integer('quantity'), 'expires_at' => now()->addMonthsNoOverflow(3)]);
        }, 3);

        return response()->json($this->cartData($request));
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        $deleted = Cart::whereKey($cart->id)->where('client_id', $request->attributes->get('cart_client_id'))
            ->where('expires_at', '>', now())->delete();
        abort_unless($deleted, 404);

        return response()->json($this->cartData($request));
    }

    /** @return array{items: list<array{id: int, product_id: int, title: string, preview: ?string, quantity: int, can_update: bool, price: string, total: string, update_url: string, remove_url: string}>, count: int, total: string} */
    private function cartData(Request $request): array
    {
        $totalCents = 0;
        $count = 0;
        $items = [];
        $carts = Cart::where('client_id', $request->attributes->get('cart_client_id'))->where('expires_at', '>', now())
            ->whereHas('product', fn (Builder $query): Builder => $query->where('public', true))
            ->with('product.translation', 'product.pictures')->orderBy('id')->get();
        foreach ($carts as $cart) {
            $product = $cart->product;
            if ($product === null) {
                continue;
            }
            $lineCents = (int) str_replace('.', '', $product->final_price) * $cart->quantity;
            $totalCents += $lineCents;
            $count += $cart->quantity;
            $items[] = [
                'id' => $cart->id,
                'product_id' => $cart->product_id,
                'title' => $product->title ?: $product->slag,
                'preview' => $product->preview('mini'),
                'quantity' => $cart->quantity,
                'can_update' => $product->available !== 2,
                'price' => $product->final_price,
                'total' => $this->price($lineCents),
                'update_url' => route('cart.update', $cart),
                'remove_url' => route('cart.destroy', $cart),
            ];
        }

        return ['items' => $items, 'count' => $count, 'total' => $this->price($totalCents)];
    }

    private function price(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
