<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadsController extends Controller
{
    /**
     * Show verified users
     */
    public function verifiedUsers(Request $request)
    {
        $users = User::whereNotNull('email_verified_at')
            ->with('plan:id,name')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Leads/VerifiedUsers', [
            'users' => $users,
            'total' => User::whereNotNull('email_verified_at')->count(),
        ]);
    }

    /**
     * Show non-verified users
     */
    public function nonVerifiedUsers(Request $request)
    {
        $users = User::whereNull('email_verified_at')
            ->with('plan:id,name')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Leads/NonVerifiedUsers', [
            'users' => $users,
            'total' => User::whereNull('email_verified_at')->count(),
        ]);
    }

    /**
     * Show purchase users (users with active subscription or plan)
     */
    public function purchaseUsers(Request $request)
    {
        $users = User::where(function($query) {
                $query->whereNotNull('plan_id')
                    ->orWhere('subscription_status', 'active');
            })
            ->with('plan:id,name,price')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Leads/PurchaseUsers', [
            'users' => $users,
            'total' => User::where(function($query) {
                $query->whereNotNull('plan_id')
                    ->orWhere('subscription_status', 'active');
            })->count(),
        ]);
    }
}
