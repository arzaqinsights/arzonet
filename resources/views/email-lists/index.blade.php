@extends('layouts.app')
@section('title', 'Audience Manager')
@section('heading', 'Audience')

@section('header-actions')
    <a href="{{ route('admin.email-lists.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Contacts
    </a>
@endsection

@section('content')
    <div class="space-y-4 animate-slide-up">
        {{-- ── Global Audience Analytics ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Total Audience</p>
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['total']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-surface-800 h-full w-full"></div>
                </div>
            </div>
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Subscribed</p>
                <h3 class="text-2xl font-black text-emerald-600" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['subscribed']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full" style="width: {{ $globalStats['total'] > 0 ? ($globalStats['subscribed'] / $globalStats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Unsubscribed</p>
                <h3 class="text-2xl font-black text-amber-600" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['unsubscribed']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-amber-500 h-full" style="width: {{ $globalStats['total'] > 0 ? ($globalStats['unsubscribed'] / $globalStats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Bounced</p>
                <h3 class="text-2xl font-black text-red-500" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['bounced']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-red-500 h-full" style="width: {{ $globalStats['total'] > 0 ? ($globalStats['bounced'] / $globalStats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Invalid</p>
                <h3 class="text-2xl font-black text-red-400" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['invalid']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-red-400 h-full" style="width: {{ $globalStats['total'] > 0 ? ($globalStats['invalid'] / $globalStats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Duplicates</p>
                <h3 class="text-2xl font-black text-surface-400" style="font-family:'Outfit',sans-serif;">{{ number_format($globalStats['duplicate']) }}</h3>
                <div class="mt-2 w-full bg-gray-50 h-1 rounded-full overflow-hidden">
                    <div class="bg-surface-300 h-full" style="width: {{ $globalStats['total'] > 0 ? ($globalStats['duplicate'] / $globalStats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        {{-- ── Audience Lists Table ── --}}
        <div class="bg-white border rounded-sm overflow-hidden">
            <div class="p-5 border-b border-color flex items-center justify-between">
                <h4 class="text-sm font-black text-surface-900 uppercase tracking-[0.2em]">Contact Repositories</h4>
                <div class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">{{ $lists->total() }} Lists Found</div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-color">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">List Identity</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Total</th>
                            <th class="px-6 py-4 text-[10px] font-black text-emerald-600 uppercase tracking-widest text-center">Valid</th>
                            <th class="px-6 py-4 text-[10px] font-black text-red-500 uppercase tracking-widest text-center">Invalid</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($lists as $list)
                            <tr class="group hover:bg-gray-50 border-b border-color transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.email-lists.show', $list) }}" class="text-sm font-bold text-surface-900 hover:text-brand transition-colors leading-tight">{{ $list->name }}</a>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[9px] font-bold text-surface-400 uppercase tracking-tighter truncate max-w-[120px]" title="{{ $list->original_filename }}">{{ $list->original_filename ?: 'Manual Entry' }}</span>
                                            <div class="w-0.5 h-0.5 rounded-full bg-surface-200"></div>
                                            <span class="text-[9px] font-bold text-surface-400 uppercase tracking-tighter">{{ $list->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-surface-900">{{ number_format($list->total_records) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-emerald-600">{{ number_format($list->valid_count) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-red-600">{{ number_format($list->invalid_count) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php 
                                                                    $cls = match ($list->status) {
                                            'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                            'processing' => 'bg-amber-50 text-amber-600 border-amber-100 animate-pulse',
                                            'failed' => 'bg-red-50 text-red-600 border-red-100',
                                            default => 'bg-gray-50 text-gray-400 border-gray-200'
                                        }; 
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-sm text-[8px] font-black uppercase tracking-widest border {{ $cls }}">
                                        {{ $list->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 transition-opacity duration-200">
                                        <a href="{{ route('admin.email-lists.show', $list) }}" class="p-2 text-surface-400 hover:text-brand hover:bg-brand/5 rounded-sm transition-all" title="Manage Contacts">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <div class="h-4 w-px bg-gray-100"></div>
                                        <form action="{{ route('admin.email-lists.destroy', $list) }}" method="POST" onsubmit="return confirm('Delete this list? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-sm transition-all" title="Delete List">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-24 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                        <h5 class="text-surface-900 font-bold">No Audience Lists</h5>
                                        <p class="text-xs text-surface-500 mt-1 mb-6">Start building your contact database by importing your first list.</p>
                                        <a href="{{ route('admin.email-lists.create') }}" class="btn btn-primary px-8">Import Contacts</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($lists->hasPages())
                <div class="px-6 py-4 border-t border-gray-50 bg-gray-50/30">
                    {{ $lists->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
