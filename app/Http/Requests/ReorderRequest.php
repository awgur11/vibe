<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...config('reordering.resources.'.$this->route('resource').'.scope', []),
            'ids' => ['required', 'array', 'list', 'min:1', ...($this->route('resource') === 'fvalues' ? [] : ['max:100'])],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
