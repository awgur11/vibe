<?php

namespace App\Http\Requests;

use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;

class UploadEditorImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
        ];
    }
}
