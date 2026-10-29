<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCommentRequest;
use App\Models\Product;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteProductController extends Controller
{
    public function show(Product $product, SiteViewData $siteViewData): View
    {
        abort_unless($product->public, 404);
        $product->load('translation', 'catalog.translation', 'pictures', 'fvalues.translation', 'fvalues.filter.translation');

        return view('site.product', [...$siteViewData->get(), 'product' => $product,
            'comments' => $product->comments()->where('status', 'approved')->latest('id')->paginate(10)->fragment('reviews'),
        ]);
    }

    public function comment(StoreProductCommentRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->public, 404);
        $product->comments()->create([...$request->validated(), 'status' => 'pending']);

        return redirect()->to(route('product', ['product' => $product->slag]).'#reviews')->with('comment_status', __('Спасибо! Ваш отзыв отправлен на модерацию.'));
    }
}
