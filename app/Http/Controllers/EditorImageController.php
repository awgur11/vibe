<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadEditorImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EditorImageController extends Controller
{
    public function __invoke(UploadEditorImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('editor-images', 'public');

        abort_if($path === false, 500, __('Не вдалося зберегти зображення.'));

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
