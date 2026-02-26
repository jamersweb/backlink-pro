<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    /**
     * Show pricing/plans page
     */
    public function index()
    {
        $plans = Plan::active()->ordered()->get()->map(function($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->code ?? '',
                'description' => '',
                'price' => $plan->price_monthly ? ($plan->price_monthly / 100) : 0,
                'billing_interval' => 'monthly',
                'features' => $plan->features_json ?? [],
                'max_domains' => $plan->getLimit('domains.max_active'),
            ];
        });
        
        return Inertia::render('Plans', [
            'plans' => $plans,
            'user' => Auth::check() ? Auth::user() : null,
        ]);
    }

    /**
     * Show subscription management page
     */
    public function manage()
    {
        $user = Auth::user();
        $currentPlan = $user->plan;
        $subscription = null;
        $invoices = [];
        $customer = null;

        // Get Stripe subscription details if exists
        if ($user->stripe_subscription_id) {
            try {
                $subscription = StripeSubscription::retrieve($user->stripe_subscription_id);
                
                // Get customer details
                if ($user->stripe_customer_id) {
                    $customer = Customer::retrieve($user->stripe_customer_id);
                }

                // Get recent invoices
                if ($user->stripe_customer_id) {
                    $invoices = \Stripe\Invoice::all([
                        'customer' => $user->stripe_customer_id,
                        'limit' => 10,
                    ])->data;
                }
            } catch (ApiErrorException $e) {
                // Handle error silently
            }
        }

        return Inertia::render('Subscription/Manage', [
            'user' => $user,
            'currentPlan' => $currentPlan,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ] : null,
            'customer' => $customer ? [
                'id' => $customer->id,
                'email' => $customer->email,
            ] : null,
            'invoices' => array_map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'amount_paid' => $invoice->amount_paid / 100, // Convert from cents
                    'currency' => strtoupper($invoice->currency),
                    'status' => $invoice->status,
                    'created' => $invoice->created,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                ];
            }, $invoices),
            'allPlans' => Plan::active()->ordered()->get(),
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $subscription = StripeSubscription::retrieve($user->stripe_subscription_id);
            $subscription->cancel_at_period_end = true;
            $subscription->save();

            return back()->with('success', 'Subscription will be cancelled at the end of the billing period.');
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    /**
     * Resume subscription
     */
    public function resume(Request $request)
    {
        $user = Auth::user();

        if (!$user->stripe_subscription_id) {
            return back()->with('error', 'No subscription found.');
        }

        try {
            $subscription = StripeSubscription::retrieve($user->stripe_subscription_id);
            $subscription->cancel_at_period_end = false;
            $subscription->save();

            return back()->with('success', 'Subscription has been resumed.');
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Failed to resume subscription: ' . $e->getMessage());
        }
    }

    /**
     * Create Stripe checkout session
     */
    public function checkout(Request $request, $plan)
    {
        // Try to find by ID first, then by code
        $planModel = Plan::where('id', $plan)
            ->orWhere('code', $plan)
            ->firstOrFail();
        
        // If free plan, just assign it directly
        $planPrice = $planModel->price_monthly ? ($planModel->price_monthly / 100) : 0;
        if ($planPrice == 0) {
            if (!Auth::check()) {
                return redirect()->route('login')->with('error', 'Please login to activate the free plan.');
            }
            
            $user = Auth::user();
            $user->update([
                'plan_id' => $planModel->id,
                'subscription_status' => 'active',
            ]);
            
            return redirect()->route('subscription.success')->with('success', 'Free plan activated successfully!');
        }
        
        // Require authentication for paid plans
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to subscribe to a plan.');
        }
        
        $user = Auth::user();
        $interval = $request->get('interval', 'monthly') === 'yearly' ? 'yearly' : 'monthly';

        try {
            // Check if Stripe is configured
            if (!config('services.stripe.secret')) {
                return back()->with('error', 'Payment processing is not configured. Please contact support.');
            }

            // Create or retrieve Stripe customer
            $customerId = $user->stripe_customer_id;
            if (!$customerId) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $customerId = $customer->id;
                $user->update(['stripe_customer_id' => $customerId]);
            }

            // Determine Stripe price ID based on interval
            $priceId = $interval === 'yearly'
                ? $planModel->stripe_price_id_yearly
                : $planModel->stripe_price_id_monthly;

            if (!$priceId) {
                return back()->with('error', 'Stripe price not configured for this plan.');
            }

            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer' => $customerId,
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.cancel-page'),
                'metadata' => [
                    'type' => 'user_subscription',
                    'user_id' => $user->id,
                    'plan_code' => $planModel->code,
                    'interval' => $interval,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'type' => 'user_subscription',
                        'user_id' => $user->id,
                        'plan_code' => $planModel->code,
                        'interval' => $interval,
                    ],
                ],
            ]);

            return redirect($session->url);
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Failed to create checkout session: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful subscription
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return Inertia::render('Subscription/Success', [
                'error' => 'Invalid session'
            ]);
        }

        try {
            $session = Session::retrieve($sessionId);
            
            if ($session->payment_status === 'paid' && $session->subscription) {
                $userId = $session->metadata->user_id ?? (Auth::check() ? Auth::id() : null);

                if ($userId) {
                    try {
                        $subscription = StripeSubscription::retrieve($session->subscription);

                        // Determine plan from price ID
                        $priceId = $subscription->items->data[0]->price->id ?? null;
                        $plan = $priceId
                            ? Plan::where('stripe_price_id_monthly', $priceId)
                                ->orWhere('stripe_price_id_yearly', $priceId)
                                ->first()
                            : null;

                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $user->plan_id = $plan?->id ?? $user->plan_id;
                            $user->stripe_customer_id = $subscription->customer;
                            $user->stripe_subscription_id = $subscription->id;
                            $user->subscription_status = $subscription->status;
                            $user->trial_ends_at = $subscription->trial_end
                                ? date('Y-m-d H:i:s', $subscription->trial_end)
                                : null;
                            $user->save();

                            // Ensure a user_subscriptions row exists and is updated
                            $userSubscription = \App\Models\UserSubscription::firstOrNew(['user_id' => $userId]);
                            if (!$userSubscription->started_at) {
                                $userSubscription->started_at = now();
                            }
                            $userSubscription->plan_id = $plan?->id ?? $userSubscription->plan_id;
                            $userSubscription->status = in_array($subscription->status, ['trialing', 'active'])
                                ? \App\Models\UserSubscription::STATUS_ACTIVE
                                : \App\Models\UserSubscription::STATUS_CANCELED;
                            $userSubscription->current_period_start = date('Y-m-d', $subscription->current_period_start);
                            $userSubscription->current_period_end = date('Y-m-d', $subscription->current_period_end);
                            $userSubscription->meta_json = [
                                'stripe_subscription_id' => $subscription->id,
                                'stripe_customer_id' => $subscription->customer,
                                'stripe_price_id' => $priceId,
                                'interval' => $subscription->items->data[0]->price->recurring->interval ?? null,
                            ];
                            $userSubscription->save();
                        }

                        return Inertia::render('Subscription/Success', [
                            'success' => 'Subscription activated successfully!',
                            'plan' => $plan,
                        ]);
                    } catch (ApiErrorException $e) {
                        // Fall through to generic error handling below
                        return Inertia::render('Subscription/Success', [
                            'error' => 'Failed to verify subscription: ' . $e->getMessage()
                        ]);
                    }
                }
            }
        } catch (ApiErrorException $e) {
            return Inertia::render('Subscription/Success', [
                'error' => 'Failed to verify subscription: ' . $e->getMessage()
            ]);
        }

        return Inertia::render('Subscription/Success');
    }

    /**
     * Handle cancelled subscription
     */
    public function cancelPage()
    {
        return Inertia::render('Subscription/Cancel', [
            'info' => 'Subscription cancelled'
        ]);
    }

    /**
     * Handle Stripe webhook
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $this->handleSubscriptionUpdate($subscription);
                break;
            
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionCancellation($subscription);
                break;
            
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $this->handlePaymentSuccess($invoice);
                break;
            
            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $this->handlePaymentFailure($invoice);
                break;
        }

        return response()->json(['received' => true]);
    }

    protected function handleSubscriptionUpdate($subscription)
    {
        $user = \App\Models\User::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($user) {
            $user->update([
                'subscription_status' => $subscription->status,
            ]);
        }
    }

    protected function handleSubscriptionCancellation($subscription)
    {
        $user = \App\Models\User::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($user) {
            $freePlan = Plan::where('code', 'free')->orWhere('code', 'starter')->first();
            $user->update([
                'subscription_status' => 'cancelled',
                'plan_id' => $freePlan?->id,
            ]);
        }
    }

    protected function handlePaymentSuccess($invoice)
    {
        // Payment succeeded, subscription is active
    }

    protected function handlePaymentFailure($invoice)
    {
        $user = \App\Models\User::where('stripe_customer_id', $invoice->customer)->first();
        
        if ($user) {
            $user->update([
                'subscription_status' => 'past_due',
            ]);
        }
    }
}
