<?php

namespace App\Http\Controllers;

use App\Models\Adv;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;
use App\Models\Slide;
use App\Services\BreadcrumbService;
use App\Services\LanguageSwitcherService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    protected array $data = [];

    public function __construct(
        private BreadcrumbService $breadcrumbService,
        private LanguageSwitcherService $languageSwitcherService,
    ) {}

    /** @param array<string, mixed> $parameters */
    public function callAction(string $method, array $parameters): mixed
    {
        $this->data = [
            'menuPages' => $this->menuPages(),
            'languageButtons' => $this->languageSwitcherService->forRequest(),
            'menuCatalogs' => Catalog::where('public', true)->with(['translation', 'parent.translation', 'parent.parent.translation'])->orderBy('position')->orderBy('id')->get()->groupBy('parent_id'),
        ];

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
        $this->data['products'] = Product::with(['translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation'])->wherePopular(1)->get();

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

        return view('site.article', $this->data);
    }

    /** @return Collection<int, Page> */
    private function menuPages(): Collection
    {
        return Page::with('translation')->whereType('pages')->where('public', true)->where('is_menu', true)
            ->whereNotNull('slag')->where('slag', '!=', '')
            ->orderBy('position')->orderBy('id')->get();
    }
}
