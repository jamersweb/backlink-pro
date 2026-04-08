<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\RunSeoAuditJob;
use App\Models\Audit;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController extends Controller
{
    // Form dikhane ka method
    public function show()
    {
        return Inertia::render('Auth/Register', [
            'prefill' => [
                'name' => request()->query('name', ''),
                'email' => request()->query('email', ''),
                'audit_url' => request()->query('audit_url', ''),
            ],
        ]);
    }

    // Form submit hone per register karne ka method
    public function register(Request $request)
    {
        // Validation
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|confirmed|min:8',
            'audit_url'             => 'nullable|url|max:2048',
        ]);

        // User create (password set plain so User model 'hashed' cast hashes once)
        $user = User::create([
            'name'     => $data['name'],
            'email'    => strtolower(trim($data['email'])),
            'password' => $data['password'],
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
        $user->startFreeTrialIfEligible();

        if (!empty($data['audit_url'])) {
            $audit = $this->createOnboardingAudit($user, $data['audit_url']);

            $reportUrl = route('audit-report.show', ['id' => $audit->id]);

            if ($request->header('X-Inertia')) {
                return Inertia::location($reportUrl);
            }

            return redirect($reportUrl)->with('status', 'verification-link-sent');
        }
        
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

    protected function createOnboardingAudit(User $user, string $url): Audit
    {
        $normalizedUrl = $this->normalizeUrl($url);

        $audit = Audit::create([
            'user_id' => $user->id,
            'url' => $url,
            'normalized_url' => $normalizedUrl,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_AUTH,
            'lead_email' => $user->email,
            'share_token' => Str::random(32),
            'pages_limit' => 1,
            'crawl_depth' => 0,
            'started_at' => now(),
            'progress_percent' => 0,
        ]);

        try {
            @set_time_limit(120);
            RunSeoAuditJob::dispatchSync($audit->id);
        } catch (\Throwable $e) {
            \Log::error('Onboarding audit run failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $audit->refresh();

            if ($audit->status !== Audit::STATUS_COMPLETED) {
                $audit->status = Audit::STATUS_FAILED;
                $audit->error = $e->getMessage();
                $audit->finished_at = now();
                $audit->save();
            }
        }

        return $audit->fresh();
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }
}
