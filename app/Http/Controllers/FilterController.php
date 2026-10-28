<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFilterRequest;
use App\Models\Filter;
use App\Models\Fvalue;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim($data['search'] ?? '');

        return view('admin.filters.index', [
            'baseLanguage' => Language::ensureBase(),
            'search' => $search,
            'filters' => Filter::with('translations')->withCount('fvalues')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->whereLike('slag', '%'.$search.'%')
                            ->orWhereHas('translations', fn (Builder $translations): Builder => $translations->whereLike('title', '%'.$search.'%'));
                    });
                })
                ->orderBy('position')->orderByDesc('id')->paginate(20)->withQueryString(),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function values(Request $request, Filter $filter): JsonResponse
    {
        $request->validate(['values_page' => ['sometimes', 'integer', 'min:1']]);
        $filter->load('translations');
        $values = $filter->fvalues()->with('translations')->simplePaginate(200, ['*'], 'values_page')->withQueryString();

        return response()->json([
            'html' => view('admin.filters.fvalues', [
                'filter' => $filter,
                'baseLanguage' => Language::ensureBase(),
                'valuesExpanded' => true,
                'values' => $values,
            ])->render(),
            'next_url' => $values->nextPageUrl(),
            'values' => $values->getCollection()->mapWithKeys(fn (Fvalue $value): array => [$value->id => [
                'url' => route('filters.fvalues.update', ['filter' => $filter, 'fvalue' => $value, 'page' => request('page')]),
                'slag' => $value->slag,
                'position' => $value->position,
                'titles' => $value->translations->pluck('title', 'lacale'),
            ]]),
        ]);
    }

    public function store(SaveFilterRequest $request): RedirectResponse
    {
        $this->save($request, new Filter);

        return redirect()->route('filters.index')->with('status', __('Фільтр створено.'));
    }

    public function update(SaveFilterRequest $request, Filter $filter): RedirectResponse
    {
        $this->save($request, $filter);

        return redirect()->route('filters.index', ['page' => $request->query('page')])->with('status', __('Фільтр збережено.'));
    }

    public function destroy(Filter $filter): RedirectResponse
    {
        $filter->delete();

        return redirect()->route('filters.index')->with('status', __('Фільтр видалено.'));
    }

    private function save(SaveFilterRequest $request, Filter $filter): void
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $filter): void {
            $filter->fill([
                'slag' => $data['slag'] ?? $this->uniqueSlag($data['languages'][Language::ensureBase()->id]['title'], $filter->id),
                'position' => $data['position'] ?? ($filter->exists ? $filter->position : (Filter::max('position') ?? -1) + 1),
            ])->save();
            foreach (Language::whereIn('id', array_keys($data['languages']))->get() as $language) {
                $filter->translations()->updateOrCreate(['lacale' => $language->locale], $data['languages'][$language->id]);
            }
        });
    }

    private function uniqueSlag(string $title, ?int $ignoreId = null): string
    {
        $base = rtrim(substr(x_slag($title), 0, 255), '-') ?: 'filter';
        $candidate = $base;
        $suffix = 1;
        while (Filter::where('slag', $candidate)->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))->exists()) {
            $ending = '-'.++$suffix;
            $candidate = rtrim(substr($base, 0, 255 - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
