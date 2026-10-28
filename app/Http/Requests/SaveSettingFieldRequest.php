<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SaveSettingFieldRequest extends FormRequest
{
    private ?array $fieldDefinition = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $definition = $this->definition();
        $multilingual = $definition['languages'] ?? false;
        if ($multilingual) {
            Language::ensureBase();
        }
        $locales = $multilingual ? Language::pluck('locale')->all() : [];
        if ($definition['type'] === 'phones') {
            return [
                'value' => ['nullable', 'array', 'max:20'],
                'value.*' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s().-]{2,39}$/D'],
            ];
        }
        if ($definition['type'] === 'preview') {
            return [
                'preview' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
                'preview_url' => ['nullable', 'url:http,https', 'max:2048'],
                'remove_preview' => ['sometimes', 'boolean'],
                'locale' => $multilingual ? ['required', Rule::in($locales)] : ['prohibited'],
            ];
        }

        $valueRules = match ($definition['type']) {
            'checkbox' => ['required', 'boolean'],
            'radio' => ['required', 'string', Rule::in(array_keys($definition['options']))],
            default => ['nullable', 'string', 'max:'.($definition['max'] ?? 1000)],
        };
        if (isset($definition['regex'])) {
            $valueRules[] = 'regex:'.$definition['regex'];
        }
        if (($definition['input_type'] ?? null) === 'email') {
            $valueRules[] = 'email';
        } elseif (($definition['input_type'] ?? null) === 'url') {
            $valueRules[] = 'url:http,https';
        }
        if (! $multilingual) {
            return ['value' => $valueRules];
        }

        $rules = ['value' => ['required', 'array:'.implode(',', $locales)]];
        foreach ($locales as $locale) {
            $rules['value.'.$locale] = ['sometimes', ...$valueRules];
        }

        return $rules;
    }

    /** @return array{type: string, languages: bool, default?: mixed, max?: int, options?: array<string, string>, regex?: string, input_type?: string} */
    public function definition(): array
    {
        if ($this->fieldDefinition !== null) {
            return $this->fieldDefinition;
        }
        try {
            $token = $this->input('_definition');
            if (! is_string($token)) {
                throw new \RuntimeException('Missing setting definition.');
            }
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($payload) || ($payload['key'] ?? null) !== $this->route('key')
                || ($payload['user_id'] ?? null) !== $this->user()?->getAuthIdentifier()
                || ! is_array($payload['definition'] ?? null)
                || ! in_array($payload['definition']['type'] ?? null, ['input', 'textarea', 'checkbox', 'radio', 'preview', 'phones'], true)) {
                throw new \RuntimeException('Invalid setting definition.');
            }

            return $this->fieldDefinition = $payload['definition'];
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['_definition' => __('Форма налаштувань застаріла. Оновіть сторінку та спробуйте ще раз.')]);
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [
            'value' => __('значення'), 'preview' => __('зображення'),
            'preview_url' => __('посилання на зображення'), 'locale' => __('мова'),
        ];
        foreach (Language::all() as $language) {
            $attributes['value.'.$language->locale] = __('значення').' ('.$language->title.')';
        }

        return $attributes;
    }
}
