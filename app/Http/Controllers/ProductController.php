<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\Catalog;
use App\Models\Language;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim($data['search'] ?? '');
        return view('admin.products.index', [
            'baseLanguage' => Language::ensureBase(),
            'search' => $search,
            'products' => Product::with(['translations', 'catalog.translations'])
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->whereLike('articule', '%'.$search.'%')
                            ->orWhereLike('slag', '%'.$search.'%')
                            ->orWhereHas('translations', fn (Builder $translations): Builder => $translations->whereLike('title', '%'.$search.'%'));
                    });
                })
                ->orderBy('position')->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = DB::transaction(function () use ($data): Product {
            $product = Product::create(['slag' => $this->uniqueSlag($data['title']), 'price' => 0, 'discount' => 0, 'public' => false, 'position' => (Product::max('position') ?? -1) + 1]);
            $product->translations()->create(['lacale' => Language::ensureBase()->locale, 'title' => $data['title']]);

            return $product;
        });

        return redirect()->route('products.edit', $product)->with('status', __('Товар створено. Заповніть його вміст.'));
    }

    public function edit(Product $product): View
    {
        $baseLanguage = Language::ensureBase();
        $catalogId = old('catalog_id', $product->catalog_id);
        $catalog = is_scalar($catalogId) ? Catalog::find($catalogId) : null;

        return view('admin.products.form', [
            'product' => $product->load('translations', 'fvalues', 'pictures'),
            'catalogs' => Catalog::with('translations')->orderBy('position')->orderBy('id')->get(),
            'filters' => $catalog ? $this->filtersForCatalog($catalog) : collect(),
            'baseLanguage' => $baseLanguage,
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function catalogFilters(Catalog $catalog): View
    {
        return view('admin.products.filters', [
            'filters' => $this->filtersForCatalog($catalog),
            'baseLanguage' => Language::ensureBase(),
            'selectedValueIds' => [],
        ]);
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        if (($data['position'] ?? null) === null) {
            unset($data['position']);
        }
        $translations = $data['languages'];
        $valueIds = $data['fvalues'] ?? [];
        unset($data['languages'], $data['fvalues']);
        $data['slag'] = $data['slag'] ?? $this->uniqueSlag($translations[Language::ensureBase()->id]['title'], $product->id);
        DB::transaction(function () use ($product, $data, $translations, $valueIds): void {
            $product->fill($data)->save();
            $product->fvalues()->sync($valueIds);
            foreach (Language::whereIn('id', array_keys($translations))->get() as $language) {
                $product->translations()->updateOrCreate(['lacale' => $language->locale], $translations[$language->id]);
            }
        });

        return redirect()->route('products.edit', $product)->with('status', __('Товар збережено.'));
    }

    public function destroy(Product $product, ImageService $imageService): RedirectResponse
    {
        $paths = DB::transaction(function () use ($product): array {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $paths = $product->pictures()->pluck('path')->all();
            $product->delete();

            return $paths;
        });
        foreach ($paths as $path) {
            $imageService->delete($path);
        }

        return redirect()->route('products.index')->with('status', __('Товар видалено.'));
    }

    /** @return Collection<int, \App\Models\Filter> */
    private function filtersForCatalog(Catalog $catalog): Collection
    {
        $valueIds = $catalog->fvalues()->pluck('fvalues.id');

        return $catalog->filters()->with(['translations', 'fvalues' => fn (HasMany $query): HasMany => $query->whereIn('id', $valueIds)->with('translations')])
            ->orderBy('position')->orderByDesc('filters.id')->get();
    }

    private function uniqueSlag(string $title, ?int $ignoreId = null): string
    {
        $base = rtrim(substr(x_slag($title), 0, 255), '-') ?: 'product';
        $candidate = $base;
        $suffix = 1;
        while (Product::where('slag', $candidate)->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))->exists()) {
            $ending = '-'.++$suffix;
            $candidate = rtrim(substr($base, 0, 255 - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
