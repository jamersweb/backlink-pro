<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;

class BillingController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Show billing index
     */
    public function index(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $plan = $organization->plan;
        $planLimiter = new \App\Services\Billing\PlanLimiter();

        return Inertia::render('Billing/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'plan_key' => $organization->plan_key,
                'plan_status' => $organization->plan_status,
                'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
                'usage_period_started_at' => $organization->usage_period_started_at?->toIso8601String(),
                'usage_period_ends_at' => $organization->usage_period_ends_at?->toIso8601String(),
            ],
            'currentPlan' => $plan ? [
                'name' => $plan->name,
                'code' => $plan->code,
                'limits' => $plan->limits_json,
            ] : null,
            'usage' => [
                'audits_today' => \App\Services\Billing\UsageRecorder::getUsageCount(
                    $organization,
                    \App\Models\UsageEvent::TYPE_AUDIT_CREATED,
                    now()->startOfDay(),
                    now()->endOfDay()
                ),
                'audits_percentage' => $planLimiter->getUsagePercentage(
                    $organization,
                    \App\Models\UsageEvent::TYPE_AUDIT_CREATED
                ),
            ],
        ]);
    }

    /**
     * Show plans selection
     */
    public function plans(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'tagline' => $plan->tagline,
                    'price_monthly' => $plan->price_monthly,
                    'price_annual' => $plan->price_annual,
                    'stripe_price_id_monthly' => $plan->stripe_price_id_monthly,
                    'stripe_price_id_yearly' => $plan->stripe_price_id_yearly,
                    'limits' => $plan->limits_json,
                    'features' => $plan->features_json,
                    'is_highlighted' => $plan->is_highlighted,
                    'badge' => $plan->badge,
                ];
            });

        return Inertia::render('Billing/Plans', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'plan_key' => $organization->plan_key,
            ],
            'plans' => $plans,
        ]);
    }

    /**
     * Create Stripe checkout session
     */
    public function checkout(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'plan_key' => ['required', 'string', 'in:pro,agency'],
            'interval' => ['required', 'string', 'in:monthly,yearly'],
        ]);

        $plan = Plan::where('code', $validated['plan_key'])->firstOrFail();
        
        $priceId = $validated['interval'] === 'yearly' 
            ? $plan->stripe_price_id_yearly 
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return back()->withErrors(['plan' => 'Plan pricing not configured']);
        }

        // Create or get Stripe customer
        $customerId = $organization->stripe_customer_id;
        if (!$customerId) {
            $customer = \Stripe\Customer::create([
                'email' => $organization->billing_email ?? $organization->owner->email,
                'name' => $organization->billing_name ?? $organization->name,
                'metadata' => [
                    'organization_id' => $organization->id,
                ],
            ]);
            $customerId = $customer->id;
            $organization->update(['stripe_customer_id' => $customerId]);
        }

        // Create checkout session
        $session = Session::create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('billing.success', [
                'organization' => $organization->id,
                'session_id' => '{CHECKOUT_SESSION_ID}',
            ]),
            'cancel_url' => route('billing.plans', $organization),
            'metadata' => [
                'organization_id' => $organization->id,
                'plan_key' => $validated['plan_key'],
            ],
        ]);

        return redirect($session->url);
    }

    /**
     * Redirect to Stripe billing portal
     */
    public function portal(Organization $organization)
    {
        $this->authorize('manage', $organization);

        if (!$organization->stripe_customer_id) {
            return back()->withErrors(['billing' => 'No active subscription found']);
        }

        $session = PortalSession::create([
            'customer' => $organization->stripe_customer_id,
            'return_url' => route('billing.index', $organization),
        ]);

        return redirect($session->url);
    }

    /**
     * Handle successful checkout
     */
    public function success(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $sessionId = $request->query('session_id');
        
        if ($sessionId) {
            try {
                $session = Session::retrieve($sessionId);
                
                return Inertia::render('Billing/Success', [
                    'organization' => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                    ],
                    'session' => [
                        'id' => $session->id,
                        'payment_status' => $session->payment_status,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Stripe session retrieval failed', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('billing.index', $organization)
            ->with('success', 'Subscription activated successfully!');
    }
}
