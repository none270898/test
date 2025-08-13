<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        \Log::info('Login attempt', $request->all());

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();
        \Log::info('User found', ['user' => $user ? $user->toArray() : null]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            \Log::info('Auth failed');
            throw ValidationException::withMessages([
                'email' => 'Nieprawidłowy email lub hasło.',
            ]);
        }

        \Log::info('Auth success, redirecting');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
