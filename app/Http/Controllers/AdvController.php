<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdvRequest;
use App\Models\Adv;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdvController extends Controller
{
    public function index(): View
    {
        return view('admin.advs.index', [
            'advs' => Adv::with('translations')->orderBy('position')->orderBy('id')->get(),
            'baseLanguage' => Language::ensureBase(),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function store(SaveAdvRequest $request): RedirectResponse
    {
        $this->save($request, new Adv);

        return redirect()->route('advs.index')->with('status', __('Преимущество создано.'));
    }

    public function update(SaveAdvRequest $request, Adv $adv): RedirectResponse
    {
        $this->save($request, $adv);

        return redirect()->route('advs.index')->with('status', __('Преимущество сохранено.'));
    }

    public function destroy(Adv $adv): RedirectResponse
    {
        $adv->delete();

        return redirect()->route('advs.index')->with('status', __('Преимущество удалено.'));
    }

    private function save(SaveAdvRequest $request, Adv $adv): void
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $adv): void {
            $adv->fill([
                'icon' => $data['icon'],
                'position' => $data['position'] ?? ($adv->exists ? $adv->position : (Adv::max('position') ?? -1) + 1),
                'public' => $data['public'],
            ])->save();
            foreach (Language::whereIn('id', array_keys($data['languages']))->get() as $language) {
                $translation = $data['languages'][$language->id];
                $adv->translations()->updateOrCreate(['lacale' => $language->locale], [
                    'title' => $translation['title'] ?? '',
                    'description' => $translation['description'] ?? null,
                ]);
            }
        });
    }
}
