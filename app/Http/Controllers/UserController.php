<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Ensure only admin users can access team management.
     */
    protected function checkAdminAuthorization()
    {
        if (app()->has('team_user')) {
            abort(403, 'Unauthorized action. Only administrators can manage team members.');
        }
    }

    public function index()
    {
        $this->checkAdminAuthorization();

        $currentUser = auth()->user();
        
        // Count how many team members this admin has
        $teamCount = $currentUser->teamMembers()->count();
        $teamLimit = $currentUser->getTeamLimit();

        $users = $currentUser->teamMembers()->latest()->paginate(15);

        return view('users.index', compact('users', 'teamCount', 'teamLimit'));
    }

    public function create()
    {
        $this->checkAdminAuthorization();

        $currentUser = auth()->user();

        // Check if admin has CRM module
        if (!$currentUser->hasModule('crm')) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Your current subscription does not include the CRM & Team module. Please upgrade your plan.');
        }

        $permissionGroups = config('permissions.groups');

        return view('users.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $this->checkAdminAuthorization();

        $currentUser = auth()->user();

        // Validate
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'permissions' => ['nullable', 'array'],
        ]);

        // Check CRM module
        if (!$currentUser->hasModule('crm')) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Your current subscription does not include the CRM & Team module. Please upgrade your plan.');
        }

        // Check limit
        $currentTeamCount = $currentUser->teamMembers()->count();
        if ($currentTeamCount >= $currentUser->getTeamLimit()) {
            return redirect()->route('admin.users.index')
                ->with('error', "You have reached your limit of {$currentUser->getTeamLimit()} team members. Please upgrade your plan.");
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_TEAM,
            'parent_id' => $currentUser->id,
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Team member added successfully.');
    }

    public function show(User $user)
    {
        $this->checkAdminAuthorization();

        // Ensure this user belongs to the current admin
        if ($user->parent_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $permissionGroups = config('permissions.groups');

        return view('users.show', compact('user', 'permissionGroups'));
    }

    public function edit(User $user)
    {
        $this->checkAdminAuthorization();

        // Ensure this user belongs to the current admin
        if ($user->parent_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $permissionGroups = config('permissions.groups');

        return view('users.edit', compact('user', 'permissionGroups'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdminAuthorization();

        // Ensure this user belongs to the current admin
        if ($user->parent_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'permissions' => ['nullable', 'array'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'permissions' => $request->permissions ?? [],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Team member details updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->checkAdminAuthorization();

        // Ensure this user belongs to the current admin
        if ($user->parent_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Team member deleted successfully.');
    }
}
