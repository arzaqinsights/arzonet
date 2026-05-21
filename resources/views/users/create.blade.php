@extends('layouts.app')

@section('title', 'Add Team Member')
@section('heading', 'Add Team Member')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i>
            Back to Team
        </a>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Basic Info Card --}}
        <div class="glass-card p-6 space-y-4">
            <h3 class="text-sm font-bold text-surface-900 border-b border-surface-100 pb-2">
                <i class="fa-solid fa-id-card text-brand mr-2"></i>
                Account Details
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Rahul Sharma" required>
                    @error('name')
                        <p class="text-red-500 text-[11px] mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="e.g. rahul@company.com" required>
                    @error('email')
                        <p class="text-red-500 text-[11px] mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    @error('password')
                        <p class="text-red-500 text-[11px] mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required>
                </div>
            </div>
        </div>

        {{-- Permissions Card --}}
        <div class="glass-card p-6 space-y-4">
            <div class="border-b border-surface-100 pb-2 flex items-center justify-between">
                <h3 class="text-sm font-bold text-surface-900">
                    <i class="fa-solid fa-shield-halved text-brand mr-2"></i>
                    Permissions Scope
                </h3>
                <span class="text-[11px] font-bold text-surface-500 bg-surface-100 px-2 py-0.5 rounded-sm">Granular Controls</span>
            </div>
            
            <p class="text-xs text-surface-600 leading-normal">
                Check the permissions you want to assign to this team member. The member will only be able to see the sidebar menus, access routes, and click action buttons that are checked below.
            </p>

            <div class="space-y-6 pt-2">
                @foreach($permissionGroups as $groupKey => $group)
                    <div class="border border-surface-200 rounded-sm p-4" x-data="{
                        allChecked: false,
                        toggleAll() {
                            let checkboxes = this.$el.querySelectorAll('.perm-checkbox');
                            checkboxes.forEach(c => c.checked = this.allChecked);
                        },
                        checkState() {
                            let checkboxes = Array.from(this.$el.querySelectorAll('.perm-checkbox'));
                            this.allChecked = checkboxes.length > 0 && checkboxes.every(c => c.checked);
                        }
                    }" x-init="checkState()">
                        <div class="flex items-center justify-between mb-4 border-b border-surface-150 pb-2">
                            <span class="font-black text-sm flex items-center gap-2 text-surface-950">
                                <i class="fa-solid {{ $group['icon'] }} text-brand text-xs"></i>
                                {{ $group['title'] }}
                            </span>
                            <label class="flex items-center gap-2 text-xs font-black text-surface-600 cursor-pointer">
                                <input type="checkbox" x-model="allChecked" @change="toggleAll()" class="rounded-sm border-gray-300 focus:ring-brand text-brand">
                                Select All
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($group['permissions'] as $permKey => $perm)
                                <label class="flex items-start gap-3 p-3 border border-surface-150 rounded-sm hover:bg-surface-50 transition-all cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $permKey }}" @change="checkState()" class="perm-checkbox mt-0.5 rounded-sm border-gray-300 focus:ring-brand text-brand">
                                    <div class="space-y-0.5">
                                        <p class="text-xs font-bold text-surface-900">{{ $perm['label'] }}</p>
                                        <p class="text-[10px] text-surface-500 font-medium leading-normal">{{ $perm['description'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex gap-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary flex-1">Cancel</a>
            <button type="submit" class="btn btn-primary flex-1">Create Team Member</button>
        </div>
    </form>
</div>
@endsection
