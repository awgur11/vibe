<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBrandRequest;
use App\Models\Brand;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::with('translations')->orderBy('position')->orderBy('id')->get(),
            'baseLanguage' => Language::ensureBase(),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function store(SaveBrandRequest $request): RedirectResponse
    {
        $this->save($request, new Brand);

        return redirect()->route('brands.index')->with('status', __('Бренд создан.'));
    }

    public function update(SaveBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->save($request, $brand);

        return redirect()->route('brands.index')->with('status', __('Бренд сохранён.'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('brands.index')->with('status', __('Бренд удалён.'));
    }

    private function save(SaveBrandRequest $request, Brand $brand): void
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $brand): void {
            $brand->fill([
                'name' => $data['name'],
                'position' => $data['position'] ?? ($brand->exists ? $brand->position : (Brand::max('position') ?? -1) + 1),
                'public' => $data['public'],
            ])->save();
            foreach (Language::whereIn('id', array_keys($data['languages']))->get() as $language) {
                $translation = $data['languages'][$language->id];
                $brand->translations()->updateOrCreate(['lacale' => $language->locale], [
                    'title' => $translation['title'] ?? '',
                ]);
            }
        });
    }
}
