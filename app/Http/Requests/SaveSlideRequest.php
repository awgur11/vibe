<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;

class SaveSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        Language::ensureBase();
        $languages = Language::all();
        $rules = [
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
            'preview' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
            'preview_url' => ['nullable', 'url:http,https', 'max:2048'],
            'remove_preview' => ['sometimes', 'boolean'],
            'link' => ['nullable', 'url:http,https', 'max:2048'],
            'public' => ['required', 'boolean'],
        ];

        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:title,subtitle'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
            $rules[$prefix.'.subtitle'] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [
            'languages' => __('Переклади'),
            'preview' => __('зображення'), 'preview_url' => __('посилання на зображення'),
            'link' => __('посилання на сторінку'), 'public' => __('опубліковано'),
        ];
        foreach (Language::all() as $language) {
            foreach (['title' => __('Заголовок'), 'subtitle' => __('Підзаголовок')] as $field => $label) {
                $attributes['languages.'.$language->id.'.'.$field] = $label.' ('.$language->title.')';
            }
        }

        return $attributes;
    }
}

