@extends('layouts.app')
@section('title', $emailList->name)
@section('heading', $emailList->name)

@section('header-actions')
    <div class="flex items-center gap-2">
        <button @click="$dispatch('open-import-more')" class="px-4 py-2 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-sm font-bold uppercase tracking-widest">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Add Contacts
        </button>
        <a href="{{ route('admin.email-lists.index') }}" class="px-4 py-2 flex items-center text-sm font-bold text-surface-600 hover:text-surface-900 border rounded-sm uppercase tracking-widest transition-colors"><svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Back to Lists</a>
    </div>
@endsection

@section('content')
<div class="space-y-4 animate-slide-up"
     x-data="emailListView()"
     @keydown.escape.window="showEditModal = false; showImportMoreModal = false"
     @open-import-more.window="showImportMoreModal = true"
     x-init="@if($emailList->status === 'processing') pollStatus() @endif">

    {{-- ── Processing Banner ── --}}
    @if($emailList->status === 'processing')
    <div class="p-6 bg-amber-50 border border-amber-100 rounded-sm">
        <div class="flex items-center gap-4">
            <div class="animate-spin w-6 h-6 border-2 border-amber-600 border-t-transparent rounded-full"></div>
            <div>
                <p class="text-amber-900 font-black uppercase text-xs tracking-widest">Optimization in Progress</p>
                <p class="text-[11px] text-amber-700 font-medium mt-0.5">Validating email identities and cleaning duplicates. Page will refresh automatically.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Segment Analytics ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <button @click="filterBy('all')" 
                :class="filter === 'all' ? 'border-brand bg-brand/5 shadow-lg shadow-brand/5' : 'border-gray-100 hover:border-gray-200'"
                class="bg-white border rounded-sm p-5 text-left transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-8 h-8 rounded-sm bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-brand group-hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div class="text-[8px] font-black text-surface-400 uppercase tracking-widest">Repository</div>
            </div>
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Total Records</p>
            <h3 class="text-2xl font-black text-surface-900" x-text="stats.total.toLocaleString()">{{ number_format($stats['total']) }}</h3>
            <div class="mt-3 h-1 w-full bg-gray-50 rounded-full overflow-hidden">
                <div class="h-full bg-surface-800" style="width: 100%"></div>
            </div>
        </button>

        <button @click="filterBy('valid')" 
                :class="filter === 'valid' ? 'border-emerald-500 bg-emerald-50/30 shadow-lg shadow-emerald-50' : 'border-gray-100 hover:border-emerald-200'"
                class="bg-white border rounded-sm p-5 text-left transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-8 h-8 rounded-sm bg-emerald-50 flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-[8px] font-black text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full uppercase tracking-tighter" x-text="Math.round(stats.valid/stats.total*100 || 0) + '%'"></div>
            </div>
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Valid Entries</p>
            <h3 class="text-2xl font-black text-emerald-600" x-text="stats.valid.toLocaleString()">{{ number_format($stats['valid']) }}</h3>
            <div class="mt-3 h-1 w-full bg-gray-50 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500" :style="`width: ${(stats.valid/stats.total*100) || 0}%`"></div>
            </div>
        </button>

        <button @click="filterBy('invalid')" 
                :class="filter === 'invalid' ? 'border-red-500 bg-red-50/30 shadow-lg shadow-red-50' : 'border-gray-100 hover:border-red-200'"
                class="bg-white border rounded-sm p-5 text-left transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-8 h-8 rounded-sm bg-red-50 flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="text-[8px] font-black text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full uppercase tracking-tighter" x-text="Math.round(stats.invalid/stats.total*100 || 0) + '%'"></div>
            </div>
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Invalid Emails</p>
            <h3 class="text-2xl font-black text-red-600" x-text="stats.invalid.toLocaleString()">{{ number_format($stats['invalid']) }}</h3>
            <div class="mt-3 h-1 w-full bg-gray-50 rounded-full overflow-hidden">
                <div class="h-full bg-red-500" :style="`width: ${(stats.invalid/stats.total*100) || 0}%`"></div>
            </div>
        </button>

        <button @click="filterBy('duplicate')" 
                :class="filter === 'duplicate' ? 'border-amber-500 bg-amber-50/30 shadow-lg shadow-amber-50' : 'border-gray-100 hover:border-amber-200'"
                class="bg-white border rounded-sm p-5 text-left transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-8 h-8 rounded-sm bg-amber-50 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="text-[8px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full uppercase tracking-tighter" x-text="Math.round(stats.duplicate/stats.total*100 || 0) + '%'"></div>
            </div>
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Duplicates</p>
            <h3 class="text-2xl font-black text-amber-600" x-text="stats.duplicate.toLocaleString()">{{ number_format($stats['duplicate']) }}</h3>
            <div class="mt-3 h-1 w-full bg-gray-50 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500" :style="`width: ${(stats.duplicate/stats.total*100) || 0}%`"></div>
            </div>
        </button>
    </div>

    {{-- ── Contact Explorer ── --}}
    <div class="bg-white border border-gray-100 rounded-sm overflow-hidden shadow-sm">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center gap-6 bg-surface-50/30">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model.debounce.300ms="search" @input="fetchEmails()" class="w-full pl-12 pr-4 py-3 bg-white border border-gray-100 rounded-sm text-sm focus:border-brand focus:ring-0 transition-all" placeholder="Search by email, name or domain...">
            </div>
            <div class="flex items-center gap-3">
                <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Active Filter:</div>
                <div class="p-3.5 bg-surface-800 border rounded-sm text-xs font-black text-white uppercase tracking-widest" x-text="filter"></div>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[500px]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-100 border-b border-t border-color">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">Email Address</th>
                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">Full Name</th>
                        @php
                            $mapping = $emailList->column_mapping ?? [];
                            $displayedFields = [];
                            foreach(['company', 'job_title', 'phone', 'city'] as $field) {
                                if (isset($mapping[$field])) $displayedFields[] = $field;
                            }
                            foreach($mapping as $key => $val) {
                                if (str_starts_with($key, 'custom_')) $displayedFields[] = $key;
                            }
                        @endphp

                        @foreach($displayedFields as $field)
                            <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">{{ str_replace(['_', 'custom_'], [' ', ''], $field) }}</th>
                        @endforeach

                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">Status</th>
                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">Subscription</th>
                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600">Joined</th>
                        <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-surface-600 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="email-table-body">
                    @include('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 border-t border-gray-100 bg-gray-50/30" id="pagination-links">
            {{ $emails->links() }}
        </div>
    </div>

    {{-- ── Teleported Modals ── --}}
    <template x-teleport="body">
        <div>
            {{-- ── Edit Contact Modal ── --}}
            <div x-show="showEditModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80">
                <div class="bg-white rounded-sm w-full max-w-2xl shadow-2xl animate-scale-in" @click.away="showEditModal = false">
                    <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                        <div>
                            <h3 class="text-lg font-black text-surface-900 tracking-tight">Modify Record</h3>
                            <p class="text-[10px] text-brand font-black uppercase mt-0.5 tracking-widest" x-text="editingContact.email"></p>
                        </div>
                        <button @click="showEditModal = false" class="text-surface-400 hover:text-surface-600 transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="updateContact()">
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-5">
                                <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest border-b border-gray-100 pb-2">Core Identity</h4>
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Email Address</label>
                                    <input type="email" x-model="editingContact.email" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Full Name</label>
                                    <input type="text" x-model="editingContact.name" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Subscription</label>
                                    <select x-model="editingContact.subscription_status" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                        <option value="subscribed">Subscribed</option>
                                        <option value="unsubscribed">Unsubscribed</option>
                                        <option value="bounced">Bounced</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="space-y-5">
                                <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest border-b border-gray-100 pb-2">Extended Attributes</h4>
                                <div class="grid grid-cols-1 gap-5">
                                    <template x-for="(col, key) in mapping" :key="key">
                                        <template x-if="!['email', 'name'].includes(key)">
                                            <div>
                                                <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2" x-text="key.replace('custom_', '').toUpperCase()"></label>
                                                <input type="text" x-model="editingContact.meta[key]" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="showEditModal = false" class="text-[10px] font-black text-surface-400 uppercase tracking-widest px-6 py-2">Discard</button>
                            <button type="submit" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm shadow-xl shadow-brand/10" :disabled="saving">
                                <span x-show="saving" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2 inline-block"></span>
                                Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Modal: Import & Add ── --}}
            <div x-show="showImportMoreModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80" x-cloak>
                <div class="bg-white rounded-sm shadow-2xl w-full max-w-xl overflow-hidden animate-scale-in" @click.away="showImportMoreModal = false">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-surface-900 tracking-tight">Expand Repository</h3>
                                <p class="text-[10px] text-surface-400 font-bold uppercase mt-1 tracking-widest">Add contacts to "{{ $emailList->name }}"</p>
                            </div>
                            <button @click="showImportMoreModal = false" class="p-2 hover:bg-gray-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-6" x-data="{ type: 'upload', fileName: '' }">
                        {{-- Method Selection --}}
                        <div class="grid grid-cols-3 gap-2">
                            <button @click="type = 'upload'" :class="type === 'upload' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400 hover:border-gray-200'" class="p-4 border rounded-sm transition-all text-center group">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-[9px] font-black uppercase tracking-widest">Excel/CSV</p>
                            </button>
                            <button @click="type = 'paste'" :class="type === 'paste' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400 hover:border-gray-200'" class="p-4 border rounded-sm transition-all text-center group">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-[9px] font-black uppercase tracking-widest">Paste</p>
                            </button>
                            <button @click="type = 'single'" :class="type === 'single' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400 hover:border-gray-200'" class="p-4 border rounded-sm transition-all text-center group">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                <p class="text-[9px] font-black uppercase tracking-widest">Single</p>
                            </button>
                        </div>

                        {{-- Batch Import Form --}}
                        <form x-show="type !== 'single'" action="{{ route('admin.email-lists.import-more', $emailList) }}" method="POST" enctype="multipart/form-data" class="space-y-6 animate-in fade-in slide-in-from-top-1">
                            @csrf
                            <input type="hidden" name="import_type" :value="type">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Segment Tag</label>
                                    <input type="text" name="segment_name" value="{{ $emailList->segment_name }}" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Source</label>
                                    <input type="text" name="signup_source" value="{{ $emailList->signup_source }}" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                </div>
                            </div>

                            <div x-show="type === 'upload'">
                                <div class="relative group">
                                    <input type="file" name="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".csv, .xlsx, .txt" @change="fileName = $event.target.files[0]?.name || ''">
                                    <div class="p-8 border-2 border-dashed border-brand/30 rounded-sm bg-gray-50 group-hover:bg-white group-hover:border-brand/60 transition-all text-center">
                                        <svg class="w-8 h-8 text-brand/30 mx-auto mb-3 group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <p class="text-surface-900 font-black uppercase text-[10px] tracking-widest" x-text="fileName ? 'File Selected' : 'Select File'"></p>
                                        <p class="text-[9px] text-surface-400 font-bold uppercase mt-1" x-text="fileName ? fileName : '.csv, .xlsx, .txt'"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="type === 'paste'">
                                <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Email List</label>
                                <textarea name="emails_text" rows="4" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-xs font-mono focus:bg-white focus:border-brand transition-all" placeholder="email@example.com&#10;another@email.com"></textarea>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm shadow-lg shadow-brand/10">Process Batch</button>
                            </div>
                        </form>

                        {{-- Single Contact Form --}}
                        <form x-show="type === 'single'" @submit.prevent="addManualContact()" class="space-y-6 animate-in fade-in slide-in-from-top-1">
                            <div class="grid grid-cols-1 gap-5">
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Email Address</label>
                                    <input type="email" x-model="newContact.email" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all" placeholder="john@example.com" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Full Name</label>
                                    <input type="text" x-model="newContact.name" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all" placeholder="John Doe">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Segment</label>
                                        <input type="text" x-model="newContact.segment_name" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all" placeholder="Sales">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1.5">Source</label>
                                        <select x-model="newContact.signup_source" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-sm text-sm focus:bg-white focus:border-brand transition-all">
                                            <option value="Manual Entry">Manual Entry</option>
                                            <option value="Website Widget">Website Widget</option>
                                            <option value="Import">Import</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-surface-800 text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm shadow-lg" :disabled="adding">Save Contact</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function emailListView() {
    return {
        filter: 'all',
        search: '',
        showEditModal: false,
        showImportMoreModal: false,
        adding: false,
        saving: false,
        newContact: { email: '', name: '', segment_name: '', signup_source: 'Manual Entry' },
        editingContact: { id: null, email: '', name: '', subscription_status: '', meta: {} },
        mapping: @js($emailList->column_mapping ?? []),
        stats: {
            total: {{ $stats['total'] }},
            valid: {{ $stats['valid'] }},
            invalid: {{ $stats['invalid'] }},
            duplicate: {{ $stats['duplicate'] }},
        },

        editContact(id) {
            fetch(`/email-lists/{{ $emailList->id }}/emails/${id}`)
                .then(r => r.json())
                .then(data => {
                    this.editingContact = data;
                    if (!this.editingContact.meta) this.editingContact.meta = {};
                    this.showEditModal = true;
                });
        },

        updateContact() {
            this.saving = true;
            fetch(`/email-lists/{{ $emailList->id }}/emails/${this.editingContact.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.editingContact)
            })
            .then(r => r.json())
            .then(() => {
                this.saving = false;
                this.showEditModal = false;
                this.fetchEmails();
            });
        },

        filterBy(status) {
            this.filter = status;
            this.fetchEmails();
        },
        fetchEmails() {
            fetch('{{ route("admin.email-lists.filter", $emailList) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: this.filter, search: this.search })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('email-table-body').innerHTML = data.html;
                document.getElementById('pagination-links').innerHTML = data.links;
            });
        },
        addManualContact() {
            this.adding = true;
            fetch('{{ route("admin.email-lists.add-contact", $emailList) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.newContact)
            })
            .then(r => r.json())
            .then(() => {
                this.adding = false;
                this.showImportMoreModal = false;
                this.newContact = { email: '', name: '', segment_name: '', signup_source: 'Manual Entry' };
                this.fetchEmails();
                this.refreshStats();
            });
        },
        deleteEmail(emailId) {
            if (!confirm('Remove this recipient?')) return;
            fetch(`/email-lists/{{ $emailList->id }}/emails/${emailId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(() => {
                this.fetchEmails();
                this.refreshStats();
            });
        },
        refreshStats() {
            fetch('{{ route("admin.email-lists.status", $emailList) }}')
                .then(r => r.json())
                .then(data => {
                    this.stats.total = data.total_records;
                    this.stats.valid = data.valid_count;
                    this.stats.invalid = data.invalid_count;
                    this.stats.duplicate = data.duplicate_count;
                });
        },
        pollStatus() {
            const interval = setInterval(() => {
                fetch('{{ route("admin.email-lists.status", $emailList) }}')
                    .then(r => r.json())
                    .then(data => {
                        this.stats.total = data.total_records;
                        this.stats.valid = data.valid_count;
                        this.stats.invalid = data.invalid_count;
                        this.stats.duplicate = data.duplicate_count;
                        if (data.status === 'completed' || data.status === 'failed') {
                            clearInterval(interval);
                            location.reload();
                        }
                    });
            }, 3000);
        }
    };
}
</script>
@endsection
