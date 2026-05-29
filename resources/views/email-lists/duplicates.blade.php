@extends('layouts.app')

@section('title', 'Resolve Duplicates - ' . $emailList->name)
@section('heading', 'Resolve Cross-List Duplicates')

@section('header-actions')
    <a href="{{ route('admin.email-lists.show', $emailList) }}"
        class="inline-flex items-center gap-2 bg-white border border-gray-150 text-surface-600 hover:text-surface-900 px-4 py-2.5 rounded-sm text-[10px] font-black uppercase tracking-widest transition-all hover:scale-[1.02] active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to list
    </a>
@endsection

@section('content')
    <div class="space-y-6 animate-slide-up" x-data="{
        selectedIds: [],
        bulkAction: '',
        resolutions: {},
        toggleSelectAll(checked) {
            this.selectedIds = checked ? this.getPageIds() : [];
        },
        getPageIds() {
            const checkboxes = document.querySelectorAll('tbody input[type=&quot;checkbox&quot;]');
            return Array.from(checkboxes).map(cb => parseInt(cb.value));
        },
        applyBulk() {
            if (!this.bulkAction) {
                alert('Please select a resolution action first.');
                return;
            }
            if (this.selectedIds.length === 0) {
                alert('Please select at least one contact.');
                return;
            }
            this.selectedIds.forEach(id => {
                this.resolutions[id] = this.bulkAction;
            });
            this.bulkAction = '';
            this.selectedIds = [];
        }
    }">
        <div class="bg-amber-50 border border-amber-100 rounded-sm p-6 flex items-start gap-4">
            <div class="p-3 bg-amber-100 rounded-full text-amber-600 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-amber-800 font-black uppercase text-xs tracking-widest">About Cross-List Duplicates</h4>
                <p class="text-xs text-amber-700 font-medium mt-1 max-w-3xl leading-relaxed">
                    These contacts exist in other lists you own. They have been imported but marked with status
                    <strong>Cross-List Dup</strong> and are temporarily excluded from campaign targeting. Please choose a
                    resolution below to activate, move, or ignore them.
                </p>
            </div>
        </div>

        {{-- Bulk Action Toolbar --}}
        <div class="flex flex-wrap items-center justify-between bg-surface-900 p-4 rounded-sm text-white gap-4 shadow-md">
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-white/50">Bulk Resolution</span>
                <select x-model="bulkAction"
                    class="bg-surface-800 text-white border border-surface-700 text-xs font-bold rounded-sm p-2 focus:ring-0 focus:outline-none cursor-pointer">
                    <option value="">Choose Resolution...</option>
                    <option value="keep_old">Keep in old list only (Do not import)</option>
                    <option value="move_new">Move to new list (Remove from old, add here)</option>
                    <option value="keep_both">Keep in both lists (Add here, retain old)</option>
                </select>
                <button type="button" @click="applyBulk()"
                    class="bg-brand text-white px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-sm shadow hover:bg-brand/90 hover:scale-[1.02] active:scale-95 transition-all">
                    Apply to Selected (<span x-text="selectedIds.length"></span>)
                </button>
            </div>
            <div class="text-[9px] font-black text-white/40 uppercase tracking-[0.2em]">
                {{ $duplicates->total() }} duplicates pending resolution
            </div>
        </div>

        <form action="{{ route('admin.email-lists.duplicates.resolve', $emailList) }}" method="POST">
            @csrf
            <div class="bg-white border border-gray-150 rounded-sm overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-50 border-b border-gray-200 text-xs uppercase tracking-widest text-surface-500 font-black">
                                <th class="px-6 py-4 w-12 text-center">
                                    <input type="checkbox" @change="toggleSelectAll($event.target.checked)"
                                        :checked="selectedIds.length > 0"
                                        class="rounded-sm border-gray-300 text-brand focus:ring-brand focus:ring-offset-0 cursor-pointer">
                                </th>
                                <th class="px-6 py-4">Contact Details</th>
                                <th class="px-6 py-4">Conflicts / Existing Lists</th>
                                <th class="px-6 py-4 text-center">Resolution Option</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150">
                            @forelse($duplicates as $email)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-5 text-center">
                                        <input type="checkbox" :value="{{ $email->id }}" x-model="selectedIds"
                                            class="rounded-sm border-gray-300 text-brand focus:ring-brand focus:ring-offset-0 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-surface-900">
                                                {{ $email->name ?: '—' }}
                                            </span>
                                            <div class="flex items-center gap-4 mt-1">
                                                @if ($email->email)
                                                    <span class="text-xs text-surface-500 font-bold flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        {{ $email->email }}
                                                    </span>
                                                @endif
                                                @if ($email->whatsapp_number)
                                                    <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                        </svg>
                                                        {{ $email->whatsapp_number }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-wrap gap-1.5">
                                            @if (isset($email->meta['cross_list_duplicates']))
                                                @foreach ($email->meta['cross_list_duplicates'] as $dup)
                                                    <a href="{{ route('admin.email-lists.show', $dup['list_id']) }}"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-surface-100 hover:bg-surface-200 text-surface-700 text-[10px] font-black uppercase tracking-wider rounded-sm border border-surface-200 transition-colors">
                                                        <svg class="w-3 h-3 text-surface-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                        {{ $dup['list_name'] }}
                                                    </a>
                                                @endforeach
                                            @else
                                                <span class="text-xs text-surface-400 italic font-medium">Other list details
                                                    not recorded</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-center gap-4"
                                            x-init="resolutions[{{ $email->id }}] = resolutions[{{ $email->id }}] || 'keep_both'">
                                            <input type="hidden" :name="`resolutions[{{ $email->id }}]`"
                                                :value="resolutions[{{ $email->id }}]">

                                            <button type="button" @click="resolutions[{{ $email->id }}] = 'keep_old'"
                                                :class="resolutions[{{ $email->id }}] === 'keep_old' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-surface-500 border-gray-200 hover:bg-slate-50'"
                                                class="px-3 py-2 border rounded-sm text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer">
                                                Keep Old Only
                                            </button>
                                            <button type="button" @click="resolutions[{{ $email->id }}] = 'move_new'"
                                                :class="resolutions[{{ $email->id }}] === 'move_new' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-surface-500 border-gray-200 hover:bg-slate-50'"
                                                class="px-3 py-2 border rounded-sm text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer">
                                                Move Here
                                            </button>
                                            <button type="button" @click="resolutions[{{ $email->id }}] = 'keep_both'"
                                                :class="resolutions[{{ $email->id }}] === 'keep_both' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-white text-surface-500 border-gray-200 hover:bg-slate-50'"
                                                class="px-3 py-2 border rounded-sm text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer">
                                                Keep in Both
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center text-surface-400 font-bold uppercase tracking-widest bg-slate-50/50">
                                        No cross-list duplicates found requiring resolution.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($duplicates->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                        <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest">
                            Showing {{ $duplicates->firstItem() }}-{{ $duplicates->lastItem() }} of
                            {{ $duplicates->total() }} Entries
                        </div>
                        {{ $duplicates->links() }}
                    </div>
                @endif
            </div>

            @if ($duplicates->isNotEmpty())
                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('admin.email-lists.show', $emailList) }}"
                        class="px-6 py-4 bg-white border border-gray-150 hover:bg-gray-50 text-surface-600 rounded-sm text-xs font-black uppercase tracking-[0.2em] transition-all">
                        Cancel Changes
                    </a>
                    <button type="submit"
                        class="px-8 py-4 bg-surface-900 text-white rounded-sm text-xs font-black uppercase tracking-[0.2em] hover:bg-black transition-all hover:scale-[1.02] active:scale-95 shadow-md">
                        Apply Resolutions
                    </button>
                </div>
            @endif
        </form>
    </div>
@endsection
