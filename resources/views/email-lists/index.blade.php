@extends('layouts.app')

@section('title', 'Audience Intelligence')
@section('heading', 'Audience Repositories')

@section('header-actions')
    <a href="{{ route('admin.email-lists.create') }}"
        class="inline-flex items-center gap-2 bg-surface-800 text-white px-5 py-2.5 rounded-sm text-[11px] font-black uppercase tracking-[0.1em] hover:bg-brand/90 transition-all hover:scale-[1.02] active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Create New Audience
    </a>
@endsection

@section('content')
    <div class="space-y-8 animate-slide-up">
        {{-- ── Global Intelligence Header ── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Total Reach --}}
            <div class="bg-surface-800 rounded-sm p-6 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-20 h-20 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                    </svg>
                </div>
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mb-4">Total Ecosystem Reach</p>
                <h3 class="text-4xl font-black text-white tracking-tighter">{{ number_format($globalStats['total']) }}</h3>
                <div class="mt-4 flex items-center gap-2">
                    <span
                        class="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 px-2 py-0.5 rounded-sm flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Audience Health: Active
                    </span>
                </div>
            </div>

            {{-- Quality Index --}}
            <div class="bg-white border border-gray-100 rounded-sm p-6 relative overflow-hidden group">
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mb-4">Quality Index (Valid)</p>
                <div class="flex items-end justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-emerald-600 tracking-tighter">
                            {{ number_format($globalStats['subscribed']) }}</h3>
                        <p class="text-[10px] font-bold text-surface-400 mt-1 uppercase">Deliverable Contacts</p>
                    </div>
                    <div class="text-right">
                        <span
                            class="text-2xl font-black text-surface-900">{{ $globalStats['total'] > 0 ? round(($globalStats['subscribed'] / $globalStats['total']) * 100, 1) : 0 }}%</span>
                        <div class="w-16 bg-gray-100 h-1 rounded-full mt-2 overflow-hidden">
                            <div class="bg-emerald-500 h-full"
                                style="width: {{ $globalStats['total'] > 0 ? ($globalStats['subscribed'] / $globalStats['total'] * 100) : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Risk Assessment --}}
            <div class="bg-white border border-gray-100 rounded-sm p-6 relative overflow-hidden group">
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mb-4">Risk Assessment (Bounces)
                </p>
                <div class="flex items-end justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-red-600 tracking-tighter">
                            {{ number_format($globalStats['bounced']) }}</h3>
                        <p class="text-[10px] font-bold text-surface-400 mt-1 uppercase">Failed Deliveries</p>
                    </div>
                    <div class="text-right">
                        <span
                            class="text-2xl font-black text-surface-900">{{ $globalStats['total'] > 0 ? round(($globalStats['bounced'] / $globalStats['total']) * 100, 1) : 0 }}%</span>
                        <div class="w-16 bg-gray-100 h-1 rounded-full mt-2 overflow-hidden">
                            <div class="bg-red-500 h-full"
                                style="width: {{ $globalStats['total'] > 0 ? ($globalStats['bounced'] / $globalStats['total'] * 100) : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cleanup Required --}}
            <div class="bg-white border border-gray-100 rounded-sm p-6 relative overflow-hidden group">
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mb-4">Maintenance Debt</p>
                <div class="flex items-end justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-amber-600 tracking-tighter">
                            {{ number_format($globalStats['invalid']) }}</h3>
                        <p class="text-[10px] font-bold text-surface-400 mt-1 uppercase">Invalid Records</p>
                    </div>
                    <div class="text-right">
                        <a href="#" class="text-[10px] font-black text-brand uppercase underline tracking-widest">Fix
                            All</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Advanced Audience Grid ── --}}
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                @forelse($lists as $list)
                    <div
                        class="bg-white border border-gray-100 rounded-sm p-4 hover:border-brand/20 transition-all group relative">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                            {{-- Identity --}}
                            <div class="lg:col-span-3">
                                <div class="flex items-start gap-4">
                                    <div x-data="{ 
                                        isEditing: false, 
                                        newName: '{{ addslashes($list->name) }}', 
                                        isSaving: false,
                                        updateName() {
                                            if (this.newName.trim() === '' || this.newName === '{{ addslashes($list->name) }}') {
                                                this.isEditing = false;
                                                return;
                                            }
                                            this.isSaving = true;
                                            fetch('{{ route('admin.email-lists.update-name', $list) }}', {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({ name: this.newName })
                                            }).then(res => res.json()).then(data => {
                                                if(data.success) {
                                                    this.isEditing = false;
                                                }
                                                this.isSaving = false;
                                            }).catch(() => {
                                                this.isSaving = false;
                                                this.isEditing = false;
                                            });
                                        }
                                    }" class="w-full">
                                        <template x-if="!isEditing">
                                            <div class="flex items-center gap-2 group/edit">
                                                <a href="{{ route('admin.email-lists.show', $list) }}"
                                                    class="text-lg font-semibold uppercase text-surface-900 hover:text-brand transition-colors block truncate" x-text="newName"></a>
                                                <button @click="isEditing = true" class="text-surface-300 hover:text-brand transition-colors opacity-0 group-hover/edit:opacity-100 p-1 flex-shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="isEditing">
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="newName" @keydown.enter="updateName()" @keydown.escape="isEditing = false; newName = '{{ addslashes($list->name) }}'" class="text-lg font-semibold uppercase text-surface-900 border-b-2 border-brand focus:outline-none bg-transparent w-full pb-0.5" :disabled="isSaving" x-init="$el.focus()">
                                                <span x-show="isSaving" class="text-brand flex-shrink-0">
                                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </span>
                                            </div>
                                        </template>
                                        <div class="flex items-center gap-2 mt-1 w-full">
                                            <span
                                                class="text-[10px] text-surface-600 uppercase tracking-widest">{{str($list->original_filename ?: 'Manual Intake')->limit(20) }}</span>
                                            <span class="text-surface-200">•</span>
                                            <span
                                                class="text-[10px] text-surface-600 uppercase tracking-widest">{{ $list->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Health Analytics --}}
                            <div class="lg:col-span-3">
                                <div class="flex items-center gap-8">
                                    <div>
                                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-2">
                                            Contacts</p>
                                        <h4 class="text-2xl font-black text-surface-900 leading-none">
                                            {{ number_format($list->total_records) }}</h4>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-2">Health
                                            Matrix</p>
                                        <div
                                            class="flex h-2 w-full rounded-full overflow-hidden bg-gray-50 border border-gray-100">
                                            <div class="bg-emerald-500 h-full"
                                                style="width: {{ $list->total_records > 0 ? ($list->valid_count / $list->total_records * 100) : 0 }}%">
                                            </div>
                                            <div class="bg-red-400 h-full"
                                                style="width: {{ $list->total_records > 0 ? ($list->invalid_count / $list->total_records * 100) : 0 }}%">
                                            </div>
                                        </div>
                                        <div class="flex justify-between mt-1.5">
                                            <span class="text-[8px] font-black text-emerald-600 uppercase">
                                                {{ number_format($list->valid_count) }}
                                                ({{ $list->total_records > 0 ? round(($list->valid_count / $list->total_records) * 100) : 0 }}%)
                                                Valid
                                            </span>
                                            <span class="text-[8px] font-black text-red-500 uppercase">
                                                {{ number_format($list->invalid_count) }}
                                                ({{ $list->total_records > 0 ? round(($list->invalid_count / $list->total_records) * 100) : 0 }}%)
                                                Invalid
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Performance --}}
                            <div class="lg:col-span-3">
                                <div class="flex items-center gap-8">
                                    <div class="">
                                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-2">
                                            Performance Risk</p>
                                        <div class="flex items-center gap-6">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-[10px] font-black text-red-800">{{ number_format($list->emails()->where('subscription_status', 'bounced')->count()) }}</span>
                                                <span class="text-[8px] font-bold text-surface-400 uppercase">Bounces</span>
                                            </div>
                                            <div class="flex flex-col border-l border-gray-200 pl-4">
                                                <span
                                                    class="text-[10px] font-black text-amber-600">{{ number_format($list->emails()->whereNotNull('unsubscribed_at')->count()) }}</span>
                                                <span class="text-[8px] font-bold text-surface-400 uppercase">Unsubs</span>
                                            </div>
                                            <div class="flex flex-col border-l border-gray-200 pl-4">
                                                <span
                                                    class="text-[10px] font-black text-emerald-600">{{ $list->total_records > 0 ? round(($list->valid_count / $list->total_records) * 100) : 0 }}%</span>
                                                <span class="text-[8px] font-bold text-surface-400 uppercase">Reach</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Activity & Actions --}}
                            <div class="lg:col-span-3">
                                <div class="flex items-center justify-end gap-6">
                                    <div class="text-right">
                                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Last
                                            Updated</p>
                                        <p class="text-xs font-black text-surface-900 uppercase tracking-tighter">
                                            {{ $list->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.email-lists.show', $list) }}"
                                            class="bg-surface-800 text-white p-3 rounded-sm text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all active:scale-95">Analytics</a>
                                        <form action="{{ route('admin.email-lists.destroy', $list) }}" method="POST"
                                            onsubmit="return confirm('PERMANENT DELETE: This cannot be undone. Proceed?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-surface-600 hover:text-red-600 hover:bg-red-50 rounded-sm transition-all">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white border-2 border-dashed border-gray-100 rounded-sm py-24 text-center">
                        <div
                            class="w-20 h-20 bg-surface-50 rounded-full flex items-center justify-center mx-auto mb-6 text-surface-200">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-surface-900 uppercase tracking-widest">No Intelligence Data</h3>
                        <p
                            class="text-sm text-surface-400 font-bold uppercase tracking-tight mt-2 mb-10 max-w-xs mx-auto leading-relaxed">
                            Import your contact ecosystem to start generating advanced deliverability analytics.</p>
                        <a href="{{ route('admin.email-lists.create') }}"
                            class="bg-brand text-white px-10 py-4 rounded-sm text-xs font-black uppercase tracking-[0.2em] hover:scale-[1.05] transition-all">Onboard
                            First Audience</a>
                    </div>
                @endforelse
            </div>

            @if($lists->hasPages())
                <div class="pt-6">
                    {{ $lists->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection