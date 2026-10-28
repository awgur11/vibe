<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:catalogs,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['title' => __('назва каталогу'), 'parent_id' => __('батьківський каталог')];
    }
}
