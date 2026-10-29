<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9][0-9\s().-]{6,39}$/D']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => __('Имя'), 'phone' => __('Телефон')];
    }
}
