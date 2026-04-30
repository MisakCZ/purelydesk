<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Ldap\LdapAuthenticationException;
use App\Services\Ldap\LdapAuthenticator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, LdapAuthenticator $ldapAuthenticator): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'regex:/^[^\x00\(\)\*\\\\]+$/u'],
            'password' => ['required', 'string'],
        ]);

        try {
            $user = $ldapAuthenticator->authenticate($validated['username'], $validated['password']);
        } catch (LdapAuthenticationException $exception) {
            throw ValidationException::withMessages([
                'username' => $exception->getMessage(),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
