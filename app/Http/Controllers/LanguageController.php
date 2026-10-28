<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLanguageRequest;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    public function index(): View
    {
        Language::ensureBase();

        return view('admin.languages.index', [
            'languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.languages.form', ['language' => new Language(['active' => true])]);
    }

    public function store(SaveLanguageRequest $request): RedirectResponse
    {
        Language::create($request->validated());

        return redirect()->route('languages.index')->with('status', __('Язык создан.'));
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.form', ['language' => $language]);
    }

    public function update(SaveLanguageRequest $request, Language $language): RedirectResponse
    {
        $language->update($request->validated());

        return redirect()->route('languages.index')->with('status', __('Язык сохранён.'));
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('languages.index')->with('status', __('Язык удалён.'));
    }
}
