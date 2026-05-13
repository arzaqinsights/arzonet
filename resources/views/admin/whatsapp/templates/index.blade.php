@extends('layouts.app')

@section('title', 'WhatsApp Templates')

@section('header-actions')
<div class="flex items-center gap-3">
    @if($accounts->count() > 0)
    <form action="{{ route('admin.whatsapp.templates.sync', $accounts->first()) }}" method="POST">
        @csrf
        <button type="submit" class="text-[10px] font-black uppercase tracking-widest px-6 py-3 border border-color rounded-sm hover:bg-surface-50 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-rotate"></i> Sync from Meta
        </button>
    </form>
    @endif
    <a href="{{ route('admin.whatsapp.templates.create') }}" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> New Template
    </a>
</div>
@endsection

@section('content')
<div class="space-y-4">
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-sm flex items-center gap-3 text-emerald-700 text-sm font-bold">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="p-4 bg-red-50 border border-red-100 rounded-sm flex items-center gap-3 text-red-700 text-sm font-bold">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
    </div>
    @endif

    @if($accounts->count() === 0)
    <div class="py-20 text-center bg-white border border-color rounded-sm">
        <i class="fa-brands fa-whatsapp text-4xl text-surface-200 mb-4 block"></i>
        <p class="text-sm font-black text-surface-900 uppercase tracking-widest">No WhatsApp Number Connected</p>
        <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-2">Connect a number first to manage templates.</p>
        <a href="{{ route('admin.whatsapp.accounts.index') }}" class="inline-block mt-4 bg-brand text-white text-[10px] font-black uppercase tracking-widest px-6 py-2.5 rounded-sm hover:bg-black transition-all">
            Go to Phone Numbers
        </a>
    </div>
    @else

    {{-- Stats --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Total Templates</p>
            <p class="text-3xl font-black text-surface-900 mt-1">{{ $templates->count() }}</p>
        </div>
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Approved</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ $templates->where('status', 'approved')->count() }}</p>
        </div>
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Pending</p>
            <p class="text-3xl font-black text-amber-500 mt-1">{{ $templates->whereIn('status', ['pending', 'in_appeal', 'pending_deletion'])->count() }}</p>
        </div>
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Rejected</p>
            <p class="text-3xl font-black text-red-500 mt-1">{{ $templates->where('status', 'rejected')->count() }}</p>
        </div>
    </div>

    {{-- Templates Table --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        <div class="border-b border-color px-6 py-4 bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">All Templates</h2>
        </div>

        @forelse($templates as $template)
        <div class="border-b border-color last:border-0 px-6 py-5 hover:bg-surface-50/30 transition-colors" 
             x-data="{ open: false }">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 flex-grow">
                    {{-- Icon --}}
                    <div class="w-9 h-9 rounded-sm flex-shrink-0 flex items-center justify-center
                        {{ $template->category === 'MARKETING' ? 'bg-purple-50 text-purple-600' : 
                           ($template->category === 'UTILITY' ? 'bg-blue-50 text-blue-600' : 'bg-green-50 text-green-600') }}">
                        <i class="fa-solid {{ $template->category === 'MARKETING' ? 'fa-bullhorn' : ($template->category === 'UTILITY' ? 'fa-gear' : 'fa-shield-halved') }} text-sm"></i>
                    </div>
                    {{-- Info --}}
                    <div class="flex-grow">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-sm font-black text-surface-900">{{ $template->name }}</h3>
                            {{-- Status Badge --}}
                            @php
                                $statusClass = match($template->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-100',
                                    'pending', 'in_appeal' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-surface-100 text-surface-500 border-color'
                                };
                            @endphp
                            <span class="border text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-sm {{ $statusClass }}">
                                {{ $template->status }}
                            </span>
                            <span class="text-[9px] font-black text-surface-300 uppercase tracking-widest">{{ $template->category }}</span>
                            <span class="text-[9px] font-black text-surface-300 uppercase tracking-widest">{{ strtoupper($template->language) }}</span>
                        </div>
                        <p class="text-[11px] text-surface-500 mt-1.5 line-clamp-2">{{ $template->body ?: 'No body text' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="open = !open" class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 border border-color rounded-sm hover:bg-surface-50 transition-colors text-surface-600">
                        <i class="fa-solid fa-eye mr-1"></i> Preview
                    </button>
                    @if($template->status === 'approved')
                    <span class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-sm border border-emerald-100">
                        <i class="fa-solid fa-check mr-1"></i> Ready to Use
                    </span>
                    @endif
                </div>
            </div>

            {{-- Preview Panel --}}
            <div x-show="open" x-cloak x-transition class="mt-4 ml-13 pl-4 border-l-2 border-brand/20">
                <div class="bg-surface-50 rounded-sm p-4 max-w-lg">
                    <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-3">Message Preview</p>
                    @if($template->components)
                        @foreach($template->components as $component)
                            @if($component['type'] === 'HEADER')
                            <div class="font-black text-surface-900 text-sm mb-2">
                                @if(($component['format'] ?? '') === 'TEXT'){{ $component['text'] ?? '' }}@else<span class="text-brand text-[10px] uppercase font-black">[{{ $component['format'] ?? 'MEDIA' }} HEADER]</span>@endif
                            </div>
                            @elseif($component['type'] === 'BODY')
                            <div class="text-sm text-surface-700 whitespace-pre-line">{{ $component['text'] ?? '' }}</div>
                            @elseif($component['type'] === 'FOOTER')
                            <div class="text-[10px] text-surface-400 mt-2 italic">{{ $component['text'] ?? '' }}</div>
                            @elseif($component['type'] === 'BUTTONS')
                            <div class="mt-3 space-y-1.5">
                                @foreach($component['buttons'] ?? [] as $btn)
                                <div class="text-center py-2 border border-brand/30 rounded-sm text-[10px] font-black text-brand uppercase tracking-widest">
                                    {{ $btn['text'] ?? '' }}
                                </div>
                                @endforeach
                            </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-sm text-surface-600">{{ $template->body }}</p>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="py-20 text-center">
            <i class="fa-solid fa-rectangle-list text-3xl text-surface-200 mb-4 block"></i>
            <p class="text-sm font-black text-surface-900 uppercase tracking-widest">No Templates Yet</p>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-2">Create a new template or sync from Meta.</p>
        </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
