<?php

namespace App\Http\Requests;

use App\Models\Catalog;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProductRequest extends FormRequest
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
            'available' => ['sometimes', 'required', 'integer', Rule::in([0, 1, 2])],
            'articule' => ['nullable', 'string', 'max:255'],
            'public' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'catalog_id' => ['required', 'integer', 'exists:catalogs,id'],
            'slag' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('products', 'slag')->ignore($this->route('product'))],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'discount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'fvalues' => ['sometimes', 'array'],
            'fvalues.*' => ['integer', 'distinct', 'exists:fvalues,id'],
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
        ];
        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:title,description,tag_title,tag_description'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
            $rules[$prefix.'.description'] = ['nullable', 'string'];
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
            $catalog = Catalog::findOrFail($data['catalog_id']);
            $ids = $data['fvalues'] ?? [];
            $availableCount = $catalog->fvalues()->whereIn('fvalues.id', $ids)
                ->whereIn('filter_id', $catalog->filters()->select('filters.id'))->count();
            if ($availableCount !== count($ids)) {
                $validator->errors()->add('fvalues', __('Оберіть значення фільтрів вибраного каталогу.'));
            }
        }];
    }
}
