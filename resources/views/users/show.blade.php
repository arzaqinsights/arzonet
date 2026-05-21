@extends('layouts.app')

@section('title', 'Team Member Details')
@section('heading', 'Team Member Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header Navigation --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i>
            Back to Team
        </a>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-user-pen mr-1"></i>
                Edit Details / Permissions
            </a>
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this team member? This will revoke all their permissions and log them out.')" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-user-minus mr-1"></i>
                    Remove Member
                </button>
            </form>
        </div>
    </div>

    {{-- Profile Overview Card --}}
    <div class="glass-card p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-sm bg-brand/10 text-brand flex items-center justify-center text-3xl font-black shadow-sm">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="space-y-1">
                    <h2 class="text-xl font-black text-surface-900 leading-tight">{{ $user->name }}</h2>
                    <p class="text-sm font-semibold text-surface-500">{{ $user->email }}</p>
                    <div class="flex items-center gap-2 pt-1">
                        <span class="badge badge-neutral capitalize font-bold">
                            {{ $user->role }}
                        </span>
                        <span class="badge badge-success">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 animate-ping mr-1"></span>
                            Active Status
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="border-t md:border-t-0 md:border-l border-surface-200 pt-4 md:pt-0 md:pl-8 space-y-2">
                <div class="flex items-center justify-between gap-4 text-xs">
                    <span class="font-bold text-surface-500">Seat Created:</span>
                    <span class="font-black text-surface-850">{{ $user->created_at->format('M d, Y, h:i A') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 text-xs">
                    <span class="font-bold text-surface-500">Access Scope:</span>
                    <span class="badge badge-brand font-black">
                        {{ is_array($user->permissions) ? count($user->permissions) : 0 }} Actions Permitted
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions Visualized Card --}}
    <div class="glass-card p-6 space-y-4">
        <h3 class="text-sm font-bold text-surface-900 border-b border-surface-100 pb-2">
            <i class="fa-solid fa-shield-halved text-brand mr-2"></i>
            Assigned Permissions Summary
        </h3>

        <div class="space-y-6 pt-2">
            @foreach($permissionGroups as $groupKey => $group)
                <div class="border border-surface-150 rounded-sm p-4 bg-surface-50/30">
                    <h4 class="font-black text-sm flex items-center gap-2 text-surface-950 mb-3 border-b border-surface-200 pb-1.5">
                        <i class="fa-solid {{ $group['icon'] }} text-brand text-xs"></i>
                        {{ $group['title'] }}
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($group['permissions'] as $permKey => $perm)
                            @php
                                $hasPerm = $user->hasPermission($permKey);
                            @endphp
                            <div class="flex items-start gap-3 p-3 border rounded-sm transition-all {{ $hasPerm ? 'border-green-200 bg-green-50/20' : 'border-surface-200 bg-surface-100/10' }}">
                                <div class="mt-0.5 shrink-0">
                                    @if($hasPerm)
                                        <div class="w-5 h-5 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-black">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @else
                                        <div class="w-5 h-5 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-[10px] font-black">
                                            <i class="fa-solid fa-xmark"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-xs font-bold {{ $hasPerm ? 'text-green-900' : 'text-surface-600 line-through' }}">
                                        {{ $perm['label'] }}
                                    </p>
                                    <p class="text-[10px] {{ $hasPerm ? 'text-green-800/80' : 'text-surface-400' }} font-medium leading-normal">
                                        {{ $perm['description'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
