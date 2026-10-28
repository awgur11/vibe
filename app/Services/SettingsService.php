<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    private const string CACHE_KEY = 'site.settings.all';

    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ((new Setting)->getConnection()->transactionLevel() > 0) {
            return $this->load();
        }

        return $this->settings ??= Cache::rememberForever(self::CACHE_KEY, $this->load(...));
    }

    public function get(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $settings = $this->all();
        if (! array_key_exists($key, $settings)) {
            $separator = strrpos($key, '.');
            if ($separator === false) {
                return $default;
            }
            $imageKey = substr($key, 0, $separator);
            $size = substr($key, $separator + 1);
            if (! in_array($size, ['original', 'origin', ...array_keys(ImageService::THUMB_WIDTHS)], true)) {
                return $default;
            }

            return $this->imageUrl($this->get($imageKey, null, $locale), $size, $default);
        }

        $value = $settings[$key];
        if (is_array($value) && $value !== [] && ! array_is_list($value)
            && array_all(array_keys($value), fn (mixed $language): bool => is_string($language) && preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z0-9]{2,8})*$/D', $language) === 1)) {
            $locale ??= app()->getLocale();
            $fallback = request()->attributes->get('site_base_locale', config('app.fallback_locale'));
            if (array_key_exists($locale, $value)) {
                return $value[$locale];
            }

            return array_key_exists($fallback, $value) ? $value[$fallback] : $default;
        }

        return $value;
    }

    private function imageUrl(mixed $path, string $size, mixed $default): mixed
    {
        if (! is_string($path) || $path === '') {
            return $default;
        }
        if (str_starts_with($path, '/') || str_starts_with($path, 'https://') || str_starts_with($path, 'http://')) {
            return $path;
        }
        if (ImageService::paths($path) === []) {
            return $default;
        }
        if (array_key_exists($size, ImageService::THUMB_WIDTHS)) {
            $thumbnail = ImageService::thumbnailPath($path, $size);
            if (Storage::disk('public')->exists($thumbnail)) {
                $path = $thumbnail;
            }
        }

        return Storage::disk('public')->url($path);
    }

    public function forget(): void
    {
        $this->settings = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        return Setting::pluck('value', 'key')->map(fn (string $value): mixed => json_decode($value, true))->all();
    }
}
