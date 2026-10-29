<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

class SiteViewData
{
    public function __construct(private LanguageSwitcherService $languageSwitcherService, private BreadcrumbService $breadcrumbService) {}

    /** @return array{menuPages: Collection, languageButtons: list<array{locale: string, title: string, short: string, url: string, active: bool}>, menuCatalogs: Collection, crumbs: list<array{title: string, url: ?string}>} */
    public function get(): array
    {
        return [
            'crumbs' => $this->breadcrumbService->forRequest(),
            'menuPages' => Page::with('translation')->whereType('pages')->where('public', true)->where('is_menu', true)
                ->whereNotNull('slag')->where('slag', '!=', '')
                ->orderBy('position')->orderBy('id')->get(),
            'languageButtons' => $this->languageSwitcherService->forRequest(),
            'menuCatalogs' => Catalog::where('public', true)->with(['translation', 'parent.translation', 'parent.parent.translation'])
                ->orderBy('position')->orderBy('id')->get()->groupBy('parent_id'),
        ];
    }
}
