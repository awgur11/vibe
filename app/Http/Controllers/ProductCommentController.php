<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModerateProductCommentRequest;
use App\Models\ProductComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductCommentController extends Controller
{
    public function index(): View
    {
        return view('admin.product-comments.index', ['comments' => ProductComment::with('product.translation')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest('id')->paginate(25)]);
    }

    public function update(ModerateProductCommentRequest $request, ProductComment $productComment): RedirectResponse|JsonResponse
    {
        $productComment->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['status' => $productComment->status, 'message' => __('Статус отзыва сохранён.')]);
        }

        return back()->with('status', __('Статус отзыва сохранён.'));
    }

    public function destroy(ProductComment $productComment): RedirectResponse
    {
        $productComment->delete();

        return back()->with('status', __('Отзыв удалён.'));
    }
}
