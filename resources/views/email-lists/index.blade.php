@extends('layouts.app')

@section('title', 'Contacts Intelligence')
@section('heading', 'Contact Repositories')

@section('header-actions')
    <a href="{{ route('admin.email-lists.create') }}"
        class="inline-flex items-center gap-2 bg-surface-800 text-white px-5 py-2.5 rounded-sm text-[11px] font-black uppercase tracking-[0.1em] hover:bg-brand/90 transition-all hover:scale-[1.02] active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        Create New Contact List
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
                    <div x-data="{ 
                        isEditing: false, 
                        newName: '{{ addslashes($list->name) }}', 
                        isSaving: false,
                        showDeleteConfirm: false,
                        deleteText: '',
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
                    }" class="bg-white border border-gray-100 rounded-sm p-4 hover:border-brand/20 transition-all group relative">
                        
                        {{-- Delete Confirmation Modal --}}
                        <div x-show="showDeleteConfirm" @click.away="showDeleteConfirm = false; deleteText = ''" class="absolute right-4 top-full mt-2 w-72 bg-white border border-red-200 rounded-sm shadow-xl p-4 z-50 animate-fade-in" x-cloak>
                            <p class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-2">Irreversible Action</p>
                            <p class="text-[10px] font-bold text-red-800 mb-3">Type "DELETE" below to confirm removal of this list and all its contacts.</p>
                            <input type="text" x-model="deleteText" class="w-full px-3 py-2 border border-red-200 rounded-sm text-xs font-bold focus:border-red-500 focus:ring-0 mb-3 uppercase text-red-900" placeholder="DELETE">
                            <div class="flex gap-2">
                                <button @click="showDeleteConfirm = false; deleteText = ''" class="flex-1 px-3 py-2 bg-gray-50 text-surface-600 text-[10px] font-black uppercase tracking-widest rounded-sm border border-gray-200 hover:bg-gray-100">Cancel</button>
                                <form action="{{ route('admin.email-lists.destroy', $list) }}" method="POST" class="flex-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full px-3 py-2 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest rounded-sm disabled:opacity-50 hover:bg-red-700 transition-colors" :disabled="deleteText !== 'DELETE'">Confirm</button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                            {{-- Identity --}}
                            <div class="lg:col-span-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-full flex-1">
                                        <template x-if="!isEditing">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('admin.email-lists.show', $list) }}"
                                                        class="text-base font-black uppercase tracking-tight text-surface-900 hover:text-brand transition-colors block truncate" x-text="newName"></a>
                                                    
                                                    @if($list->list_type === 'email')
                                                        <span class="bg-primary-50 text-primary-600 text-[8px] font-black uppercase px-1.5 py-0.5 rounded-sm border border-primary-100 flex items-center gap-1">
                                                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                            Email
                                                        </span>
                                                    @elseif($list->list_type === 'whatsapp')
                                                        <span class="bg-emerald-50 text-emerald-600 text-[8px] font-black uppercase px-1.5 py-0.5 rounded-sm border border-emerald-100 flex items-center gap-1">
                                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.888-.788-1.487-1.761-1.66-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                            WhatsApp
                                                        </span>
                                                    @else
                                                        <span class="bg-indigo-50 text-indigo-600 text-[8px] font-black uppercase px-1.5 py-0.5 rounded-sm border border-indigo-100 flex items-center gap-1">
                                                            <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                            Dual
                                                        </span>
                                                    @endif

                                                    <button @click="isEditing = true" class="text-surface-300 hover:text-brand transition-colors opacity-0 group-hover:opacity-100 p-1 flex-shrink-0">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                </div>
                                        </template>
                                        <template x-if="isEditing">
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="newName" @keydown.enter="updateName()" @keydown.escape="isEditing = false; newName = '{{ addslashes($list->name) }}'" class="text-base font-black uppercase tracking-tight text-surface-900 border-b-2 border-brand focus:outline-none bg-transparent w-full pb-0.5" :disabled="isSaving" x-init="$el.focus()">
                                                <span x-show="isSaving" class="text-brand flex-shrink-0">
                                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </span>
                                            </div>
                                        </template>
                                        <div class="flex items-center gap-2 mt-1.5 w-full">
                                            <span class="text-[9px] text-surface-500 uppercase font-bold tracking-widest">{{str($list->original_filename ?: 'Manual Intake')->limit(20) }}</span>
                                            <span class="text-surface-200">•</span>
                                            <span class="text-[9px] text-surface-500 uppercase font-bold tracking-widest">{{ $list->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Counts (Original Rows) --}}
                            <div class="lg:col-span-2 border-l border-gray-100 pl-6">
                                <div>
                                    <h4 class="text-xl font-black text-surface-900 leading-none tracking-tighter">{{ number_format($list->unique_contacts_count) }}</h4>
                                    <p class="text-[9px] font-bold text-surface-400 uppercase mt-1 tracking-widest">Profiles</p>
                                    <p class="text-[8px] font-bold text-brand uppercase mt-0.5 tracking-widest">Rows: {{ number_format($list->total_records) }}</p>
                                </div>
                            </div>

                            {{-- Stats (Channels) --}}
                            <div class="lg:col-span-3">
                                @php
                                    $emailCount = $list->emails()->where('is_archived', false)->whereNotNull('email')->where('email', '!=', '')->count();
                                    $waCount = $list->emails()->where('is_archived', false)->whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '')->count();
                                @endphp
                                <div class="flex items-center gap-5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-sm bg-blue-50 flex items-center justify-center shrink-0 border border-blue-100">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-base font-black text-surface-900 leading-none tracking-tighter">{{ number_format($emailCount) }}</p>
                                        </div>
                                    </div>
                                    <div class="w-px h-6 bg-gray-200"></div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-sm bg-emerald-50 flex items-center justify-center shrink-0 border border-emerald-100">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.888-.788-1.487-1.761-1.66-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-base font-black text-surface-900 leading-none tracking-tighter">{{ number_format($waCount) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Activity & Actions --}}
                            <div class="lg:col-span-3">
                                <div class="flex items-center justify-end gap-5">
                                    <div class="text-right">
                                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Updated</p>
                                        <p class="text-[10px] font-black text-surface-900 uppercase tracking-tighter">{{ $list->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.email-lists.show', $list) }}"
                                            class="bg-surface-800 text-white px-4 py-2.5 rounded-sm text-[10px] font-black uppercase tracking-widest hover:bg-brand transition-colors active:scale-95">Analytics</a>
                                        
                                        <button @click="showDeleteConfirm = true" class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-sm transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
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
                            First Contact List</a>
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