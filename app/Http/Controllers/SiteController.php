<?php

namespace App\Http\Controllers;

use App\Models\Adv;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;
use App\Models\Slide;
use App\Services\BreadcrumbService;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    protected array $data = [];

    public function __construct(
        private BreadcrumbService $breadcrumbService,
        private SiteViewData $siteViewData,
    ) {}

    /** @param array<string, mixed> $parameters */
    public function callAction(string $method, array $parameters): mixed
    {
        $this->data = $this->siteViewData->get();

        return $this->{$method}(...array_values($parameters));
    }

    public function index(Request $request): View
    {
        $this->data['catalogs'] = Catalog::with('translation')->where('public', true)->where('popular', true)
            ->whereNotNull('preview')->where('preview', '!=', '')
            ->orderBy('position')->orderBy('id')->limit(24)->get();
        $this->data['crumbs'] = $this->breadcrumbService->forPage();
        $this->data['slides'] = Slide::with('translation')->where('public', true)
            ->whereHas('translation', fn (Builder $query): Builder => $query->whereNotNull('title')->where('title', '!=', ''))
            ->orderBy('position')->orderBy('id')->get();
        $this->data['products'] = Product::with(['translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation'])->where('public', true)->where('popular', true)->where('available', 0)->orderBy('position')->orderBy('id')->get();

        $this->data['advs'] = Adv::with('translation')->where('public', true)->orderBy('position')->orderBy('id')->get();

        $this->data['brands'] = Brand::with('translation')->where('public', true)->orderBy('position')->orderBy('id')->get();

        $this->data['blogPage'] = Page::where('type', '!=', 'articles')->where('template', 'blog')->where('public', true)
            ->whereNotNull('slag')->where('slag', '!=', '')
            ->orderBy('position')->orderBy('id')->first();
        $this->data['latestArticles'] = $this->data['blogPage'] === null ? new Collection : Page::with('translation')
            ->where('type', 'articles')->where('public', true)->whereNotNull('slag')->where('slag', '!=', '')
            ->latest()->orderByDesc('id')->limit(10)->get();

        return view('site.index', $this->data);
    }

    public function catalog(Request $request): View
    {
        $catalog = Catalog::with('translation')->where('public', true)->where('slag', $request->route('catalogSlag'))->firstOrFail();
        $this->data['catalog'] = $catalog;
        $this->data['catalogs'] = $catalog->children()->with('translation')->where('public', true)
            ->orderBy('position')->orderBy('id')->get();
        $this->data['crumbs'] = [
            ['title' => __('Головна'), 'url' => route('index')],
            ['title' => $catalog->title ?: $catalog->slag, 'url' => null],
        ];

        if ($this->data['catalogs']->isEmpty()) {
            $allowedValueIds = $catalog->fvalues()
                ->whereIn('filter_id', $catalog->filters()->select('filters.id'))->pluck('fvalues.id');
            $data = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'sort' => ['sometimes', 'string', Rule::in(['popular', 'price_desc', 'price_asc'])],
                'fvalues' => ['sometimes', 'array', 'list', 'max:100'],
                'fvalues.*' => ['required', 'integer', 'distinct', Rule::in($allowedValueIds->all())],
            ]);
            $sort = $data['sort'] ?? 'popular';
            $selectedValueIds = array_map(intval(...), $data['fvalues'] ?? []);
            $filters = $catalog->filters()->with([
                'translation',
                'fvalues' => fn (HasMany $query): HasMany => $query->whereIn('id', $allowedValueIds)->with('translation'),
            ])->orderBy('position')->orderBy('filters.id')->get();
            $products = $catalog->products()->where('public', true)->with([
                'translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation',
            ]);
            foreach ($filters as $filter) {
                $selectedForFilter = $filter->fvalues->whereIn('id', $selectedValueIds)->modelKeys();
                if ($selectedForFilter !== []) {
                    $products->whereHas('fvalues', fn (Builder $query): Builder => $query->whereIn('fvalues.id', $selectedForFilter));
                }
            }
            $activeFilters = [];
            foreach ($filters as $filter) {
                foreach ($filter->fvalues->whereIn('id', $selectedValueIds) as $filterValue) {
                    $remainingIds = array_values(array_diff($selectedValueIds, [$filterValue->id]));
                    $activeFilters[] = [
                        'id' => $filterValue->id,
                        'label' => ($filter->title ?: $filter->slag).': '.($filterValue->title ?: $filterValue->slag),
                        'url' => route('catalog', [
                            'catalogSlag' => $catalog->slag,
                            ...($remainingIds === [] ? [] : ['fvalues' => $remainingIds]),
                            ...($sort === 'popular' ? [] : ['sort' => $sort]),
                        ]),
                    ];
                }
            }
            $this->data['activeFilters'] = $activeFilters;
            $this->data['filters'] = $filters;
            $this->data['selectedValueIds'] = $selectedValueIds;
            $products->orderBy('available');
            match ($sort) {
                'price_desc' => $products->orderByDesc('final_price'),
                'price_asc' => $products->orderBy('final_price'),
                default => $products->orderByDesc('popular'),
            };
            $this->data['sort'] = $sort;
            $this->data['products'] = $products->orderBy('position')->orderBy('id')->paginate(24)->withQueryString();
        }

        return view('site.catalog', $this->data);
    }

    public function page(Request $request): View
    {
        $page = Page::with('translation')->where('type', '!=', 'articles')->where('public', true)->where('slag', $request->route('slag'))->firstOrFail();

        $template = $page->template ? 'site.'.$page->template : 'site.page';
        if (! view()->exists($template)) {
            $template = 'site.page';
        }
        $this->data['page'] = $page;
        $this->data['crumbs'] = $this->breadcrumbService->forPage($page);

        if ($template === 'site.blog') {
            $this->data['articles'] = Page::with('translation')->where('type', 'articles')->where('public', true)
                ->whereNotNull('slag')->where('slag', '!=', '')
                ->latest()->paginate(12);
        }

        return view($template, $this->data);
    }

    public function article(Request $request): View
    {
        $blog = Page::with('translation')->where('type', '!=', 'articles')
            ->where('template', 'blog')->where('public', true)->where('slag', $request->route('blogSlag'))->firstOrFail();
        $article = Page::with('translation')->where('type', 'articles')
            ->where('public', true)->where('slag', $request->route('articleSlag'))->firstOrFail();

        $this->data['page'] = $article;
        $this->data['crumbs'] = $this->breadcrumbService->forPage($article, $blog);
        $this->data['blog'] = $blog;
        $this->data['latestArticles'] = Page::with('translation')->where('type', 'articles')->where('public', true)
            ->whereKeyNot($article->id)->whereNotNull('slag')->where('slag', '!=', '')
            ->latest()->orderByDesc('id')->limit(5)->get();

        return view('site.article', $this->data);
    }
}
