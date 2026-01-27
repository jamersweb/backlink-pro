<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContactRequest;
use App\Models\PartnerApplication;
use App\Models\FreePlanRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketingLeadsController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', 'all');
        
        $contacts = MarketingContactRequest::query()
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'contacts_page');
        
        $partners = PartnerApplication::query()
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'partners_page');
        
        $freePlans = FreePlanRequest::query()
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['*'], 'free_plans_page');
        
        return Inertia::render('Admin/MarketingLeads/Index', [
            'contacts' => $contacts,
            'partners' => $partners,
            'freePlans' => $freePlans,
            'filters' => [
                'type' => $type,
                'status' => $status,
            ],
        ]);
    }

    public function updateStatus(Request $request, $type, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,closed,reviewing,approved,rejected',
        ]);

        $model = match($type) {
            'contact' => MarketingContactRequest::findOrFail($id),
            'partner' => PartnerApplication::findOrFail($id),
            'free-plan' => FreePlanRequest::findOrFail($id),
            default => abort(404),
        };

        $model->update(['status' => $validated['status']]);

        return back()->with('success', 'Status updated.');
    }
}
