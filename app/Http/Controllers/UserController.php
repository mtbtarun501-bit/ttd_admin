<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all users, you might want to paginate this in a real app
        $users = User::with('roles')->orderBy('created_at', 'desc')->get();
        $roles = Role::all();
        
        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string',
        ]);

        if ($request->role === 'none') {
            // Revoke all roles
            $user->syncRoles([]);
        } else {
            // Ensure the role actually exists in the database first to prevent RoleDoesNotExist exceptions
            $role = Role::firstOrCreate(['name' => $request->role]);
            
            // Assign new role, replacing old ones
            $user->syncRoles([$role]);
        }

        return redirect()->route('users.index')->with('success', 'User role updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Don't allow Super Admin to delete themselves
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }
        
        $user->delete();
        
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
