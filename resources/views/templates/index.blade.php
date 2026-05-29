@extends('layouts.app')
@section('title', 'Templates')
@section('heading', 'Email Templates')

@section('header-actions')
    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary rounded-md px-6 shadow-lg shadow-primary-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Template
    </a>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up">
    @if($templates->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($templates as $template)
        <div class="glass-card group overflow-hidden border border-surface-200 hover:border-primary-400 hover:shadow-2xl transition-all duration-500">
            {{-- Preview Area --}}
            <div class="relative h-64 bg-surface-50 border-b border-surface-100 overflow-hidden">
                {{-- Mac Style Top Bar --}}
                <div class="absolute top-0 left-0 right-0 h-6 bg-surface-100/80 backdrop-blur-md flex items-center px-3 gap-1.5 z-10 border-b border-surface-200/50">
                    <div class="w-2 h-2 rounded-full bg-rose-400"></div>
                    <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                    <div class="ml-2 text-[8px] font-black text-surface-400 uppercase tracking-widest truncate max-w-[150px]">{{ $template->name }}</div>
                </div>

                {{-- Live Iframe Preview (ZOOMED IN) --}}
                <div class="absolute inset-0 pt-12 scale-[0.5] origin-top-left w-[200%] h-[200%] pointer-events-none select-none opacity-90 group-hover:opacity-100 transition-opacity">
                    <iframe src="{{ route('admin.templates.preview', $template) }}?raw=1" class="w-full h-full border-none bg-white"></iframe>
                </div>

                {{-- Hover Overlay --}}
                <div class="absolute inset-0 bg-primary-900/5 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-3 z-20">
                    <a href="{{ route('admin.templates.preview', $template) }}" target="_blank" class="bg-white text-surface-900 px-4 py-2 rounded-md font-black text-[10px] uppercase tracking-widest shadow-xl hover:scale-105 transition-transform">
                        Fullscreen
                    </a>
                    <form action="{{ route('admin.templates.clone', $template) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md font-black text-[10px] uppercase tracking-widest shadow-xl hover:scale-105 transition-transform shadow-indigo-200 cursor-pointer">
                            Duplicate
                        </button>
                    </form>
                    <a href="{{ route('admin.templates.edit', $template) }}" class="bg-brand text-white px-4 py-2 rounded-md font-black text-[10px] uppercase tracking-widest shadow-xl hover:scale-105 transition-transform shadow-primary-200">
                        Edit Design
                    </a>
                </div>
            </div>

            {{-- Info Area --}}
            <div class="p-6 relative bg-white">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-surface-900 font-black text-base uppercase tracking-tight">{{ $template->name }}</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.templates.clone', $template) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-surface-300 hover:text-primary-500 transition-colors" title="Duplicate Template">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                            </button>
                        </form>
                        <form action="{{ route('admin.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template permanently?')">
                            @csrf @method('DELETE')
                            <button class="text-surface-300 hover:text-rose-500 transition-colors" title="Delete Template">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 text-[9px] font-black text-surface-400 uppercase tracking-tighter pt-4 border-t border-surface-50">
                    <span class="flex items-center gap-1.5">
                        <div class="w-1 h-1 rounded-full bg-emerald-500"></div>
                        Ready to send
                    </span>
                    <span class="ml-auto italic">Updated {{ $template->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $templates->links() }}</div>
    @else
    <div class="glass-card p-20 text-center flex flex-col items-center">
        <div class="w-24 h-24 bg-primary-50 rounded-full flex items-center justify-center mb-8 text-primary-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
        </div>
        <h3 class="text-3xl font-black text-surface-900 uppercase tracking-tight mb-3">No Master Assets Found</h3>
        <p class="text-surface-500 mb-10 max-w-md mx-auto font-medium">Your creative library is currently empty. Start building high-converting email templates for your next mission.</p>
        <a href="{{ route('admin.templates.create') }}" class="btn btn-primary px-12 py-4 rounded-md shadow-xl shadow-primary-100">Initialize First Template</a>
    </div>
    @endif
</div>
@endsection
