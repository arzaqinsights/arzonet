@extends('layouts.app')
@section('title', 'Templates')
@section('heading', 'Email Templates')

@section('header-actions')
    <a href="{{ route('templates.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Template
    </a>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up">
    @if($templates->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($templates as $template)
        <div class="glass-card group overflow-hidden border border-surface-200 hover:border-primary-300 hover:shadow-xl hover:shadow-primary-100/50 transition-all duration-300">
            <div class="h-48 bg-surface-50 flex items-center justify-center border-b border-surface-100 relative group-hover:bg-primary-50/30 transition-colors">
                <svg class="w-16 h-16 text-surface-200 group-hover:text-primary-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                
                <div class="absolute inset-0 bg-white/60 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <a href="{{ route('templates.preview', $template) }}" class="btn btn-primary btn-sm px-4">Preview</a>
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-ghost btn-sm bg-white shadow-sm px-4 border border-surface-200">Edit</a>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-surface-900 font-bold text-lg mb-1">{{ $template->name }}</h3>
                <p class="text-xs font-bold text-primary-600 uppercase tracking-widest mb-4 truncate">{{ $template->subject }}</p>
                
                <div class="flex items-center justify-between pt-4 border-t border-surface-50">
                    <span class="text-[10px] font-bold text-surface-400 uppercase tracking-tighter">Last Modified: {{ $template->updated_at->format('M d, Y') }}</span>
                    <form action="{{ route('templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Permanently delete this template?')">
                        @csrf @method('DELETE')
                        <button class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $templates->links() }}</div>
    @else
    <div class="glass-card p-20 text-center">
        <div class="w-20 h-20 bg-surface-50 rounded-full flex items-center justify-center mx-auto mb-6 text-surface-300">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
        </div>
        <h3 class="text-2xl font-bold text-surface-900 mb-2">No templates yet</h3>
        <p class="text-surface-500 mb-8 max-w-sm mx-auto">Design reusable email templates using our advanced drag-and-drop builder.</p>
        <a href="{{ route('templates.create') }}" class="btn btn-primary px-8">Build Your First Template</a>
    </div>
    @endif
</div>
@endsection
