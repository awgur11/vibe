<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($this->route('setting'))],
            'value' => ['bail', 'required', 'string', 'json', function (string $attribute, mixed $value, Closure $fail): void {
                if (strlen($value) > 65535) {
                    $fail(__('Значення JSON не має перевищувати 65535 байтів.'));
                }
            }],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['key' => __('ключ'), 'value' => __('значення')];
    }
}
