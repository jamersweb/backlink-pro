<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    // Form dikhane ka method
    public function show()
    {
        return view('auth.register');
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
    // Default role assign karo
    $user->assignRole('user');
        // Auto login
        Auth::login($user);

        return redirect()->route('/');
    }
}
