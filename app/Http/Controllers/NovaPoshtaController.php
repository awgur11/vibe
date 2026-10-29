<?php

namespace App\Http\Controllers;

use App\Services\NovaPoshtaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NovaPoshtaController extends Controller
{
    public function __construct(private NovaPoshtaService $novaPoshta) {}

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        try {
            return response()->json(['cities' => $this->novaPoshta->cities($data['q'])]);
        } catch (RuntimeException) {
            return response()->json(['message' => __('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.')], 503);
        }
    }

    public function warehouses(Request $request): JsonResponse
    {
        $data = $request->validate(['city_ref' => ['required', 'string', 'uuid']]);
        try {
            $warehouses = array_map(fn (array $warehouse): array => ['ref' => $warehouse['ref'], 'label' => $warehouse['label']], $this->novaPoshta->warehouses($data['city_ref']));

            return response()->json(['warehouses' => $warehouses]);
        } catch (RuntimeException) {
            return response()->json(['message' => __('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.')], 503);
        }
    }
}
