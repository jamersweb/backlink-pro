<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * POST /login — validate, attempt (web guard), regenerate session, redirect.
     * Email is normalized (trim + lowercase) to match registration.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim((string) $credentials['email']));
        $credentials['email'] = $email;
        $guard = 'web';

        if (config('app.debug')) {
            $user = \App\Models\User::where('email', $email)->first();
            $hashPrefix = $user ? (substr($user->getAuthPassword(), 0, 4) ?: null) : null;
            Log::channel('single')->debug('Login attempt', [
                'email_normalized' => $email,
                'user_found'       => $user !== null,
                'user_id'          => $user?->id,
                'hash_prefix'      => $hashPrefix,
                'guard'            => $guard,
            ]);
        }

        if (Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (config('app.debug')) {
                Log::channel('single')->debug('Login success', ['user_id' => $user->id]);
            }

            if ($user->hasRole('admin')) {
                return redirect()->intended('/admin/dashboard');
            }

            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('status', 'Please verify your email address to continue.');
            }

            return redirect()->intended('/dashboard');
        }

        if (config('app.debug')) {
            Log::channel('single')->debug('Login failed', ['email_normalized' => $email]);
        }

        return back()->withErrors([
            'email' => 'Invalid credentials. Please check your email and password.',
        ])->onlyInput('email');
    }

    // Logout method
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
