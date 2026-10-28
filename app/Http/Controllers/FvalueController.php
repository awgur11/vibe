<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFvalueRequest;
use App\Models\Filter;
use App\Models\Fvalue;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FvalueController extends Controller
{
    public function store(SaveFvalueRequest $request, Filter $filter): RedirectResponse
    {
        $this->save($request, $filter, new Fvalue);

        return $this->redirectToFilter($request, $filter)->with('status', __('Значення фільтра створено.'));
    }

    public function update(SaveFvalueRequest $request, Filter $filter, Fvalue $fvalue): RedirectResponse
    {
        $this->save($request, $filter, $fvalue);

        return $this->redirectToFilter($request, $filter)->with('status', __('Значення фільтра збережено.'));
    }

    public function destroy(Request $request, Filter $filter, Fvalue $fvalue): RedirectResponse
    {
        $fvalue->delete();

        return $this->redirectToFilter($request, $filter)->with('status', __('Значення фільтра видалено.'));
    }

    private function redirectToFilter(Request $request, Filter $filter): RedirectResponse
    {
        return redirect()->route('filters.index', ['page' => $request->query('page'), 'expanded' => $filter->id]);
    }

    private function save(SaveFvalueRequest $request, Filter $filter, Fvalue $fvalue): void
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $filter, $fvalue): void {
            $fvalue->fill([
                'filter_id' => $filter->id,
                'slag' => $data['slag'] ?? $this->uniqueSlag($data['languages'][Language::ensureBase()->id]['title'], $filter, $fvalue->id),
                'position' => $data['position'] ?? ($fvalue->exists ? $fvalue->position : ($filter->fvalues()->max('position') ?? -1) + 1),
            ])->save();
            foreach (Language::whereIn('id', array_keys($data['languages']))->get() as $language) {
                $fvalue->translations()->updateOrCreate(['lacale' => $language->locale], $data['languages'][$language->id]);
            }
        });
    }

    private function uniqueSlag(string $title, Filter $filter, ?int $ignoreId = null): string
    {
        $base = rtrim(substr(x_slag($title), 0, 255), '-') ?: 'value';
        $candidate = $base;
        $suffix = 1;
        while ($filter->fvalues()->where('slag', $candidate)->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))->exists()) {
            $ending = '-'.++$suffix;
            $candidate = rtrim(substr($base, 0, 255 - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
