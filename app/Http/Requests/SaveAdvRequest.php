<?php

namespace App\Http\Requests;

use App\Models\Adv;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAdvRequest extends FormRequest
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
            'icon' => ['required', 'string', Rule::in(array_keys(Adv::ICONS))],
            'position' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'public' => ['required', 'boolean'],
            'languages' => ['required', 'array:'.$languages->pluck('id')->implode(',')],
        ];
        foreach ($languages as $language) {
            $prefix = 'languages.'.$language->id;
            $rules[$prefix] = [$language->isBase() ? 'required' : 'sometimes', 'array:title,description'];
            $rules[$prefix.'.title'] = [$language->isBase() ? 'required' : 'nullable', 'string', 'max:255'];
            $rules[$prefix.'.description'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
