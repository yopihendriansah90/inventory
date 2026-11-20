<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetInventoryLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventorySessionController extends Controller
{
    public function language(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(SetInventoryLocale::LANGUAGES)],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $target = $data['return_to'] ?? '';
        if (! preg_match('~\A/(?:signin|inventory(?:/[A-Za-z0-9/-]+)?)(?:\?[^\r\n#]*)?\z~', $target)) {
            $target = route('inventory.home');
        }

        return redirect()->to($target)
            ->withCookie(cookie(SetInventoryLocale::COOKIE, $data['locale'], 60 * 24 * 365));
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attemptWhen($credentials, fn ($user) => in_array($user->role, ['qc', 'warehouse', 'cs'], true))) {
            throw ValidationException::withMessages(['email' => __('inventory.login.invalid')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('inventory.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin');
    }
}
