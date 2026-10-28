<?php

namespace App\Http\Requests;

use App\Services\UploadLimits;
use Illuminate\Foundation\Http\FormRequest;

class StorePictureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'image' => ['required_without:preview_url', 'prohibits:preview_url', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', ...UploadLimits::rules()],
            'preview_url' => ['required_without:image', 'prohibits:image', 'url:http,https', 'max:2048'],
        ];
    }
}
