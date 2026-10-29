<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallbackRequest;
use App\Mail\CallbackRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class CallbackController extends Controller
{
    public function store(StoreCallbackRequest $request): JsonResponse
    {
        $email = setting('admin_email');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => __('Не удалось отправить заявку. Позвоните нам или попробуйте позже.')], 503);
        }
        $data = $request->validated();
        Mail::to($email)->locale(app()->getLocale())->queue(new CallbackRequested($data['name'], $data['phone']));

        return response()->json(['message' => __('Спасибо! Мы перезвоним вам в ближайшее рабочее время.')]);
    }
}
