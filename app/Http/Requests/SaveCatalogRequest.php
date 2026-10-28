<?php

namespace App\Http\Requests;

use App\Models\Catalog;
use App\Models\Fvalue;
use App\Models\Language;
use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveCatalogRequest extends FormRequest
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
            'fvalues' => ['sometimes', 'array'],
            'fvalues.*' => ['integer', 'distinct', 'exists:fvalues,id'],
            'filters' => ['sometimes', 'array'],
            'filters.*' => ['integer', 'distinct', 'exists:filters,id'],
            'parent_id' => ['nullable', 'integer', 'exists:catalogs,id'],
            'slag' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('catalogs', 'slag')->ignore($this->route('catalog'))],
            'public' => ['required', 'boolean'],
            'popular' => ['sometimes', 'boolean'],
            'preview' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
            'preview_url' => ['nullable', 'url:http,https', 'max:2048'],
            'remove_preview' => ['sometimes', 'boolean'],
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
        ];
        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:title,short_content,content,tag_title,tag_description'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
            $rules[$prefix.'.short_content'] = ['nullable', 'string'];
            $rules[$prefix.'.content'] = ['nullable', 'string'];
            $rules[$prefix.'.tag_title'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.tag_description'] = ['nullable', 'string'];
        }

        return $rules;
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->validated();
            if (Fvalue::whereIn('id', $data['fvalues'] ?? [])->whereNotIn('filter_id', $data['filters'] ?? [])->exists()) {
                $validator->errors()->add('fvalues', __('Оберіть фільтри для вибраних значень.'));
            }
        }, function (Validator $validator): void {
            if ($validator->errors()->has('parent_id')) {
                return;
            }
            $catalog = $this->route('catalog');
            $parentId = $this->input('parent_id');
            $visited = [];
            while ($parentId !== null) {
                if ((int) $parentId === $catalog->id || isset($visited[$parentId])) {
                    $validator->errors()->add('parent_id', __('Не можна створювати циклічну ієрархію каталогів.'));

                    return;
                }
                $visited[$parentId] = true;
                $parentId = Catalog::find($parentId)?->parent_id;
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = ['parent_id' => __('батьківський каталог'), 'slag' => 'slag', 'public' => __('публікація'), 'preview' => __('зображення'), 'preview_url' => __('посилання на зображення')];
        foreach (Language::all() as $language) {
            foreach (['title' => __('Заголовок'), 'short_content' => __('Короткий опис'), 'content' => __('Вміст'), 'tag_title' => 'tag_title', 'tag_description' => 'tag_description'] as $field => $label) {
                $attributes['languages.'.$language->id.'.'.$field] = $label.' ('.$language->title.')';
            }
        }

        return $attributes;
    }
}
