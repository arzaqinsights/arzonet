@extends('layouts.app')
@section('title', 'Subscription Topics')
@section('heading', 'Subscription Topics')

@section('content')
<div class="space-y-8 animate-slide-up">

    {{-- Create Topic Card --}}
    <div class="glass-card rounded-md">
        <div class="p-8">
            <div>
                <h3 class="text-xl font-bold text-surface-900 tracking-tight">Create Subscription Topic</h3>
                <p class="text-sm text-surface-500 mt-1">Define marketing, transactional, or newsletter categories for recipient preferences.</p>
            </div>

            <form action="{{ route('admin.subscription-topics.store') }}" method="POST" class="space-y-8 mt-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Topic Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input rounded-md !bg-surface-50 @error('name') border-rose-500 @else border-surface-200 @enderror py-3 text-sm font-semibold" placeholder="e.g. Weekly Newsletter" required>
                        @error('name') <p class="text-[10px] font-bold text-rose-500 uppercase tracking-tight">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Description (Visible to subscribers)</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-input rounded-md !bg-surface-50 @error('description') border-rose-500 @else border-surface-200 @enderror py-3 text-sm font-semibold" placeholder="e.g. Receive our weekly digest of curated content and updates." required>
                        @error('description') <p class="text-[10px] font-bold text-rose-500 uppercase tracking-tight">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary rounded-md px-12 py-4 shadow-xl shadow-primary-200 text-sm font-black uppercase tracking-widest">
                        Create Topic
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Topics Table --}}
    <div class="glass-card overflow-hidden rounded-md">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-[10px] uppercase tracking-[0.2em]">Active Topics Registry</h4>
            <span class="text-[10px] font-bold text-surface-400">{{ $topics->total() }} Defined Topics</span>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th class="!pl-8">Topic Name</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th class="text-right !pr-8">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topics as $topic)
                <tr class="group">
                    <td class="!pl-8">
                        <div class="flex items-center gap-4 py-2">
                            <div class="w-10 h-10 rounded-md bg-brand/5 border border-brand/10 text-brand flex items-center justify-center font-black text-sm shadow-sm">
                                {{ strtoupper(substr($topic->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-bold text-surface-900 leading-tight">{{ $topic->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-xs text-surface-600 font-medium">{{ Str::limit($topic->description, 70) }}</span>
                    </td>
                    <td>
                        <span class="text-xs text-surface-500 font-medium">{{ $topic->created_at->format('M d, Y') }}</span>
                    </td>
                    <td class="!pr-8">
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <a href="{{ route('admin.subscription-topics.edit', $topic) }}" class="p-2 text-primary-650 hover:bg-primary-50 rounded-md transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>

                            <form action="{{ route('admin.subscription-topics.destroy', $topic) }}" method="POST" onsubmit="return confirm('WARNING: Deleting this topic will remove it from all campaigns. Are you sure you want to delete this topic?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-surface-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-24 opacity-55">
                        <p class="text-sm italic">No subscription topics registered yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($topics->hasPages())
        <div class="p-4 border-t border-surface-100">
            {{ $topics->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
