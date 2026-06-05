@extends('layouts.app')
@section('title', 'Create Pipeline')
@section('heading', 'Create Pipeline')

@section('content')
<div class="max-w-xl mx-auto animate-slide-up">
    <div class="glass-card p-8">
        <div class="mb-8">
            <h2 class="text-xl font-black text-surface-900">New Deal Pipeline</h2>
            <p class="text-sm text-surface-500 mt-1">5 default stages (Lead, Contacted, Proposal Sent, Won, Lost) will be created automatically.</p>
        </div>

        <form action="{{ route('admin.pipelines.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label class="form-label">Pipeline Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Sales Q3 2026" required autofocus>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ showAdvanced: false }">
                    <label class="form-label">Sharing Visibility</label>
                    <div class="flex items-center gap-4 mt-2 mb-3">
                        <label class="inline-flex items-center text-xs font-semibold cursor-pointer">
                            <input type="radio" name="is_public" value="1" checked class="form-radio text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                            Public (All Team Members)
                        </label>
                        <label class="inline-flex items-center text-xs font-semibold cursor-pointer">
                            <input type="radio" name="is_public" value="0" class="form-radio text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                            Private (Creator Only)
                        </label>
                    </div>

                    <button type="button" @click="showAdvanced = true" class="text-brand hover:text-brand/80 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Advanced Settings
                    </button>

                    {{-- Advanced Settings Modal --}}
                    <div x-show="showAdvanced" x-cloak class="fixed inset-0 bg-black/30 z-[100] flex items-center justify-center p-4" @click.self="showAdvanced = false">
                        <div class="bg-white rounded-sm shadow-2xl w-full max-w-md animate-slide-up" @keydown.escape.window="showAdvanced = false">
                            <div class="p-6 border-b border-surface-100">
                                <h3 class="text-base font-black text-surface-900 uppercase tracking-widest text-left">Advanced Pipeline Settings</h3>
                                <p class="text-xs text-surface-500 mt-1 text-left">Configure permissions for other team members on this pipeline.</p>
                            </div>
                            <div class="p-6 space-y-4 text-left">
                                <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Allowed Actions for Team</p>
                                <div class="space-y-3">
                                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                                        <input type="checkbox" name="team_permissions[add_deal]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                        Add Deals
                                    </label>
                                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                                        <input type="checkbox" name="team_permissions[edit_deal]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                        Edit Deals
                                    </label>
                                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                                        <input type="checkbox" name="team_permissions[delete_deal]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                        Delete Deals
                                    </label>
                                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                                        <input type="checkbox" name="team_permissions[move_deal]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                        Move Deals (Drag & Drop)
                                    </label>
                                </div>
                            </div>
                            <div class="p-6 border-t border-surface-100 flex justify-end">
                                <button type="button" @click="showAdvanced = false" class="btn btn-primary btn-sm uppercase tracking-widest text-[9px]">Apply & Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-surface-50 p-4 rounded-sm border border-surface-100">
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3">Default Stages</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            ['Lead', '#6366f1'],
                            ['Contacted', '#3b82f6'],
                            ['Proposal Sent', '#f59e0b'],
                            ['Won', '#10b981'],
                            ['Lost', '#ef4444'],
                        ] as [$name, $color])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-sm text-xs font-bold text-surface-700 bg-white border border-surface-200">
                                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $color }}"></span>
                                {{ $name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-surface-100">
                    <a href="{{ route('admin.pipelines.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Pipeline
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
