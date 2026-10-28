<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingRequest;
use App\Http\Requests\SaveSettingFieldRequest;
use App\Models\Setting;
use App\Models\Language;
use App\Services\ImageService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SettingController extends Controller
{
    public function __construct(private ImageService $imageService) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('settings.seo');
    }

    public function seo(): View
    {
        return $this->section('seo');
    }

    public function design(): View
    {
        return $this->section('design');
    }

    public function contacts(): View
    {
        return $this->section('contacts');
    }

    private function section(string $section): View
    {
        Language::ensureBase();
        return view('admin.settings.'.$section, [
            'settingValues' => app(SettingsService::class)->all(),
            'site_languages' => Language::orderByRaw('case when locale = ? then 0 else 1 end', [config('app.locale')])->orderBy('title')->get(),
        ]);
    }

    public function saveField(SaveSettingFieldRequest $request, string $key): JsonResponse
    {
        $definition = $request->definition();
        $multilingual = $definition['languages'] ?? false;
        $isImage = $definition['type'] === 'preview';
        $oldPath = null;
        $newPath = null;

        try {
            $value = DB::transaction(function () use ($request, $key, $definition, $multilingual, $isImage, &$oldPath, &$newPath): mixed {
                $setting = Setting::where('key', $key)->lockForUpdate()->first() ?? new Setting(['key' => $key]);
                $current = json_decode($setting->value ?? 'null', true);
                if ($isImage) {
                    $locale = $request->validated('locale');
                    $oldPath = $multilingual ? (is_array($current) ? ($current[$locale] ?? null) : null) : $current;
                    $oldPath = is_string($oldPath) ? $oldPath : null;
                    $source = $request->file('preview') ?? $request->validated('preview_url');
                    if ($source === null && ! $request->boolean('remove_preview')) {
                        throw ValidationException::withMessages(['preview' => __('Виберіть файл або вкажіть посилання на зображення.')]);
                    }
                    $newPath = $this->imageService->store($source, currentPath: $oldPath, remove: $request->boolean('remove_preview'), directory: 'settings');
                    $value = $multilingual ? array_replace(is_array($current) ? $current : [], [$locale => $newPath]) : $newPath;
                } else {
                    $submitted = $request->validated('value');
                    $normalize = fn (mixed $item): mixed => match ($definition['type']) {
                        'checkbox' => filter_var($item, FILTER_VALIDATE_BOOLEAN),
                        'phones' => array_values($item ?? []),
                        default => $item ?? '',
                    };
                    $value = $multilingual
                        ? array_replace(is_array($current) ? $current : [], array_map($normalize, $submitted))
                        : $normalize($submitted);
                }
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                if (strlen($encoded) > 65535) {
                    throw ValidationException::withMessages(['value' => __('Значення JSON не має перевищувати 65535 байтів.')]);
                }
                $setting->value = $encoded;
                $setting->save();

                return $value;
            });
        } catch (Throwable $exception) {
            if ($newPath !== $oldPath) {
                $this->imageService->delete($newPath);
            }

            throw $exception;
        }
        if ($isImage && $oldPath !== $newPath && str_starts_with($oldPath ?? '', 'settings/')) {
            $this->imageService->delete($oldPath);
        }

        return response()->json([
            'value' => $value,
            'preview_url' => $isImage && $newPath !== null ? Storage::disk('public')->url($newPath) : null,
            'message' => __('Налаштування збережено.'),
        ]);
    }

    public function store(SaveSettingRequest $request): RedirectResponse
    {
        Setting::create($request->validated());

        return redirect()->route('settings.index')->with('status', __('Налаштування створено.'));
    }

    public function update(SaveSettingRequest $request, Setting $setting): RedirectResponse
    {
        $setting->update($request->validated());

        return redirect()->route('settings.index')->with('status', __('Налаштування збережено.'));
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        $setting->delete();

        return redirect()->route('settings.index')->with('status', __('Налаштування видалено.'));
    }
}
