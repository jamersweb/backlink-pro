<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Show team management page
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user's primary team (or create if doesn't exist)
        $team = $user->primaryTeam();
        if (!$team) {
            $team = Team::create([
                'owner_user_id' => $user->id,
                'name' => "{$user->name} Workspace",
            ]);
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => Team::ROLE_OWNER,
            ]);
        }

        $team->load(['members.user', 'invitations' => function($q) {
            $q->where('status', 'pending')->latest();
        }]);

        return Inertia::render('Team/Show', [
            'team' => $team,
            'isOwner' => $team->owner_user_id === $user->id,
        ]);
    }

    /**
     * Update team name
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $team = $user->primaryTeam();
        
        if (!$team || $team->owner_user_id !== $user->id) {
            abort(403, 'Only team owner can update team name');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->update(['name' => $validated['name']]);

        return back()->with('success', 'Team name updated');
    }
}
