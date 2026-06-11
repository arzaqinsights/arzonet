@extends('layouts.app')
@section('title', 'Edit Sequence')
@section('heading', 'Edit Drip Sequence')

@section('content')
<div class="max-w-xl mx-auto animate-slide-up">
    <div class="mb-4">
        <a href="{{ route('admin.sequences.index') }}" class="inline-flex items-center text-xs font-black uppercase tracking-widest text-surface-400 hover:text-surface-700 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Sequences
        </a>
    </div>

    <div class="glass-card rounded-md">
        <div class="p-6 border-b border-surface-100 bg-surface-50/50">
            <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Rename Sequence</h3>
            <p class="text-xs text-surface-500 mt-1">Update the name of this automated drip campaign.</p>
        </div>
        
        <form action="{{ route('admin.sequences.update', $sequence) }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div class="space-y-2">
                <label class="text-xs font-black text-surface-450 uppercase tracking-widest">Sequence Name</label>
                <input type="text" name="name" value="{{ old('name', $sequence->name) }}" required class="form-input rounded-md bg-surface-50 border-surface-200 py-3 text-sm font-semibold w-full focus:border-brand focus:ring-0" placeholder="e.g. 3-Part Lead Welcome Journey">
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-100">
                <a href="{{ route('admin.sequences.index') }}" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary rounded-md px-6 py-2.5 text-xs font-black uppercase tracking-widest">
                    Update Sequence
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
