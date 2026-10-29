<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

class SiteViewData
{
    public function __construct(private LanguageSwitcherService $languageSwitcherService) {}

    /** @return array{menuPages: Collection, languageButtons: list<array{locale: string, title: string, short: string, url: string, active: bool}>, menuCatalogs: Collection} */
    public function get(): array
    {
        return [
            'menuPages' => Page::with('translation')->whereType('pages')->where('public', true)->where('is_menu', true)
                ->whereNotNull('slag')->where('slag', '!=', '')
                ->orderBy('position')->orderBy('id')->get(),
            'languageButtons' => $this->languageSwitcherService->forRequest(),
            'menuCatalogs' => Catalog::where('public', true)->with(['translation', 'parent.translation', 'parent.parent.translation'])
                ->orderBy('position')->orderBy('id')->get()->groupBy('parent_id'),
        ];
    }
}
