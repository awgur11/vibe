<?php

namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $language = $this->route('language');

        return [
            'locale' => [
                'required', 'string', 'max:35', 'regex:/^[a-z]{2,3}(?:[_-][A-Za-z0-9]{2,8})*$/',
                Rule::unique('languages', 'locale')->ignore($language instanceof Language ? $language : null),
                ...($language instanceof Language && $language->isBase() ? [Rule::in([config('app.locale')])] : []),
            ],
            'short' => ['required', 'string', 'size:3', 'regex:/^\p{L}{3}$/u'],
            'title' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => __('Поле «:attribute» обязательно.'),
            'max' => __('Поле «:attribute» не должно превышать :max символов.'),
            'locale.unique' => __('Язык с таким locale уже существует.'),
            'locale.regex' => __('Укажите locale в формате en, uk или en_US.'),
            'locale.in' => __('Locale базового языка задаётся в конфигурации.'),
            'short.size' => __('Скорочення має містити рівно три літери.'),
            'short.regex' => __('Скорочення має містити рівно три літери.'),
            'active.boolean' => __('Укажите корректный статус активности.'),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['locale' => __('Locale'), 'short' => __('Краткое название'), 'title' => __('Название'), 'active' => __('Активен')];
    }
}
