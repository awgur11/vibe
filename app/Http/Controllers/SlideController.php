<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSlideRequest;
use App\Http\Requests\UpdateSlidePublicationRequest;
use App\Models\Language;
use App\Models\Slide;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SlideController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function index(): View
    {
        return view('admin.slides.index', ['slides' => Slide::with('translations')->orderBy('position')->orderByDesc('id')->paginate(20), 'baseLanguage' => Language::ensureBase()]);
    }

    public function create(): View
    {
        return $this->form(new Slide(['position' => 0, 'public' => false]));
    }

    public function store(SaveSlideRequest $request): RedirectResponse
    {
        $this->saveSlide(new Slide, $request);

        return redirect()->route('slides.index')->with('status', __('Слайд створено.'));
    }

    public function edit(Slide $slide): View
    {
        return $this->form($slide);
    }

    public function update(SaveSlideRequest $request, Slide $slide): RedirectResponse
    {
        $this->saveSlide($slide, $request);

        return redirect()->route('slides.index')->with('status', __('Слайд збережено.'));
    }

    public function updatePublication(UpdateSlidePublicationRequest $request, Slide $slide): JsonResponse
    {
        $slide->update(['public' => $request->boolean('public')]);

        return response()->json(['public' => $slide->public]);
    }

    public function destroy(Slide $slide): RedirectResponse
    {
        $preview = $slide->preview;
        $slide->delete();
        $this->imageService->delete($preview);

        return redirect()->route('slides.index')->with('status', __('Слайд видалено.'));
    }

    private function saveSlide(Slide $slide, SaveSlideRequest $request): void
    {
        $data = $request->validated();
        $translations = $data['languages'];
        $oldPreview = $slide->preview;
        $data['preview'] = $this->imageService->store(
            $data['preview'] ?? $data['preview_url'] ?? null,
            currentPath: $oldPreview,
            remove: $request->boolean('remove_preview'),
            directory: 'slides',
        );
        unset($data['preview_url'], $data['remove_preview'], $data['languages']);

        try {
            DB::transaction(function () use ($slide, $data, $translations): void {
                if (! $slide->exists) {
                    $data['position'] = (Slide::max('position') ?? -1) + 1;
                }
                $slide->fill($data)->save();
                foreach (Language::whereIn('id', array_keys($translations))->get() as $language) {
                    $slide->translations()->updateOrCreate(
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

    private function form(Slide $slide): View
    {
        Language::ensureBase();

        return view('admin.slides.form', [
            'slide' => $slide->load('translations'),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }
}
