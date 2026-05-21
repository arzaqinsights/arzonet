@extends('layouts.app')

@section('title', 'Team Members')
@section('heading', 'Team Members')

@section('header-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Team Member
    </a>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Limit & Stats Banner --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Team Members</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-surface-900">{{ $teamCount }}</span>
                    <span class="text-sm font-semibold text-surface-400">/ {{ $teamLimit }} limit</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-sm bg-brand/10 text-brand flex items-center justify-center text-xl">
                <i class="fa-solid fa-users-gear"></i>
            </div>
        </div>

        <div class="glass-card p-6 md:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Seat Utilization</p>
                <span class="text-xs font-bold text-brand">{{ $teamLimit > 0 ? round(($teamCount / $teamLimit) * 100) : 0 }}% used</span>
            </div>
            <div class="progress-container mb-2">
                <div class="progress-bar-fill" style="width: {{ $teamLimit > 0 ? ($teamCount / $teamLimit) * 100 : 0 }}%"></div>
            </div>
            <p class="text-[11px] text-surface-500">Need more seats for your team? <a href="{{ route('admin.billing.plans') }}" class="text-brand font-bold hover:underline">Upgrade your plan</a> to add more members.</p>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User Details</th>
                    <th>Permissions Allowed</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-sm bg-brand/10 text-brand flex items-center justify-center text-lg font-black shadow-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-surface-900 leading-tight">{{ $user->name }}</p>
                                    <p class="text-xs text-surface-500 font-semibold">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-brand">
                                <i class="fa-solid fa-key mr-1"></i>
                                {{ is_array($user->permissions) ? count($user->permissions) : 0 }} Active Permissions
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-neutral capitalize font-bold">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-ping mr-1"></span>
                                Active
                            </span>
                        </td>
                        <td>
                            <p class="text-sm text-surface-600 font-medium">{{ $user->created_at->format('M d, Y') }}</p>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="p-2 text-surface-500 hover:text-brand hover:bg-surface-50 rounded-sm transition-all" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="p-2 text-surface-500 hover:text-secondary hover:bg-surface-50 rounded-sm transition-all" title="Edit Permissions">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this team member? This will revoke all their permissions and log them out.')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-surface-500 hover:text-red-600 hover:bg-surface-50 rounded-sm transition-all" title="Delete Team Member">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-16 bg-white">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-16 h-16 rounded-full bg-surface-50 flex items-center justify-center text-surface-400">
                                    <i class="fa-solid fa-users fa-2x"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="font-bold text-surface-900">No Team Members Yet</p>
                                    <p class="text-sm text-surface-500 max-w-sm">Create granular team members, restrict their access to specific pages, campaigns, buttons or operations, and monitor their status.</p>
                                </div>
                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm mt-2">
                                    Add First Team Member
                                </a>
                            </div>
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
@endsection
