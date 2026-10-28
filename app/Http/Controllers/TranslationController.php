<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranslateRequest;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    public function __invoke(TranslateRequest $request, TranslationService $translationService): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'translation' => $translationService->translate($data['text'], $data['source_locale'], $data['target_locale']),
        ]);
    }
}
