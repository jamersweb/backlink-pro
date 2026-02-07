<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\ServiceCatalog;
use App\Models\Audit;
use App\Models\AuditIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class ServiceRequestController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * List service requests for organization
     */
    public function index(Request $request, \App\Models\Organization $organization)
    {
        $this->authorize('view', $organization);

        $query = $organization->serviceRequests()->with(['audit', 'items.serviceCatalog']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('ServiceRequests/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'requests' => $requests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'status' => $req->status,
                    'priority' => $req->priority,
                    'total_price_cents' => $req->total_price_cents,
                    'currency' => $req->currency,
                    'audit_id' => $req->audit_id,
                    'created_at' => $req->created_at->toIso8601String(),
                ];
            }),
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show service request details
     */
    public function show(\App\Models\Organization $organization, ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $organization);

        if ($serviceRequest->organization_id !== $organization->id) {
            abort(403);
        }

        $serviceRequest->load(['items.serviceCatalog', 'messages', 'assignments.serviceProvider', 'audit']);

        return Inertia::render('ServiceRequests/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'request' => [
                'id' => $serviceRequest->id,
                'status' => $serviceRequest->status,
                'priority' => $serviceRequest->priority,
                'total_price_cents' => $serviceRequest->total_price_cents,
                'currency' => $serviceRequest->currency,
                'notes' => $serviceRequest->notes,
                'scope' => $serviceRequest->scope,
                'items' => $serviceRequest->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'service_catalog' => [
                            'name' => $item->serviceCatalog->name,
                            'description' => $item->serviceCatalog->description,
                        ],
                        'quantity' => $item->quantity,
                        'unit_price_cents' => $item->unit_price_cents,
                        'total_price_cents' => $item->total_price_cents,
                        'meta' => $item->meta,
                    ];
                }),
                'messages' => $serviceRequest->messages->map(function ($msg) {
                    return [
                        'id' => $msg->id,
                        'sender_type' => $msg->sender_type,
                        'message' => $msg->message,
                        'created_at' => $msg->created_at->toIso8601String(),
                    ];
                }),
                'audit' => $serviceRequest->audit ? [
                    'id' => $serviceRequest->audit->id,
                    'url' => $serviceRequest->audit->url,
                ] : null,
            ],
        ]);
    }

    /**
     * Create service request from audit issue
     */
    public function store(Request $request, \App\Models\Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'audit_id' => ['required', 'exists:audits,id'],
            'issue_codes' => ['required', 'array'],
            'issue_codes.*' => ['string'],
            'service_catalog_ids' => ['required', 'array'],
            'service_catalog_ids.*' => ['exists:service_catalog,id'],
        ]);

        $audit = Audit::findOrFail($validated['audit_id']);
        
        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        // Get issues
        $issues = AuditIssue::where('audit_id', $audit->id)
            ->whereIn('code', $validated['issue_codes'])
            ->get();

        // Create service request
        $serviceRequest = ServiceRequest::create([
            'organization_id' => $organization->id,
            'audit_id' => $audit->id,
            'requested_by_user_id' => auth()->id(),
            'status' => ServiceRequest::STATUS_NEW,
            'priority' => ServiceRequest::PRIORITY_NORMAL,
            'currency' => 'usd',
            'scope' => [
                'issue_codes' => $validated['issue_codes'],
                'pages' => $issues->pluck('affected_count')->sum(),
                'target_urls' => [$audit->url],
            ],
        ]);

        // Create service request items
        $totalPrice = 0;
        foreach ($validated['service_catalog_ids'] as $catalogId) {
            $catalog = ServiceCatalog::findOrFail($catalogId);
            $meta = [
                'affected_count' => $issues->count(),
                'issue_codes' => $validated['issue_codes'],
            ];
            
            $unitPrice = $catalog->calculatePrice($meta);
            $quantity = 1;
            
            $serviceRequest->items()->create([
                'service_catalog_id' => $catalogId,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'meta' => $meta,
            ]);

            $totalPrice += $unitPrice * $quantity;
        }

        $serviceRequest->update(['total_price_cents' => $totalPrice]);

        return redirect()->route('service-requests.show', [
            'organization' => $organization->id,
            'serviceRequest' => $serviceRequest->id,
        ]);
    }

    /**
     * Admin: Set quote/price
     */
    public function quote(Request $request, \App\Models\Organization $organization, ServiceRequest $serviceRequest)
    {
        // TODO: Add admin authorization
        if ($serviceRequest->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'total_price_cents' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $serviceRequest->update([
            'total_price_cents' => $validated['total_price_cents'],
            'status' => ServiceRequest::STATUS_QUOTED,
            'notes' => $validated['notes'] ?? $serviceRequest->notes,
        ]);

        return back()->with('success', 'Quote updated.');
    }

    /**
     * Create Stripe checkout for service request
     */
    public function checkout(\App\Models\Organization $organization, ServiceRequest $serviceRequest)
    {
        $this->authorize('manage', $organization);

        if ($serviceRequest->organization_id !== $organization->id) {
            abort(403);
        }

        if ($serviceRequest->status !== ServiceRequest::STATUS_QUOTED) {
            return back()->withErrors(['request' => 'Service request must be quoted before checkout.']);
        }

        if (!$serviceRequest->total_price_cents || $serviceRequest->total_price_cents <= 0) {
            return back()->withErrors(['request' => 'Invalid pricing.']);
        }

        // Get or create Stripe customer
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
                'price_data' => [
                    'currency' => strtolower($serviceRequest->currency),
                    'product_data' => [
                        'name' => 'SEO Fix Service Request #' . $serviceRequest->id,
                        'description' => 'Professional SEO fixes for audit issues',
                    ],
                    'unit_amount' => $serviceRequest->total_price_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('service-requests.show', [
                'organization' => $organization->id,
                'serviceRequest' => $serviceRequest->id,
            ]) . '?payment=success',
            'cancel_url' => route('service-requests.show', [
                'organization' => $organization->id,
                'serviceRequest' => $serviceRequest->id,
            ]),
            'metadata' => [
                'organization_id' => $organization->id,
                'service_request_id' => $serviceRequest->id,
                'type' => 'service_request',
            ],
        ]);

        return redirect($session->url);
    }

    /**
     * Add message to service request
     */
    public function addMessage(Request $request, \App\Models\Organization $organization, ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $organization);

        if ($serviceRequest->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $serviceRequest->messages()->create([
            'sender_type' => ServiceMessage::SENDER_USER,
            'sender_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Message sent.');
    }
}
