<?php

namespace App\Http\Controllers;

use App\Services\SiteViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request, SiteViewData $siteViewData): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('index');
        }

        return view('site.verify-email', $siteViewData->get());
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('index')->with('email_verified', __('Электронная почта подтверждена.'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('index');
        }
        $request->user()->sendEmailVerificationNotification();

        return back()->with('verification_sent', __('Письмо для подтверждения отправлено. Проверьте почту.'));
    }
}
