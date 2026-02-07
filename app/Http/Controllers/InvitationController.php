<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Services\Billing\PlanLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Store new invitation
     */
    public function store(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:owner,admin,member,viewer'],
        ]);

        // Check seat limit
        $planLimiter = new PlanLimiter();
        if (!$planLimiter->canAddSeat($organization)) {
            return back()->withErrors([
                'email' => 'Seat limit reached. Upgrade your plan to add more team members.',
            ]);
        }

        // Check if user already in org
        $existingUser = $organization->users()
            ->whereHas('user', function ($query) use ($validated) {
                $query->where('email', $validated['email']);
            })
            ->exists();

        if ($existingUser) {
            return back()->withErrors([
                'email' => 'User is already a member of this organization.',
            ]);
        }

        // Check for pending invitation
        $existingInvite = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvite) {
            return back()->withErrors([
                'email' => 'An invitation has already been sent to this email.',
            ]);
        }

        // Create invitation
        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by_user_id' => auth()->id(),
        ]);

        // Send invitation email
        $inviteUrl = route('invitations.accept', $invitation->token);
        // TODO: Create InvitationMail mailable
        // Mail::to($validated['email'])->send(new \App\Mail\InvitationMail($invitation, $inviteUrl));

        return back()->with('success', 'Invitation sent successfully.');
    }

    /**
     * Show invitation acceptance page
     */
    public function show(string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired.');
        }

        return Inertia::render('Invitations/Accept', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'organization' => [
                    'id' => $invitation->organization->id,
                    'name' => $invitation->organization->name,
                ],
            ],
        ]);
    }

    /**
     * Accept invitation
     */
    public function accept(Request $request, string $token)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $invitation = OrganizationInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired.');
        }

        if ($invitation->email !== $validated['email']) {
            return back()->withErrors([
                'email' => 'Email does not match invitation.',
            ]);
        }

        // Find or create user
        $user = \App\Models\User::where('email', $validated['email'])->first();
        
        if (!$user) {
            // Create user account
            $user = \App\Models\User::create([
                'name' => $request->input('name', ''),
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make(Str::random(32)), // Temporary password
                'email_verified_at' => null,
            ]);
            
            // Send password reset email
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $validated['email']]);
        }

        // Add to organization
        $organization = $invitation->organization;
        $organization->users()->create([
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        // Increment seats_used
        $organization->increment('seats_used');

        // Mark invitation as accepted
        $invitation->update([
            'accepted_at' => now(),
        ]);

        // Login user if not already logged in
        if (!auth()->check()) {
            auth()->login($user);
        }

        return redirect()->route('dashboard')
            ->with('success', 'You have joined ' . $organization->name . '!');
    }

    /**
     * Revoke invitation
     */
    public function revoke(Organization $organization, OrganizationInvitation $invitation)
    {
        $this->authorize('manage', $organization);

        if ($invitation->organization_id !== $organization->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }
}
