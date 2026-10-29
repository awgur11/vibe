<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Comparison;
use App\Models\Product;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComparisonController extends Controller
{
    private function products(Request $request): Builder
    {
        return Product::where('public', true)->whereHas('catalog', fn (Builder $query): Builder => $query->where('public', true))
            ->whereHas('comparisons', fn (Builder $query): Builder => $query->where('client_id', $request->attributes->get('cart_client_id')));
    }

    public function state(Request $request): JsonResponse
    {
        $products = $this->products($request)->with('catalog.translation')->orderBy('id')->get();

        return response()->json([
            'product_ids' => $products->modelKeys(),
            'categories' => $products->groupBy('catalog_id')->map(fn ($items): array => [
                'title' => $items->first()->catalog->title ?: $items->first()->catalog->slag,
                'count' => $items->count(),
                'url' => route('comparison.show', $items->first()->catalog),
            ])->values(),
            'token' => csrf_token(),
        ]);
    }

    public function show(Request $request, Catalog $catalog, SiteViewData $siteViewData): View
    {
        abort_unless($catalog->public, 404);
        $catalog->load('translation');
        $products = $this->products($request)->where('catalog_id', $catalog->id)
            ->with('translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation')->orderBy('id')->get();
        $filters = $products->flatMap->fvalues->map->filter->filter()->unique('id')->sortBy([['position', 'asc'], ['id', 'asc']])->values();

        return view('site.comparison', [...$siteViewData->get(), 'catalog' => $catalog, 'products' => $products, 'filters' => $filters]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->public && $product->catalog?->public, 404);
        Comparison::firstOrCreate(['client_id' => $request->attributes->get('cart_client_id'), 'product_id' => $product->id]);

        return $this->state($request);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        Comparison::where('client_id', $request->attributes->get('cart_client_id'))->where('product_id', $product->id)->delete();

        return $this->state($request);
    }
}
