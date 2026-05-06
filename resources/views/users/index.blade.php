@extends('layouts.app')

@section('title', 'User Management')
@section('heading', 'User Management')

@section('header-actions')
    <button @click="$dispatch('open-modal', 'create-user')" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Team Member
    </button>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Users Table --}}
    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-md bg-surface-100 flex items-center justify-center text-primary-600 font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-surface-900">{{ $user->name }}</p>
                                    <p class="text-xs text-surface-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $user->role === 'admin' ? 'badge-info' : 'badge-neutral' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>
                            <p class="text-sm text-surface-600">{{ $user->created_at->format('M d, Y') }}</p>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button class="p-2 text-surface-400 hover:text-primary-600 transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-surface-400 hover:text-red-600 transition-colors" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12">
                            <p class="text-surface-500">No users found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-surface-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Create User Modal --}}
<div x-data="{ open: false }" 
     @open-modal.window="if ($event.detail === 'create-user') open = true"
     x-show="open" 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/50 backdrop-blur-sm"
     x-cloak>
    <div class="bg-white rounded-md shadow-xl w-full max-w-md overflow-hidden" @click.away="open = false">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-surface-900">Add New User</h3>
            <button @click="open = false" class="text-surface-400 hover:text-surface-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-input" placeholder="John Doe" required>
            </div>
            <div>
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="john@example.com" required>
            </div>
            <div>
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="team">Team Member</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div>
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input" required>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" @click="open = false" class="btn btn-ghost flex-1">Cancel</button>
                <button type="submit" class="btn btn-primary flex-1">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection
