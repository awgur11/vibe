<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\BreadcrumbService;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, SiteViewData $siteViewData, BreadcrumbService $breadcrumbs): View
    {
        $query = $this->searchTerm($request);

        return view('site.search', [...$siteViewData->get(), 'query' => $query,
            'crumbs' => $breadcrumbs->forTitle(__('Результаты поиска')),
            'products' => $this->products($query)->with('translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation')->paginate(24)->withQueryString(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $this->searchTerm($request);
        $products = $this->products($query)->with('translation', 'pictures')->limit(9)->get();

        return response()->json(['products' => $products->map(fn (Product $product): array => [
            'title' => $product->title ?: $product->slag, 'price' => $product->final_price,
            'image' => $product->preview('mini'), 'url' => route('product', ['product' => $product->slag]),
        ])]);
    }

    private function searchTerm(Request $request): string
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200']]);

        return trim($data['q'] ?? '');
    }

    private function products(string $query): Builder
    {
        $products = Product::where('public', true);
        if (mb_strlen($query) < 3) {
            return $products->whereRaw('1 = 0');
        }
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query).'%';

        return $products->whereHas('translation', fn (Builder $translation): Builder => $translation->whereRaw("title LIKE ? ESCAPE '!'", [$pattern]))
            ->orderBy('available')->orderBy('position')->orderBy('id');
    }
}
