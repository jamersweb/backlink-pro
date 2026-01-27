<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TeamInvitationController extends Controller
{
    /**
     * Create invitation
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $team = $user->primaryTeam();
        
        if (!$team) {
            abort(404, 'Team not found');
        }

        // Check if user can invite (owner or admin)
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$member || !in_array($member->role, [Team::ROLE_OWNER, Team::ROLE_ADMIN])) {
            abort(403, 'Only team owners and admins can invite members');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        // Check if user is already a member
        $existingUser = \App\Models\User::where('email', $validated['email'])->first();
        if ($existingUser) {
            $isMember = TeamMember::where('team_id', $team->id)
                ->where('user_id', $existingUser->id)
                ->exists();
            if ($isMember) {
                return back()->withErrors(['email' => 'User is already a team member']);
            }
        }

        // Check for existing pending invitation
        $existingInvite = TeamInvitation::where('team_id', $team->id)
            ->where('email', $validated['email'])
            ->where('status', TeamInvitation::STATUS_PENDING)
            ->first();
        
        if ($existingInvite && !$existingInvite->isExpired()) {
            return back()->withErrors(['email' => 'Invitation already sent to this email']);
        }

        // Create invitation
        $token = hash('sha256', random_bytes(32));
        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => $token,
            'status' => TeamInvitation::STATUS_PENDING,
            'invited_by_user_id' => $user->id,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        return back()->with('success', 'Invitation sent');
    }

    /**
     * Accept invitation
     */
    public function accept(Request $request, string $token)
    {
        $invitation = TeamInvitation::where('token', $token)
            ->where('status', TeamInvitation::STATUS_PENDING)
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => TeamInvitation::STATUS_EXPIRED]);
            return redirect()->route('login')->withErrors(['invitation' => 'Invitation has expired']);
        }

        // User must be logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('invitation_token', $token);
        }

        $user = Auth::user();

        // Check if email matches
        if ($user->email !== $invitation->email) {
            return back()->withErrors(['email' => 'Invitation email does not match your account']);
        }

        // Check if already a member
        $isMember = TeamMember::where('team_id', $invitation->team_id)
            ->where('user_id', $user->id)
            ->exists();
        
        if ($isMember) {
            $invitation->update(['status' => TeamInvitation::STATUS_ACCEPTED]);
            return redirect()->route('team.show')->with('info', 'You are already a member of this team');
        }

        // Add user to team
        TeamMember::create([
            'team_id' => $invitation->team_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        $invitation->update(['status' => TeamInvitation::STATUS_ACCEPTED]);

        return redirect()->route('team.show')->with('success', 'You have joined the team');
    }

    /**
     * Revoke invitation
     */
    public function revoke($id)
    {
        $user = Auth::user();
        $invitation = TeamInvitation::findOrFail($id);
        $team = $invitation->team;

        // Check permission
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$member || !in_array($member->role, [Team::ROLE_OWNER, Team::ROLE_ADMIN])) {
            abort(403);
        }

        $invitation->update(['status' => TeamInvitation::STATUS_REVOKED]);

        return back()->with('success', 'Invitation revoked');
    }
}
