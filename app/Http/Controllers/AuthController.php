<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'lowercase', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = User::create($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['authenticated' => true, 'redirect' => route('verification.notice')]);
        }

        return redirect()->route('verification.notice');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('Неверный email или пароль.')]);
        }

        $request->session()->regenerate();

        if (! $request->user()->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? response()->json(['authenticated' => true, 'redirect' => route('verification.notice')])
                : redirect()->route('verification.notice');
        }

        if ($request->expectsJson()) {
            return response()->json(['authenticated' => true]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('Если аккаунт с этим email существует, письмо со ссылкой для восстановления отправлено.'));
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withErrors(['email' => __('Ссылка недействительна или устарела. Запросите восстановление ещё раз.')]);
        }

        return redirect()->route('login')->with('status', __('Пароль изменён. Войдите с новым паролем.'));
    }
}
