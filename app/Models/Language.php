<?php

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Locale;

#[Fillable(['locale', 'short', 'title', 'active'])]
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function isBase(): bool
    {
        return $this->locale === config('app.locale');
    }

    public static function ensureBase(): self
    {
        $locale = config('app.locale');

        return static::firstOrCreate(['locale' => $locale], [
            'short' => static::shortForLocale($locale),
            'title' => Locale::getDisplayName($locale, app()->getLocale()) ?: $locale,
            'active' => true,
        ]);
    }

    public static function shortForLocale(string $locale): string
    {
        $primary = Locale::getPrimaryLanguage($locale);

        return strtoupper([
            'en' => 'eng', 'uk' => 'ukr', 'ru' => 'rus', 'fr' => 'fra',
            'de' => 'deu', 'es' => 'spa', 'it' => 'ita', 'pl' => 'pol',
            'pt' => 'por', 'ja' => 'jpn', 'zh' => 'zho', 'ar' => 'ara',
        ][$primary] ?? substr(str_pad($primary, 3, 'x'), 0, 3));
    }

    protected static function booted(): void
    {
        static::deleting(function (Language $language): void {
            if ($language->isBase()) {
                throw ValidationException::withMessages(['language' => __('Базовый язык из конфигурации нельзя удалить.')]);
            }
        });

        static::updating(function (Language $language): void {
            if ($language->getOriginal('locale') === config('app.locale') && $language->isDirty('locale')) {
                throw ValidationException::withMessages(['locale' => __('Locale базового языка задаётся в конфигурации и не может быть изменён здесь.')]);
            }
        });
    }
}
