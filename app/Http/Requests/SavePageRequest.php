<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Models\Page;
use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavePageRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'max:255'],
            'template' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/D'],
            'slag' => ['nullable', 'string', 'max:255'],
            'preview' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
            'preview_url' => ['nullable', 'url:http,https', 'max:255'],
            'remove_preview' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('pages', 'id'), Rule::notIn([$this->route('page')?->id])],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
            'is_menu' => ['sometimes', 'required', 'boolean'],
            'public' => ['required', 'boolean'],
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
        ];

        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:menu,title,content,short_content,tag_title,tag_description'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
            $rules[$prefix.'.menu'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.content'] = ['nullable', 'string'];
            $rules[$prefix.'.short_content'] = ['nullable', 'string'];
            $rules[$prefix.'.tag_title'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.tag_description'] = ['nullable', 'string'];
        }

        return $rules;
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('parent_id')) {
                return;
            }
            $page = $this->route('page');
            if (! $page instanceof Page) {
                return;
            }
            $parentId = $this->input('parent_id');
            $visited = [];
            while ($parentId !== null) {
                if ((int) $parentId === $page->id || isset($visited[$parentId])) {
                    $validator->errors()->add('parent_id', __('Нельзя создавать циклическую иерархию страниц.'));
                    return;
                }
                $visited[$parentId] = true;
                $parentId = Page::find($parentId)?->parent_id;
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['required' => __('Поле «:attribute» обязательно.')];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = ['preview' => __('Зображення'), 'preview_url' => __('Посилання на зображення'), 'remove_preview' => __('Видалити зображення'), 'position' => __('Позиція'), 'is_menu' => __('Показувати в меню'), 'parent_id' => __('Батьківська сторінка'), 'template' => __('Шаблон сторінки'), 'public' => __('Опубликована'), 'languages' => __('Переводы')];
        foreach (Language::all() as $language) {
            foreach (['menu' => __('Название в меню'), 'short_content' => __('Краткое содержание'), 'title' => __('Заголовок'), 'content' => __('Содержание'), 'tag_title' => 'tag_title', 'tag_description' => 'tag_description'] as $field => $label) {
                $attributes['languages.'.$language->id.'.'.$field] = $label.' ('.$language->title.')';
            }
        }

        return $attributes;
    }
}
