<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePageRequest;
use App\Http\Requests\UpdatePagePublicationRequest;
use App\Models\Language;
use App\Models\Page;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class PageController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::where('type', '!=', 'articles')->with('translations')->orderBy('position')->orderByDesc('id')->paginate(20), 'baseLanguage' => Language::ensureBase()]);
    }

    public function create(): View
    {
        return $this->form(new Page(['public' => false, 'is_menu' => false, 'position' => 0]));
    }

    public function store(SavePageRequest $request): RedirectResponse
    {
        $this->savePage(new Page, $request);

        return redirect()->route('pages.index')->with('status', __('Страница создана.'));
    }

    public function edit(Page $page): View
    {
        abort_if($page->type === 'articles', 404);

        return $this->form($page);
    }

    public function update(SavePageRequest $request, Page $page): RedirectResponse
    {
        abort_if($page->type === 'articles', 404);

        $this->savePage($page, $request);

        return redirect()->route('pages.index')->with('status', __('Страница сохранена.'));
    }

    public function updatePublication(UpdatePagePublicationRequest $request, Page $page): JsonResponse
    {
        abort_if($page->type === 'articles', 404);
        $page->update(['public' => $request->boolean('public')]);

        return response()->json(['public' => $page->public]);
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->type === 'articles', 404);

        $preview = $page->preview;
        $page->delete();
        $this->imageService->delete($preview);

        return redirect()->route('pages.index')->with('status', __('Страница удалена.'));
    }

    private function savePage(Page $page, SavePageRequest $request): void
    {
        $data = $request->validated();
        $translations = $data['languages'];
        $baseLanguage = Language::ensureBase();
        $oldPreview = $page->preview;
        $data['preview'] = $this->imageService->store(
            $data['preview'] ?? $data['preview_url'] ?? null,
            currentPath: $oldPreview,
            remove: $request->boolean('remove_preview'),
            directory: 'pages',
        );
        $data['slag'] = rtrim(substr(x_slag($translations[$baseLanguage->id]['title']), 0, 255), '-');
        $data['type'] = 'pages';
        unset($data['languages'], $data['remove_preview'], $data['preview_url']);

        try {
            DB::transaction(function () use ($page, $data, $translations): void {
                $page->fill($data)->save();

                $languages = Language::whereIn('id', array_keys($translations))->get();
                foreach ($languages as $language) {
                    $page->translations()->updateOrCreate(
                        ['lacale' => $language->locale],
                        $translations[$language->id],
                    );
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
    }

    private function form(Page $page): View
    {
        Language::ensureBase();

        return view('admin.pages.form', [
            'page' => $page->load('translations'),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }
}
