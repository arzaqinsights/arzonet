@extends('layouts.app')
@section('title', 'Segments')
@section('heading', 'Segment Builder')

@section('header-actions')
<div class="flex items-center gap-2">
    <form action="{{ route('admin.segments.refresh-counts') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="px-5 py-3 flex items-center rounded-sm bg-surface-200 hover:bg-surface-300 text-surface-800 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3.375"/></svg>
            Refresh Counts
        </button>
    </form>
    <a href="{{ route('admin.segments.create') }}"
        class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
        New Segment
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6 animate-slide-up">
    @if($segments->isEmpty())
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-indigo-50 flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Segments Yet</h3>
            <p class="text-surface-500 text-sm mb-6">Build dynamic segments to target contacts by rules.</p>
            <a href="{{ route('admin.segments.create') }}" class="btn btn-primary">Create Segment</a>
        </div>
    @else
        <div class="glass-card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Segment Name</th>
                        <th>Description</th>
                        <th class="text-center">Rules</th>
                        <th class="text-center">Matching Contacts</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($segments as $segment)
                        <tr class="group">
                            <td>
                                <a href="{{ route('admin.segments.show', $segment) }}" class="font-bold text-brand hover:underline">
                                    {{ $segment->name }}
                                </a>
                            </td>
                            <td class="text-surface-500 text-sm">{{ Str::limit($segment->description, 60) }}</td>
                            <td class="text-center">
                                <span class="badge badge-neutral">{{ count($segment->rules ?? []) }} {{ Str::plural('rule', count($segment->rules ?? [])) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="text-lg font-black text-surface-900">{{ number_format($segment->contact_count) }}</span>
                                @if($segment->last_refreshed_at)
                                    <div class="text-[10px] text-surface-400 font-medium">Refreshed {{ $segment->last_refreshed_at->diffForHumans() }}</div>
                                @else
                                    <div class="text-[10px] text-surface-400 font-medium italic text-surface-300">Never refreshed</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('admin.segments.show', $segment) }}" class="btn btn-sm btn-ghost">View</a>
                                    <a href="{{ route('admin.segments.edit', $segment) }}" class="btn btn-sm btn-ghost">Edit</a>
                                    <form action="{{ route('admin.segments.destroy', $segment) }}" method="POST" onsubmit="return confirm('Delete this segment?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost text-red-500 hover:text-red-700 cursor-pointer">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
