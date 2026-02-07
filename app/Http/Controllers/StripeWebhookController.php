<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Handle Stripe webhooks
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                // Also handle service request payments
                if (isset($session->metadata->type) && $session->metadata->type === 'service_request') {
                    $this->handleServiceRequestPayment($session);
                }
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            case 'invoice.paid':
                $this->handleInvoicePaid($event->data->object);
                $this->handleCommissionCreation($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', [
                    'type' => $event->type,
                ]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle checkout.session.completed
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        $organizationId = $session->metadata->organization_id ?? null;
        if (!$organizationId) {
            Log::warning('Checkout session missing organization_id', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $organization = Organization::find($organizationId);
        if (!$organization) {
            Log::warning('Organization not found for checkout session', [
                'organization_id' => $organizationId,
                'session_id' => $session->id,
            ]);
            return;
        }

        // Get subscription
        $subscriptionId = $session->subscription;
        if ($subscriptionId) {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $planKey = $session->metadata->plan_key ?? 'pro';

            $organization->update([
                'stripe_subscription_id' => $subscriptionId,
                'stripe_customer_id' => $session->customer,
                'plan_key' => $planKey,
                'plan_status' => $subscription->status === 'trialing' ? 'trialing' : 'active',
                'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
                'usage_period_started_at' => date('Y-m-d H:i:s', $subscription->current_period_start),
                'usage_period_ends_at' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ]);
        }
    }

    /**
     * Handle customer.subscription.updated
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        $organization = Organization::where('stripe_subscription_id', $subscription->id)->first();
        if (!$organization) {
            return;
        }

        // Determine plan key from price ID
        $priceId = $subscription->items->data[0]->price->id;
        $plan = \App\Models\Plan::where('stripe_price_id_monthly', $priceId)
            ->orWhere('stripe_price_id_yearly', $priceId)
            ->first();

        $organization->update([
            'plan_status' => $subscription->status === 'trialing' ? 'trialing' : 'active',
            'plan_key' => $plan ? $plan->code : $organization->plan_key,
            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d H:i:s', $subscription->trial_end) : null,
            'usage_period_started_at' => date('Y-m-d H:i:s', $subscription->current_period_start),
            'usage_period_ends_at' => date('Y-m-d H:i:s', $subscription->current_period_end),
        ]);
    }

    /**
     * Handle customer.subscription.deleted
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        $organization = Organization::where('stripe_subscription_id', $subscription->id)->first();
        if (!$organization) {
            return;
        }

        $organization->update([
            'plan_key' => 'free',
            'plan_status' => 'canceled',
            'stripe_subscription_id' => null,
        ]);
    }

    /**
     * Handle invoice.payment_failed
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        $customerId = $invoice->customer;
        $organization = Organization::where('stripe_customer_id', $customerId)->first();
        if (!$organization) {
            return;
        }

        $organization->update([
            'plan_status' => 'past_due',
        ]);
    }

    /**
     * Handle invoice.paid
     */
    protected function handleInvoicePaid($invoice)
    {
        $customerId = $invoice->customer;
        $organization = Organization::where('stripe_customer_id', $customerId)->first();
        if (!$organization) {
            return;
        }

        if ($organization->plan_status === 'past_due') {
            $organization->update([
                'plan_status' => 'active',
            ]);
        }
    }

    /**
     * Handle commission creation on invoice paid
     */
    protected function handleCommissionCreation($invoice)
    {
        $customerId = $invoice->customer;
        $organization = \App\Models\Organization::where('stripe_customer_id', $customerId)->first();
        
        if (!$organization) {
            return;
        }

        // Find referral for this organization
        $referral = \App\Models\Referral::where('referred_org_id', $organization->id)
            ->where('status', '!=', \App\Models\Referral::STATUS_CANCELED)
            ->first();

        if (!$referral || !$referral->affiliate) {
            return;
        }

        $subscriptionId = $invoice->subscription;
        $isFirstInvoice = $this->isFirstInvoice($subscriptionId, $invoice->id);

        // Commission rate (20% for first, 10% recurring - configurable)
        $commissionRate = $isFirstInvoice ? 20.00 : 10.00;
        $amountCents = (int) ($invoice->amount_paid * $commissionRate / 100);

        if ($amountCents <= 0) {
            return;
        }

        // Create commission
        \App\Models\AffiliateCommission::create([
            'affiliate_id' => $referral->affiliate_id,
            'referral_id' => $referral->id,
            'organization_id' => $organization->id,
            'stripe_invoice_id' => $invoice->id,
            'stripe_subscription_id' => $subscriptionId,
            'amount_cents' => $amountCents,
            'currency' => strtolower($invoice->currency),
            'commission_rate' => $commissionRate,
            'type' => $isFirstInvoice ? 'subscription_first' : 'subscription_recurring',
            'status' => 'pending',
            'eligible_at' => now()->addDays(14), // 14-day refund window
        ]);

        // Update referral status if first conversion
        if ($isFirstInvoice && $referral->status !== \App\Models\Referral::STATUS_CONVERTED) {
            $referral->update(['status' => \App\Models\Referral::STATUS_CONVERTED]);
        }
    }

    /**
     * Handle charge refunded - reverse commission
     */
    protected function handleChargeRefunded($charge)
    {
        $invoiceId = $charge->invoice;
        if (!$invoiceId) {
            return;
        }

        $commissions = \App\Models\AffiliateCommission::where('stripe_invoice_id', $invoiceId)
            ->where('status', '!=', 'reversed')
            ->get();

        foreach ($commissions as $commission) {
            $commission->update(['status' => 'reversed']);
        }
    }

    /**
     * Check if this is the first invoice for a subscription
     */
    protected function isFirstInvoice(?string $subscriptionId, string $invoiceId): bool
    {
        if (!$subscriptionId) {
            return true; // One-time payment
        }

        // Check if there are other paid invoices for this subscription
        $otherInvoices = \App\Models\AffiliateCommission::where('stripe_subscription_id', $subscriptionId)
            ->where('stripe_invoice_id', '!=', $invoiceId)
            ->exists();

        return !$otherInvoices;
    }

    /**
     * Handle service request payment
     */
    protected function handleServiceRequestPayment($session)
    {
        $serviceRequestId = $session->metadata->service_request_id ?? null;
        if (!$serviceRequestId) {
            return;
        }

        $serviceRequest = \App\Models\ServiceRequest::find($serviceRequestId);
        if (!$serviceRequest) {
            Log::warning('Service request not found for payment', [
                'service_request_id' => $serviceRequestId,
                'session_id' => $session->id,
            ]);
            return;
        }

        // Mark as approved and in progress
        $serviceRequest->update([
            'status' => \App\Models\ServiceRequest::STATUS_APPROVED,
        ]);
    }
}
