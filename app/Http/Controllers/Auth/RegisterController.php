<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;
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
        
        // Attach referral if cookie exists
        $this->attachReferralToUser($user, $request);
        
        // Auto-login user so they can access verification page
        Auth::login($user);
        
        // Redirect to verification notice page
        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }

    /**
     * Attach referral to user if cookie exists
     */
    protected function attachReferralToUser(User $user, Request $request): void
    {
        $refCookie = $request->cookie('bp_ref');
        if (!$refCookie) {
            return;
        }

        try {
            $affiliateId = Crypt::decryptString($refCookie);
            
            // Find active referral for this visitor (within 30 days)
            $referral = Referral::where('affiliate_id', $affiliateId)
                ->where('visitor_id', $request->cookie('bp_visitor_id'))
                ->where('first_touch_at', '>', now()->subDays(30))
                ->whereNull('referred_user_id')
                ->first();

            if ($referral) {
                $referral->update([
                    'referred_user_id' => $user->id,
                    'status' => Referral::STATUS_SIGNED_UP,
                ]);
            }
        } catch (\Exception $e) {
            // Invalid cookie, ignore
            \Log::debug('Failed to attach referral: ' . $e->getMessage());
        }
    }
}
