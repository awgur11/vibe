<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCatalogRequest;
use App\Http\Requests\StoreCatalogRequest;
use App\Http\Requests\UpdateCatalogPublicationRequest;
use App\Models\Catalog;
use App\Models\Language;
use App\Models\Filter;
use App\Models\Fvalue;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CatalogController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function index(Request $request): View
    {
        $data = $request->validate(['parent_id' => ['nullable', 'integer', 'exists:catalogs,id']]);
        $parent = isset($data['parent_id']) ? Catalog::with('translations')->findOrFail($data['parent_id']) : null;

        return view('admin.catalogs.index', [
            'catalogs' => Catalog::with('translations')->withCount('children')->where('parent_id', $parent?->id)
                ->orderBy('position')->orderByDesc('id')->paginate(20)->withQueryString(),
            'parent' => $parent,
            'baseLanguage' => Language::ensureBase(),
        ]);
    }

    public function children(Catalog $catalog): View
    {
        return view('admin.catalogs.branch', [
            'catalogs' => $catalog->children()->with('translations')->withCount('children')
                ->orderBy('position')->orderByDesc('id')->get(),
            'parent' => $catalog,
            'baseLanguage' => Language::ensureBase(),
        ]);
    }

    public function store(StoreCatalogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $baseLanguage = Language::ensureBase();
        $catalog = DB::transaction(function () use ($data, $baseLanguage): Catalog {
            $parentId = $data['parent_id'] ?? null;
            $catalog = Catalog::create([
                'parent_id' => $parentId,
                'slag' => $this->uniqueSlag($data['title']),
                'position' => (Catalog::where('parent_id', $parentId)->max('position') ?? -1) + 1,
                'public' => false,
            ]);
            $catalog->translations()->create(['lacale' => $baseLanguage->locale, 'title' => $data['title']]);

            return $catalog;
        });

        return redirect()->route('catalogs.edit', $catalog)->with('status', __('Каталог створено. Заповніть його вміст.'));
    }

    public function edit(Catalog $catalog): View
    {
        Language::ensureBase();
        $allCatalogs = Catalog::with('translations')->orderBy('position')->orderBy('id')->get();
        $byId = $allCatalogs->keyBy('id');
        $parents = $allCatalogs->reject(function (Catalog $candidate) use ($catalog, $byId): bool {
            $id = $candidate->id;
            $visited = [];
            while ($id !== null) {
                if ($id === $catalog->id || isset($visited[$id])) {
                    return true;
                }
                $visited[$id] = true;
                $id = $byId->get($id)?->parent_id;
            }

            return false;
        });

        return view('admin.catalogs.form', [
            'catalog' => $catalog->load('translations', 'filters', 'fvalues'),
            'selectedValueRecords' => Fvalue::whereIn('id', (array) old('fvalues', $catalog->fvalues->modelKeys()))->get(['id', 'filter_id']),
            'parents' => $parents,
            'baseLanguage' => Language::ensureBase(),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function filters(): JsonResponse
    {
        $baseLanguage = Language::ensureBase();

        return response()->json(Filter::with('translations')->orderBy('position')->orderByDesc('id')->get()
            ->map(fn (Filter $filter): array => [
                'id' => $filter->id,
                'title' => $filter->translated('title', $baseLanguage) ?? $filter->slag,
                'values_url' => route('catalogs.filter-values', $filter),
            ]));
    }

    public function filterValues(Filter $filter): JsonResponse
    {
        $baseLanguage = Language::ensureBase();

        return response()->json($filter->fvalues()->with('translations')->get()
            ->map(fn (Fvalue $value): array => [
                'id' => $value->id,
                'title' => $value->translated('title', $baseLanguage) ?? $value->slag,
            ]));
    }

    public function update(SaveCatalogRequest $request, Catalog $catalog): RedirectResponse
    {
        $data = $request->validated();
        $translations = $data['languages'];
        $filterIds = $data['filters'] ?? [];
        $fvalueIds = $data['fvalues'] ?? [];
        unset($data['filters'], $data['fvalues']);
        $data['parent_id'] = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $data['slag'] = $data['slag'] ?? $this->uniqueSlag($translations[Language::ensureBase()->id]['title'], $catalog->id);
        $oldPreview = $catalog->preview;
        $data['preview'] = $this->imageService->store(
            $data['preview'] ?? $data['preview_url'] ?? null,
            currentPath: $oldPreview, remove: $request->boolean('remove_preview'), directory: 'catalogs',
        );
        unset($data['languages'], $data['preview_url'], $data['remove_preview']);

        try {
            DB::transaction(function () use ($catalog, $data, $translations, $filterIds, $fvalueIds): void {
                if ($catalog->parent_id !== $data['parent_id']) {
                    $data['position'] = (Catalog::where('parent_id', $data['parent_id'])->max('position') ?? -1) + 1;
                }
                $catalog->fill($data)->save();
                $catalog->filters()->sync($filterIds);
                $catalog->fvalues()->sync($fvalueIds);
                foreach (Language::whereIn('id', array_keys($translations))->get() as $language) {
                    $catalog->translations()->updateOrCreate(['lacale' => $language->locale], $translations[$language->id]);
                }
            });
        } catch (Throwable $exception) {
            if ($data['preview'] !== $oldPreview) {
                $this->imageService->delete($data['preview']);
            }
            throw $exception;
        }
        if ($data['preview'] !== $oldPreview) {
            $this->imageService->delete($oldPreview);
        }

        return redirect()->route('catalogs.edit', $catalog)->with('status', __('Каталог збережено.'));
    }

    public function updatePublication(UpdateCatalogPublicationRequest $request, Catalog $catalog): JsonResponse
    {
        $catalog->update(['public' => $request->boolean('public')]);

        return response()->json(['public' => $catalog->public]);
    }

    public function destroy(Catalog $catalog): RedirectResponse
    {
        $preview = $catalog->preview;
        $parentId = $catalog->parent_id;
        $catalog->delete();
        $this->imageService->delete($preview);

        return redirect()->route('catalogs.index', ['parent_id' => $parentId])->with('status', __('Каталог видалено.'));
    }

    private function uniqueSlag(string $title, ?int $ignoreId = null): string
    {
        $base = rtrim(substr(x_slag($title), 0, 255), '-') ?: 'catalog';
        $candidate = $base;
        $suffix = 1;
        while (Catalog::where('slag', $candidate)->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))->exists()) {
            $ending = '-'.++$suffix;
            $candidate = rtrim(substr($base, 0, 255 - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
