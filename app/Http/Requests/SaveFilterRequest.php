<?php

namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFilterRequest extends FormRequest
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
            'slag' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('filters', 'slag')->ignore($this->route('filter'))],
            'position' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
        ];
        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:title'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
