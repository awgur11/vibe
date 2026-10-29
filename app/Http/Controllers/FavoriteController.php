<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request, SiteViewData $siteViewData): View
    {
        $products = Product::where('public', true)->whereHas('favorites', fn ($query) => $query->where('client_id', $request->attributes->get('cart_client_id')))
            ->with('translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation')
            ->latest('id')->paginate(24);

        return view('site.favorites', [...$siteViewData->get(), 'products' => $products]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json(['product_ids' => Favorite::where('client_id', $request->attributes->get('cart_client_id'))
            ->whereHas('product', fn ($query) => $query->where('public', true))->pluck('product_id'), 'token' => csrf_token()]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->public, 404);
        Favorite::firstOrCreate(['client_id' => $request->attributes->get('cart_client_id'), 'product_id' => $product->id]);

        return $this->state($request);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        Favorite::where('client_id', $request->attributes->get('cart_client_id'))->where('product_id', $product->id)->delete();

        return $this->state($request);
    }
}
