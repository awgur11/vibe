<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OneCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OneCController extends Controller
{
    public function order(Request $request, Order $order, OneCService $service): StreamedResponse
    {
        $expected = (string) config('onec.api_key');
        // abort_unless($expected !== '' && hash_equals($expected, (string) $request->header('X-API-Key')), 401);
        $path = $service->exportOrder($order);

        return Storage::disk(config('onec.orders_disk'))->download($path, 'M'.$order->id.'.xml', ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function __invoke(Request $request, OneCService $service): JsonResponse
    {
        try {
            $xml = $service->parse($request->getContent());
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'code' => trim($request->getContent()) === '' ? 1001 : 1004, 'error' => trim($request->getContent()) === '' ? 'empty_xml' : 'invalid_xml', 'message' => $exception->getMessage()], 422);
        }
        $key = (string) $xml->api_key;
        $expected = (string) config('onec.api_key');
        if ($expected === '' || $key === '' || ! hash_equals($expected, $key)) {
            return response()->json(['success' => false, 'code' => $key === '' ? 1002 : 1003, 'error' => $key === '' ? 'empty_api_key' : 'invalid_api_key'], 401);
        }
        try {
            $count = $service->import($xml, $request->routeIs('onec.prices'));
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'code' => 1004, 'error' => 'invalid_xml', 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'code' => 1000, 'message' => 'Products have been imported.', 'imported' => $count]);
    }
}
