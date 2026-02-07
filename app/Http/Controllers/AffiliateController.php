<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\Referral;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    /**
     * Show affiliate dashboard
     */
    public function index()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())
            ->orWhereHas('organization', function ($query) {
                $query->whereHas('users', function ($q) {
                    $q->where('user_id', auth()->id())
                        ->whereIn('role', ['owner', 'admin']);
                });
            })
            ->first();

        if (!$affiliate) {
            return redirect()->route('affiliates.apply');
        }

        $stats = [
            'clicks' => $affiliate->referrals()->where('status', Referral::STATUS_CLICKED)->count(),
            'signups' => $affiliate->referrals()->where('status', Referral::STATUS_SIGNED_UP)->count(),
            'conversions' => $affiliate->referrals()->where('status', Referral::STATUS_CONVERTED)->count(),
            'total_earnings' => $affiliate->total_earnings,
            'pending_earnings' => $affiliate->pending_earnings,
        ];

        $recentCommissions = $affiliate->commissions()
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'amount_cents' => $commission->amount_cents,
                    'currency' => $commission->currency,
                    'type' => $commission->type,
                    'status' => $commission->status,
                    'eligible_at' => $commission->eligible_at?->toIso8601String(),
                    'paid_at' => $commission->paid_at?->toIso8601String(),
                    'organization' => $commission->organization->name ?? 'N/A',
                ];
            });

        return Inertia::render('Affiliate/Overview', [
            'affiliate' => [
                'id' => $affiliate->id,
                'code' => $affiliate->code,
                'status' => $affiliate->status,
            ],
            'stats' => $stats,
            'recentCommissions' => $recentCommissions,
        ]);
    }

    /**
     * Show referral links page
     */
    public function links()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->first();
        
        if (!$affiliate || $affiliate->status !== Affiliate::STATUS_ACTIVE) {
            return redirect()->route('affiliates.index');
        }

        $baseUrl = config('app.url');
        $referralUrl = $baseUrl . '?ref=' . $affiliate->code;

        return Inertia::render('Affiliate/Links', [
            'affiliate' => [
                'code' => $affiliate->code,
            ],
            'referralUrl' => $referralUrl,
        ]);
    }

    /**
     * Show commissions page
     */
    public function commissions()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->first();
        
        if (!$affiliate) {
            return redirect()->route('affiliates.index');
        }

        $commissions = $affiliate->commissions()
            ->with(['organization', 'referral'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Affiliate/Commissions', [
            'affiliate' => [
                'code' => $affiliate->code,
            ],
            'commissions' => $commissions->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'amount_cents' => $commission->amount_cents,
                    'currency' => $commission->currency,
                    'type' => $commission->type,
                    'status' => $commission->status,
                    'commission_rate' => $commission->commission_rate,
                    'eligible_at' => $commission->eligible_at?->toIso8601String(),
                    'paid_at' => $commission->paid_at?->toIso8601String(),
                    'organization' => $commission->organization->name ?? 'N/A',
                    'referral' => [
                        'id' => $commission->referral->id,
                        'status' => $commission->referral->status,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Show payouts page
     */
    public function payouts()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->first();
        
        if (!$affiliate) {
            return redirect()->route('affiliates.index');
        }

        $payouts = $affiliate->payouts()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Affiliate/Payouts', [
            'affiliate' => [
                'code' => $affiliate->code,
                'payout_method' => $affiliate->payout_method,
            ],
            'payouts' => $payouts->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'amount_cents' => $payout->amount_cents,
                    'currency' => $payout->currency,
                    'status' => $payout->status,
                    'paid_at' => $payout->paid_at?->toIso8601String(),
                    'payout_reference' => $payout->payout_reference,
                ];
            }),
        ]);
    }

    /**
     * Update payout method
     */
    public function updatePayoutMethod(Request $request)
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->first();
        
        if (!$affiliate) {
            abort(404);
        }

        $validated = $request->validate([
            'payout_method' => ['required', 'in:bank,paypal,wise,manual'],
            'payout_details' => ['nullable', 'array'],
        ]);

        $affiliate->update($validated);

        return back()->with('success', 'Payout method updated.');
    }

    /**
     * Apply to become affiliate
     */
    public function apply()
    {
        return Inertia::render('Affiliate/Apply');
    }

    /**
     * Store affiliate application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32', 'unique:affiliates,code'],
        ]);

        // Check if user already has affiliate
        $existing = Affiliate::where('user_id', auth()->id())->first();
        if ($existing) {
            return redirect()->route('affiliates.index');
        }

        $affiliate = Affiliate::create([
            'user_id' => auth()->id(),
            'code' => $validated['code'] ?? strtoupper(Str::random(8)),
            'status' => Affiliate::STATUS_PENDING,
        ]);

        return redirect()->route('affiliates.index')
            ->with('success', 'Affiliate application submitted. It will be reviewed shortly.');
    }
}
