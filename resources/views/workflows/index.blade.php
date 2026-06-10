@extends('layouts.app')
@section('title', 'Workflows & Automation')
@section('heading', 'Workflows & Automation')

@section('content')
<div class="space-y-8 animate-slide-up">
    {{-- Header Banner / Action --}}
    <div class="flex justify-between items-center bg-white p-6 rounded-md border border-surface-200/80 shadow-sm">
        <div>
            <h3 class="text-lg font-bold text-surface-900 leading-tight">Visual Automation Workflows</h3>
            <p class="text-sm text-surface-500 mt-1">Design multi-step contact customer journeys, wait delays, autoresponders, and conditional tag updates.</p>
        </div>
        <a href="{{ route('admin.workflows.create') }}" class="btn btn-primary rounded-md px-6 py-3 text-xs font-black uppercase tracking-widest flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Workflow
        </a>
    </div>

    {{-- Workflows List Table --}}
    <div class="glass-card overflow-hidden rounded-md">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-[10px] uppercase tracking-[0.2em]">Active Workflows Registry</h4>
            <span class="text-[10px] font-bold text-surface-400">{{ $workflows->total() }} Journeys Created</span>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th class="!pl-8">Workflow Name</th>
                    <th>Trigger</th>
                    <th>Steps Count</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th class="text-right !pr-8">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workflows as $workflow)
                <tr class="group">
                    <td class="!pl-8">
                        <div class="flex items-center gap-4 py-2">
                            <div class="w-10 h-10 rounded-md bg-brand/5 border border-brand/10 text-brand flex items-center justify-center font-black text-sm shadow-sm">
                                {{ strtoupper(substr($workflow->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-bold text-surface-900 leading-tight">{{ $workflow->name }}</p>
                                <p class="text-xs text-surface-400 mt-0.5">{{ Str::limit($workflow->description, 50) }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700">
                            @if($workflow->trigger_type === 'list_signup')
                                List Signup
                            @elseif($workflow->trigger_type === 'topic_subscribe')
                                Subscribed to Topic ({{ $workflow->trigger_value }})
                            @elseif($workflow->trigger_type === 'tag_added')
                                Tag Added: "{{ $workflow->trigger_value }}"
                            @else
                                {{ $workflow->trigger_type }}
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="text-xs font-semibold text-surface-700">{{ count($workflow->steps) }} Steps</span>
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $workflow->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-surface-100 text-surface-700' }}">
                            {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <span class="text-xs text-surface-500 font-medium">{{ $workflow->created_at->format('M d, Y') }}</span>
                    </td>
                    <td class="!pr-8">
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <a href="{{ route('admin.workflows.edit', $workflow) }}" class="p-2 text-primary-650 hover:bg-primary-50 rounded-md transition-colors" title="Edit Workflow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>

                            <form action="{{ route('admin.workflows.destroy', $workflow) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this workflow? Active runs will be terminated.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-surface-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete Workflow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-24 opacity-55">
                        <p class="text-sm italic">No workflows created for this workspace list yet.</p>
                        <a href="{{ route('admin.workflows.create') }}" class="btn btn-secondary text-xs uppercase tracking-widest mt-4 inline-block px-6 py-3 rounded-md font-bold">
                            Create First Journey
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($workflows->hasPages())
        <div class="p-4 border-t border-surface-100">
            {{ $workflows->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
