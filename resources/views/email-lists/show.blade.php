@extends('layouts.app')
@section('title', $emailList->name)
@section('heading', $emailList->name)

@section('header-actions')
    <div class="flex items-center gap-3">
        <button @click="$dispatch('open-import-more')" class="btn btn-primary btn-sm bg-indigo-600 hover:bg-indigo-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Import More
        </button>
        <button @click="$dispatch('open-add-modal')" class="btn btn-outline btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Add Single
        </button>
        <div class="h-6 w-px bg-surface-200 mx-1"></div>
        <a href="{{ route('email-lists.index') }}" class="btn btn-ghost btn-sm">Back to Lists</a>
        <form action="{{ route('email-lists.destroy', $emailList) }}" method="POST" onsubmit="return confirm('Delete this list and all emails?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50">Delete List</button>
        </form>
    </div>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up"
     x-data="emailListView()"
     @keydown.escape.window="showAddModal = false; showEditModal = false; showImportMoreModal = false"
     @open-import-more.window="showImportMoreModal = true"
     @open-add-modal.window="showAddModal = true"
     x-init="@if($emailList->status === 'processing') pollStatus() @endif">

    {{-- ── Processing Banner ── --}}
    @if($emailList->status === 'processing')
    <div class="p-6 bg-amber-50 border border-amber-200 rounded-xl">
        <div class="flex items-center gap-4">
            <div class="animate-spin w-6 h-6 border-2 border-amber-600 border-t-transparent rounded-full"></div>
            <div>
                <p class="text-amber-900 font-bold">In-Progress: Optimizing your list...</p>
                <p class="text-sm text-amber-700">Validating domains and removing duplicates. Page updates live.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Premium Metric Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <button @click="filterBy('all')" 
                :class="filter === 'all' ? 'border-primary-600 ring-4 ring-primary-50 bg-primary-50/20' : 'border-surface-200 hover:border-primary-300'"
                class="stat-card text-left transition-all duration-300">
            <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Total Records</p>
            <h3 class="text-3xl font-black text-surface-900 mt-1" x-text="stats.total.toLocaleString()">{{ number_format($stats['total']) }}</h3>
            <div class="mt-3 h-1 w-full bg-surface-100 rounded-full overflow-hidden">
                <div class="h-full bg-primary-500" style="width: 100%"></div>
            </div>
        </button>

        <button @click="filterBy('valid')" 
                :class="filter === 'valid' ? 'border-emerald-600 ring-4 ring-emerald-50 bg-emerald-50/20' : 'border-surface-200 hover:border-emerald-300'"
                class="stat-card text-left transition-all duration-300">
            <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Valid (Ready)</p>
            <h3 class="text-3xl font-black text-emerald-600 mt-1" x-text="stats.valid.toLocaleString()">{{ number_format($stats['valid']) }}</h3>
            <div class="mt-3 h-1 w-full bg-surface-100 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500" :style="`width: ${(stats.valid/stats.total*100) || 0}%`"></div>
            </div>
        </button>

        <button @click="filterBy('invalid')" 
                :class="filter === 'invalid' ? 'border-red-600 ring-4 ring-red-50 bg-red-50/20' : 'border-surface-200 hover:border-red-300'"
                class="stat-card text-left transition-all duration-300">
            <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Invalid</p>
            <h3 class="text-3xl font-black text-red-600 mt-1" x-text="stats.invalid.toLocaleString()">{{ number_format($stats['invalid']) }}</h3>
            <div class="mt-3 h-1 w-full bg-surface-100 rounded-full overflow-hidden">
                <div class="h-full bg-red-500" :style="`width: ${(stats.invalid/stats.total*100) || 0}%`"></div>
            </div>
        </button>

        <button @click="filterBy('duplicate')" 
                :class="filter === 'duplicate' ? 'border-amber-600 ring-4 ring-amber-50 bg-amber-50/20' : 'border-surface-200 hover:border-amber-300'"
                class="stat-card text-left transition-all duration-300">
            <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">Duplicates</p>
            <h3 class="text-3xl font-black text-amber-600 mt-1" x-text="stats.duplicate.toLocaleString()">{{ number_format($stats['duplicate']) }}</h3>
            <div class="mt-3 h-1 w-full bg-surface-100 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500" :style="`width: ${(stats.duplicate/stats.total*100) || 0}%`"></div>
            </div>
        </button>
    </div>

    {{-- ── Advanced Filter & Table ── --}}
    <div class="glass-card overflow-hidden">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex flex-col md:flex-row md:items-center gap-6">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model.debounce.300ms="search" @input="fetchEmails()" class="form-input pl-12 !bg-white" placeholder="Search contacts...">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-surface-400 uppercase">Showing:</span>
                <span class="badge badge-info" x-text="filter.toUpperCase()"></span>
            </div>
        </div>

        <div class="overflow-x-auto min-h-[400px]">
            <table class="data-table">
                <thead>
                    <tr class="bg-surface-50/50">
                        <th class="!pl-8 !py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Email Address</th>
                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Full Name</th>
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
                            <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">{{ str_replace(['_', 'custom_'], [' ', ''], $field) }}</th>
                        @endforeach

                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Status</th>
                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Subscription</th>
                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Joined</th>
                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400">Updated</th>
                        <th class="!py-3 text-[10px] font-black uppercase tracking-widest text-surface-400 text-right !pr-8">Action</th>
                    </tr>
                </thead>
                <tbody id="email-table-body" class="text-xs">
                    @include('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 border-t border-surface-100 bg-surface-50/30" id="pagination-links">
            {{ $emails->links() }}
        </div>
    </div>

    {{-- ── Manual Add Modal ── --}}
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/40 backdrop-blur-sm">
        <div class="glass-card w-full max-w-md shadow-2xl animate-scale-in" @click.away="showAddModal = false">
            <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                <h3 class="text-lg font-black text-surface-900">Add New Contact</h3>
                <button @click="showAddModal = false" class="text-surface-400 hover:text-surface-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="addManualContact()">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="form-label">Email Address</label>
                        <input type="email" x-model="newContact.email" class="form-input" placeholder="john@example.com" required>
                    </div>
                    <div>
                        <label class="form-label">Full Name</label>
                        <input type="text" x-model="newContact.name" class="form-input" placeholder="John Doe">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Segment</label>
                            <input type="text" x-model="newContact.segment_name" class="form-input" placeholder="Sales">
                        </div>
                        <div>
                            <label class="form-label">Source</label>
                            <select x-model="newContact.signup_source" class="form-input">
                                <option value="Manual Entry">Manual Entry</option>
                                <option value="Website Widget">Website Widget</option>
                                <option value="Import">Import</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-surface-50 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showAddModal = false" class="btn btn-ghost px-6">Cancel</button>
                    <button type="submit" class="btn btn-primary px-8" :disabled="adding">Save Contact</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Edit Contact Modal ── --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/40 backdrop-blur-sm">
        <div class="glass-card w-full max-w-2xl shadow-2xl animate-scale-in" @click.away="showEditModal = false">
            <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-surface-900">Edit Contact Details</h3>
                    <p class="text-[10px] text-surface-400 font-bold uppercase mt-0.5" x-text="editingContact.email"></p>
                </div>
                <button @click="showEditModal = false" class="text-surface-400 hover:text-surface-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="updateContact()">
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-primary-600 uppercase tracking-widest border-b border-primary-100 pb-2">Core Identity</h4>
                        <div>
                            <label class="form-label text-[10px]">Email Address</label>
                            <input type="email" x-model="editingContact.email" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Full Name</label>
                            <input type="text" x-model="editingContact.name" class="form-input">
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Subscription Status</label>
                            <select x-model="editingContact.subscription_status" class="form-input">
                                <option value="subscribed">Subscribed</option>
                                <option value="unsubscribed">Unsubscribed</option>
                                <option value="bounced">Bounced</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest border-b border-indigo-100 pb-2">CRM Metadata</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <template x-for="(col, key) in mapping" :key="key">
                                <template x-if="!['email', 'name'].includes(key)">
                                    <div>
                                        <label class="form-label text-[10px]" x-text="key.replace('custom_', '').toUpperCase()"></label>
                                        <input type="text" x-model="editingContact.meta[key]" class="form-input">
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-surface-50 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showEditModal = false" class="btn btn-ghost px-6">Cancel</button>
                    <button type="submit" class="btn btn-primary px-8" :disabled="saving">
                        <span x-show="saving" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: Import More (New) ── --}}
    <div x-show="showImportMoreModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="showImportMoreModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 border-b border-surface-100 bg-surface-50/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-surface-900 tracking-tight">Import More Contacts</h3>
                        <p class="text-sm text-surface-500 mt-1">Append new contacts to "{{ $emailList->name }}"</p>
                    </div>
                    <button @click="showImportMoreModal = false" class="p-2 hover:bg-surface-200 rounded-full transition-colors">
                        <svg class="w-6 h-6 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <form action="{{ route('email-lists.import-more', $emailList) }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8" x-data="{ type: 'upload' }">
                @csrf
                
                {{-- Import Type Selection --}}
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="import_type" value="upload" x-model="type" class="peer sr-only">
                        <div class="p-6 rounded-2xl border-2 border-surface-100 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 hover:border-primary-200 transition-all text-center">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <p class="font-bold text-surface-900">Excel / CSV</p>
                        </div>
                    </label>

                    <label class="relative cursor-pointer group">
                        <input type="radio" name="import_type" value="paste" x-model="type" class="peer sr-only">
                        <div class="p-6 rounded-2xl border-2 border-surface-100 peer-checked:border-primary-500 peer-checked:bg-primary-50/30 hover:border-primary-200 transition-all text-center">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="font-bold text-surface-900">Bulk Paste</p>
                        </div>
                    </label>
                </div>

                {{-- Context Fields (Hidden if needed, or shown for new segments) --}}
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="form-label">Segment Name (Optional)</label>
                        <input type="text" name="segment_name" value="{{ $emailList->segment_name }}" class="form-input" placeholder="e.g. New Batch Jan">
                    </div>
                    <div>
                        <label class="form-label">Signup Source</label>
                        <input type="text" name="signup_source" value="{{ $emailList->signup_source }}" class="form-input" placeholder="e.g. Website Signup">
                    </div>
                </div>

                {{-- Upload Section --}}
                <div x-show="type === 'upload'" class="animate-in fade-in slide-in-from-top-4 duration-300">
                    <div class="relative group">
                        <input type="file" name="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="p-12 border-2 border-dashed border-surface-200 rounded-3xl bg-surface-50 group-hover:bg-white group-hover:border-primary-300 transition-all text-center">
                            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4 group-hover:rotate-6 transition-transform">
                                <svg class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-surface-900 font-black text-lg">Click to select file</p>
                            <p class="text-surface-500 text-sm mt-1">Supports .csv, .xlsx, .txt</p>
                        </div>
                    </div>
                </div>

                {{-- Paste Section --}}
                <div x-show="type === 'paste'" class="animate-in fade-in slide-in-from-top-4 duration-300">
                    <label class="form-label">Paste Email List</label>
                    <textarea name="emails_text" rows="8" class="form-input font-mono text-sm" placeholder="one-email@per-line.com&#10;another@email.com"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showImportMoreModal = false" class="btn btn-ghost px-8">Cancel</button>
                    <button type="submit" class="btn btn-primary px-12 py-3 shadow-xl shadow-primary-200">
                        Continue to Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function emailListView() {
    return {
        filter: 'all',
        search: '',
        showAddModal: false,
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
            fetch('{{ route("email-lists.filter", $emailList) }}', {
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
            fetch('{{ route("email-lists.add-contact", $emailList) }}', {
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
                this.showAddModal = false;
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
            fetch('{{ route("email-lists.status", $emailList) }}')
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
                fetch('{{ route("email-lists.status", $emailList) }}')
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
