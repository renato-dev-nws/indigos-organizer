<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthenticatedSessionController extends Controller
{
    public function redirect(): RedirectResponse
    {
        /** @var RedirectResponse $response */
        $response = Socialite::driver('google')->redirect();

        return $response;
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Nao foi possivel autenticar com o Google. Tente novamente.',
            ]);
        }

        $email = Str::lower((string) $googleUser->getEmail());
        if ($email === '' || ! $this->isGmailAddress($email)) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Somente usuarios com email Gmail podem entrar.',
            ]);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Usuario nao encontrado. Somente contas previamente cadastradas podem entrar.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function isGmailAddress(string $email): bool
    {
        return Str::endsWith($email, ['@gmail.com', '@googlemail.com']);
    }
}
