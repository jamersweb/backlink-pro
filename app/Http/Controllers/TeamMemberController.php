<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    /**
     * Update member role
     */
    public function updateRole(Request $request, $id)
    {
        $user = Auth::user();
        $member = TeamMember::with('team')->findOrFail($id);
        $team = $member->team;

        // Only owner can change roles
        if ($team->owner_user_id !== $user->id) {
            abort(403, 'Only team owner can change member roles');
        }

        // Cannot change owner role
        if ($member->isOwner()) {
            abort(403, 'Cannot change owner role');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,editor,viewer',
        ]);

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Member role updated');
    }

    /**
     * Remove member
     */
    public function remove($id)
    {
        $user = Auth::user();
        $member = TeamMember::with('team')->findOrFail($id);
        $team = $member->team;

        // Only owner or admin can remove members
        $requesterMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$requesterMember || !$requesterMember->isAdminOrOwner()) {
            abort(403);
        }

        // Cannot remove owner
        if ($member->isOwner()) {
            abort(403, 'Cannot remove team owner');
        }

        $member->delete();

        return back()->with('success', 'Member removed');
    }
}
