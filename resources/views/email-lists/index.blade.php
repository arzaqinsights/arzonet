@extends('layouts.app')
@section('title', 'Audience Manager')
@section('heading', 'Audience')

@section('header-actions')
    <a href="{{ route('email-lists.create') }}" class="btn btn-primary shadow-xl shadow-primary-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Contacts
    </a>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up">
    {{-- ── Global Audience Stats ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-[0.2em] mb-1">Total Audience</p>
            <h2 class="text-3xl font-black text-surface-900">{{ number_format($lists->sum('total_records')) }}</h2>
            <div class="flex items-center gap-1.5 mt-3 text-[10px] font-bold text-emerald-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Healthy Growth
            </div>
        </div>
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-[0.2em] mb-1">Subscribed</p>
            <h2 class="text-3xl font-black text-primary-600">{{ number_format($lists->sum('valid_count')) }}</h2>
            <p class="text-[10px] text-surface-500 mt-3 font-medium italic">Verified Contacts</p>
        </div>
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-[0.2em] mb-1">Audience Health</p>
            @php 
                $total = $lists->sum('total_records');
                $valid = $lists->sum('valid_count');
                $health = $total > 0 ? round(($valid / $total) * 100) : 0;
            @endphp
            <h2 class="text-3xl font-black {{ $health > 80 ? 'text-emerald-600' : 'text-amber-500' }}">{{ $health }}%</h2>
            <div class="w-full h-1 bg-surface-100 rounded-full mt-4 overflow-hidden">
                <div class="h-full bg-emerald-500" style="width: {{ $health }}%"></div>
            </div>
        </div>
    </div>

    {{-- ── Audience List ── --}}
    @if($lists->count())
    <div class="space-y-2">
        <div class="flex items-center justify-between px-6 py-2 bg-surface-50 rounded-t-xl border-x border-t border-surface-200">
            <div class="grid grid-cols-12 gap-4 w-full">
                <div class="col-span-4 text-[9px] font-black text-surface-400 uppercase tracking-widest">List Identity</div>
                <div class="col-span-5 grid grid-cols-4 gap-4">
                    <div class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Total</div>
                    <div class="text-[9px] font-black text-surface-400 uppercase tracking-widest text-emerald-600">Valid</div>
                    <div class="text-[9px] font-black text-surface-400 uppercase tracking-widest text-red-500">Errors</div>
                    <div class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Status</div>
                </div>
                <div class="col-span-3 text-right text-[9px] font-black text-surface-400 uppercase tracking-widest">Action</div>
            </div>
        </div>

        <div class="space-y-1">
            @foreach($lists as $list)
            <div class="group relative bg-white border border-surface-200 p-4 hover:bg-surface-50/50 hover:border-primary-400 transition-all duration-200">
                <div class="grid grid-cols-12 gap-4 items-center">
                    {{-- Identity --}}
                    <div class="col-span-4">
                        <a href="{{ route('email-lists.show', $list) }}" class="text-sm font-black text-surface-900 hover:text-primary-600 block leading-tight">{{ $list->name }}</a>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[9px] font-bold text-surface-400 uppercase truncate max-w-[150px]">{{ $list->original_filename ?: 'Manual Entry' }}</span>
                            <div class="w-0.5 h-0.5 rounded-full bg-surface-200"></div>
                            <span class="text-[9px] font-medium text-surface-400">{{ $list->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>

                    {{-- Quick Stats Grid --}}
                    <div class="col-span-5 grid grid-cols-4 gap-4">
                        <div class="text-sm font-black text-surface-900">{{ number_format($list->total_records) }}</div>
                        <div class="text-sm font-black text-emerald-600">{{ number_format($list->valid_count) }}</div>
                        <div class="text-sm font-black text-red-600">{{ number_format($list->invalid_count) }}</div>
                        <div>
                            @php $cls = match($list->status) { 'completed' => 'bg-emerald-100 text-emerald-700', 'processing' => 'bg-amber-100 text-amber-700', 'failed' => 'bg-red-100 text-red-700', default => 'bg-surface-100 text-surface-600' }; @endphp
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter {{ $cls }}">{{ $list->status }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-3 flex items-center justify-end gap-3">
                        <a href="{{ route('email-lists.show', $list) }}" class="text-[10px] font-black text-primary-600 uppercase hover:underline">
                            Manage Audience
                        </a>
                        <div class="h-4 w-px bg-surface-200"></div>
                        <form action="{{ route('email-lists.destroy', $list) }}" method="POST" onsubmit="return confirm('Delete this list?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-surface-400 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $lists->links() }}</div>
    </div>
    @else
    <div class="glass-card p-24 text-center">
        <h3 class="text-2xl font-black text-surface-900 mb-3">Build your audience</h3>
        <p class="text-surface-500 mb-8 max-w-md mx-auto text-sm leading-relaxed">Your audience is where you store and manage your contacts. Let's add some people to get started.</p>
        <a href="{{ route('email-lists.create') }}" class="btn btn-primary px-10">Import Your Contacts</a>
    </div>
    @endif
</div>
@endsection
