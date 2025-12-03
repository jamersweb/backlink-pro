<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

class ProfileController extends Controller
{
    public function __construct()
    {
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    /**
     * Show user profile page
     */
    public function index()
    {
        $user = Auth::user()->load(['plan', 'campaigns', 'domains', 'connectedAccounts']);
        
        $subscription = null;
        $paymentMethod = null;
        
        // Get Stripe subscription details if exists
        if ($user->stripe_subscription_id && config('services.stripe.secret')) {
            try {
                $stripeSubscription = StripeSubscription::retrieve($user->stripe_subscription_id);
                
                // Get payment method from subscription
                if ($stripeSubscription->default_payment_method) {
                    try {
                        $pm = \Stripe\PaymentMethod::retrieve($stripeSubscription->default_payment_method);
                        $paymentMethod = [
                            'type' => $pm->type,
                            'card' => $pm->card ? [
                                'brand' => $pm->card->brand,
                                'last4' => $pm->card->last4,
                                'exp_month' => $pm->card->exp_month,
                                'exp_year' => $pm->card->exp_year,
                            ] : null,
                        ];
                    } catch (ApiErrorException $e) {
                        // Payment method not found or error
                    }
                }
                
                // Get customer details for payment method
                if (!$paymentMethod && $user->stripe_customer_id) {
                    try {
                        $customer = Customer::retrieve($user->stripe_customer_id);
                        if ($customer->invoice_settings->default_payment_method) {
                            $pm = \Stripe\PaymentMethod::retrieve($customer->invoice_settings->default_payment_method);
                            $paymentMethod = [
                                'type' => $pm->type,
                                'card' => $pm->card ? [
                                    'brand' => $pm->card->brand,
                                    'last4' => $pm->card->last4,
                                    'exp_month' => $pm->card->exp_month,
                                    'exp_year' => $pm->card->exp_year,
                                ] : null,
                            ];
                        }
                    } catch (ApiErrorException $e) {
                        // Customer or payment method not found
                    }
                }
                
                $subscription = [
                    'id' => $stripeSubscription->id,
                    'status' => $stripeSubscription->status,
                    'current_period_start' => $stripeSubscription->current_period_start,
                    'current_period_end' => $stripeSubscription->current_period_end,
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                ];
            } catch (ApiErrorException $e) {
                // Subscription not found or error
            }
        }
        
        // Get available upgrade plans based on current plan
        $upgradePlans = [];
        if ($user->plan) {
            $currentPlanSlug = $user->plan->slug;
            $planOrder = ['free' => 1, 'starter' => 2, 'pro' => 3, 'agency' => 4];
            $currentOrder = $planOrder[$currentPlanSlug] ?? 0;
            
            // Get all plans that are higher than current plan
            $upgradePlans = \App\Models\Plan::where('is_active', true)
                ->where(function($query) use ($planOrder, $currentOrder) {
                    foreach ($planOrder as $slug => $order) {
                        if ($order > $currentOrder) {
                            $query->orWhere('slug', $slug);
                        }
                    }
                })
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'price', 'billing_interval', 'description', 'features'])
                ->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                        'price' => $plan->price,
                        'billing_interval' => $plan->billing_interval,
                        'description' => $plan->description,
                        'features' => $plan->features,
                    ];
                });
        } else {
            // If no plan, show all plans
            $upgradePlans = \App\Models\Plan::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'price', 'billing_interval', 'description', 'features'])
                ->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                        'price' => $plan->price,
                        'billing_interval' => $plan->billing_interval,
                        'description' => $plan->description,
                        'features' => $plan->features,
                    ];
                });
        }
        
        return Inertia::render('Profile/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'campaigns_count' => $user->campaigns->count(),
                'domains_count' => $user->domains->count(),
                'connected_accounts_count' => $user->connectedAccounts->count(),
            ],
            'plan' => $user->plan ? [
                'id' => $user->plan->id,
                'name' => $user->plan->name,
                'slug' => $user->plan->slug,
                'price' => $user->plan->price,
                'billing_interval' => $user->plan->billing_interval,
                'description' => $user->plan->description,
                'features' => $user->plan->features,
                'max_domains' => $user->plan->max_domains,
                'max_campaigns' => $user->plan->max_campaigns,
                'daily_backlink_limit' => $user->plan->daily_backlink_limit,
            ] : null,
            'subscription' => $subscription,
            'subscription_status' => $user->subscription_status,
            'payment_method' => $paymentMethod,
            'upgradePlans' => $upgradePlans,
        ]);
    }
}

