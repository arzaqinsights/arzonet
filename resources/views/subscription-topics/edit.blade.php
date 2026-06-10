@extends('layouts.app')
@section('title', 'Edit Subscription Topic')
@section('heading', 'Edit Subscription Topic')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.subscription-topics.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i>
            Back to Topics
        </a>
    </div>

    <form action="{{ route('admin.subscription-topics.update', $subscriptionTopic) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="glass-card p-6 space-y-4">
            <h3 class="text-sm font-bold text-surface-900 border-b border-surface-100 pb-2">
                <i class="fa-solid fa-tag text-brand mr-2"></i>
                Topic Details
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Topic Name</label>
                    <input type="text" name="name" value="{{ old('name', $subscriptionTopic->name) }}" class="form-input rounded-md !bg-surface-50 @error('name') border-rose-500 @else border-surface-200 @enderror py-3 text-sm font-semibold" placeholder="e.g. Weekly Newsletter" required>
                    @error('name') <p class="text-[10px] font-bold text-rose-500 uppercase tracking-tight">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Description</label>
                    <input type="text" name="description" value="{{ old('description', $subscriptionTopic->description) }}" class="form-input rounded-md !bg-surface-50 @error('description') border-rose-500 @else border-surface-200 @enderror py-3 text-sm font-semibold" placeholder="e.g. Receive our weekly newsletter digest." required>
                    @error('description') <p class="text-[10px] font-bold text-rose-500 uppercase tracking-tight">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <a href="{{ route('admin.subscription-topics.index') }}" class="btn btn-secondary flex-1 py-3.5">Cancel</a>
            <button type="submit" class="btn btn-primary flex-1 py-3.5">Save Changes</button>
        </div>
    </form>
</div>
@endsection
