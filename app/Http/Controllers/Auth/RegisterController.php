<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegisterController extends Controller
{
    // Form dikhane ka method
    public function show()
    {
        return Inertia::render('Auth/Register');
    }

    // Form submit hone per register karne ka method
    public function register(Request $request)
    {
        // Validation
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|confirmed|min:8',
        ]);

        // User create
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        
        // Default role assign karo (ensure role exists first)
        try {
            $userRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'user', 'guard_name' => 'web']
            );
            $user->assignRole('user');
        } catch (\Exception $e) {
            // If role assignment fails, log but continue
            \Log::warning('Failed to assign user role: ' . $e->getMessage());
        }
        
        // Send email verification notification
        $user->sendEmailVerificationNotification();
        
        // Auto-login user so they can access verification page
        Auth::login($user);
        
        // Redirect to verification notice page
        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}
