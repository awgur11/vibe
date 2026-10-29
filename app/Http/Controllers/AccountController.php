<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function profile(Request $request, SiteViewData $siteViewData): View
    {
        return view('site.account.profile', [...$siteViewData->get(), 'user' => $request->user()]);
    }

    public function orders(Request $request, SiteViewData $siteViewData): View
    {
        return view('site.account.orders', [...$siteViewData->get(), 'orders' => Order::where('user_id', $request->user()->id)
            ->with('items', 'delivery')->latest('id')->paginate(10)]);
    }
}
