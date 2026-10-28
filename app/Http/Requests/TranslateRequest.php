<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TranslateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['bail', 'required', 'string', 'max:4999', function (string $attribute, mixed $value, Closure $fail): void {
                if (strlen(urlencode($value)) >= 5000) {
                    $fail(__('Текст слишком длинный для Google Translate.'));
                }
            }],
            'source_locale' => ['required', 'string', 'max:35', 'regex:/^[a-z]{2,3}(?:[_-][A-Za-z0-9]{2,8})*$/'],
            'target_locale' => ['required', 'string', 'max:35', 'regex:/^[a-z]{2,3}(?:[_-][A-Za-z0-9]{2,8})*$/'],
        ];
    }
}
