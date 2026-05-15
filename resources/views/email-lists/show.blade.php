@extends('layouts.app')
@section('title', $emailList->name)
@section('heading', $emailList->name)

@section('header-actions')
    <div class="flex items-center gap-3" x-data>
        <button @click="$dispatch('open-export-modal')"
            class="px-4 py-3 flex items-center rounded-sm bg-white border border-gray-100 text-surface-600 hover:text-surface-900 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export Contacts
        </button>
        <button @click="$dispatch('open-import-more')"
            class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
            </svg>
            Import Contacts
        </button>
    </div>
@endsection

@section('content')
    <div class="space-y-4 animate-slide-up" x-data="emailListView()"
        @keydown.escape.window="showEditModal = false; showImportMoreModal = false; showExportModal = false"
        @open-import-more.window="showImportMoreModal = true" @open-export-modal.window="showExportModal = true"
        x-init="@if($emailList->status === 'processing') pollStatus() @endif">

        {{-- Tabs Navigation --}}
        <div class="bg-white border-b border-color flex items-center gap-8 -mx-6 -mt-6 mb-6 px-6">
            <button @click="activeTab = 'contacts'"
                :class="activeTab === 'contacts' ? 'border-brand text-brand' : 'border-transparent text-surface-400 hover:text-surface-600'"
                class="py-4 border-b-2 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none cursor-pointer">
                Contacts Grid
            </button>
            <button @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-brand text-brand' : 'border-transparent text-surface-400 hover:text-surface-600'"
                class="py-4 border-b-2 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none flex items-center gap-2 cursor-pointer">
                History & Logs
                <span
                    class="px-1.5 py-0.5 bg-surface-100 text-surface-500 rounded-full text-[8px]">{{ $emailList->activityLogs()->count() }}</span>
            </button>
        </div>

        @php
            $mapping = $emailList->column_mapping ?? [];
            $displayedFields = [];
            foreach (['company', 'job_title', 'phone', 'city'] as $field) {
                if (isset($mapping[$field]))
                    $displayedFields[] = $field;
            }
            foreach ($mapping as $key => $val) {
                if (str_starts_with($key, 'custom_'))
                    $displayedFields[] = $key;
            }
        @endphp

        {{-- ── CONTACTS MAIN VIEW ── --}}
        <div x-show="activeTab === 'contacts'" class="space-y-4">
            {{-- Processing Banner --}}
            <div x-show="stats.status === 'processing'" class="p-5 bg-brand/5 border border-brand/20 rounded-sm" x-cloak>

                <div class="flex items-center gap-4">
                    <div class="shrink-0 animate-spin w-5 h-5 border-2 border-brand border-t-transparent rounded-full">
                    </div>

                    <div class="flex-1">
                        <p class="text-brand font-black uppercase text-[10px] tracking-widest">
                            Synchronization in Progress
                        </p>
                        <p class="text-[11px] text-brand/70 font-medium mt-0.5">
                            You can safely navigate away or perform other tasks. We will update you here once the import is
                            finalized.
                        </p>
                    </div>
                </div>
            </div>


            {{-- Completed Banner --}}
            <div x-show="importJustCompleted" class="p-5 bg-brand/5 border border-brand/20 rounded-sm" x-cloak>

                <div class="flex items-center justify-between gap-4">

                    {{-- Left Content --}}
                    <div class="flex items-center gap-4">
                        <div class="shrink-0 w-8 h-8 rounded-full bg-brand/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-brand font-black uppercase text-[10px] tracking-widest">
                                Import Successful
                            </p>
                            <p class="text-[11px] text-brand/70 font-medium mt-0.5">
                                The synchronization has finished. You can refresh to see the latest audience data.
                            </p>
                        </div>
                    </div>

                    {{-- Right Side Link --}}
                    <div>
                        <a @click.prevent="window.location.reload()"
                            class="text-brand text-[11px] font-semibold cursor-pointer transition">
                            Refresh now →
                        </a>
                    </div>

                </div>
            </div>
            {{-- Search & Filter Engine --}}
            <div class="space-y-4 mb-8">
                {{-- Search Row --}}
                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-surface-400 group-focus-within:text-brand transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" x-model.debounce.300ms="search" @focus="showSearchOptions = true"
                        @input="fetchEmails()"
                        class="w-full pl-11 pr-4 py-3 bg-white border border-gray-100 rounded-sm text-sm font-bold placeholder:text-surface-300 focus:bg-white focus:border-brand focus:ring-0 focus:outline-none transition-all group-hover:border-gray-200"
                        placeholder="Search by email, name, or custom data...">

                    {{-- Floating Search Field Options --}}
                    <div x-show="showSearchOptions && search.length > 0" x-transition x-cloak
                        class="absolute left-0 top-full mt-1 w-full bg-white border border-gray-100 rounded-sm z-[60] p-4 animate-in fade-in slide-in-from-top-2">
                        <p
                            class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-3 border-b border-gray-50 pb-2">
                            Where should we search for "<span class="text-surface-900" x-text="search"></span>"?</p>
                        <div class="flex flex-wrap gap-2">
                            <button @click="searchField = 'all'; showSearchOptions = false; fetchEmails()"
                                :class="searchField === 'all' ? 'bg-surface-900 text-white' : 'bg-gray-50 text-surface-600 hover:bg-gray-100'"
                                class="px-3 py-1.5 rounded-sm text-[9px] font-black uppercase tracking-widest transition-all focus:outline-none cursor-pointer">Global
                                / All</button>
                            <button @click="searchField = 'email'; showSearchOptions = false; fetchEmails()" :class="searchField === 'email' ? 'bg-brand text-white' : 'bg-gray-50 text-surface-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-sm text-[9px] font-black uppercase tracking-widest transition-all focus:outline-none cursor-pointer">Email Only</button>
                            <button @click="searchField = 'name'; showSearchOptions = false; fetchEmails()" :class="searchField === 'name' ? 'bg-brand text-white' : 'bg-gray-50 text-surface-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-sm text-[9px] font-black uppercase tracking-widest transition-all focus:outline-none cursor-pointer">Name</button>
                            @foreach($displayedFields as $field)
                                <button @click="searchField = '{{ $field }}'; showSearchOptions = false; fetchEmails()" :class="searchField === '{{ $field }}' ? 'bg-brand text-white' : 'bg-gray-50 text-surface-600 hover:bg-gray-100'" class="px-3 py-1.5 rounded-sm text-[9px] font-black uppercase tracking-widest transition-all focus:outline-none cursor-pointer">{{ strtoupper(str_replace(['_', 'custom_'], ' ', $field)) }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Filter Row --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Health Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Health:</span>
                        <select x-model="filter" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="all">All Records</option>
                            <option value="valid">Clean / Valid</option>
                            <!-- <option value="risky">Risky Contacts</option> -->
                            <option value="suspicious">Suspicious</option>
                            <option value="role_based">Role-Based</option>
                            <option value="disposable">Disposable</option>
                            <option value="invalid">Invalid/Broken</option>
                            <!-- <option value="duplicate">Duplicates</option> -->
                        </select>
                    </div>

                    {{-- Segment Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Segment:</span>
                        <select x-model="segment" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="all">All Segments</option>
                            @foreach($segments as $s) <option value="{{ $s }}">{{ $s }}</option> @endforeach
                        </select>
                    </div>

                    {{-- Tag Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Tag:</span>
                        <select x-model="tag" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="all">All Tags</option>
                            @foreach($tags as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
                        </select>
                    </div>

                    {{-- Subscription Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Status:</span>
                        <select x-model="subscription" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="all">All Subscription</option>
                            <option value="subscribed">Subscribed</option>
                            <option value="unsubscribed">Unsubscribed</option>
                            <option value="bounced">Bounced (Any)</option>
                            <option value="hard_bounce">Hard Bounces</option>
                            <option value="soft_bounce">Soft Bounces</option>
                            <option value="complaint">Spam Complaints</option>
                        </select>
                    </div>

                    {{-- Source Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Source:</span>
                        <select x-model="source" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="all">All Sources</option>
                            @foreach($sources as $src) <option value="{{ $src }}">{{ $src }}</option> @endforeach
                        </select>
                    </div>

                    {{-- Scrub Button removed from here --}}

                    {{-- Archive Filter --}}
                    <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-sm border border-gray-100 hover:border-gray-200 transition-all">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">View:</span>
                        <select x-model="archived" @change="fetchEmails()" class="bg-transparent border-none text-[10px] font-black text-surface-700 focus:ring-0 focus:outline-none cursor-pointer p-0">
                            <option value="no">Active Only</option>
                            <option value="yes">Archived Only</option>
                            <!-- <option value="all">Everything</option> -->
                        </select>
                    </div>

                    <button @click="resetFilters()" class="p-2 text-surface-300 hover:text-red-500 transition-colors focus:outline-none cursor-pointer" title="Reset Filters">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Active Import Progress --}}
            <div x-show="stats.import_progress !== undefined && stats.status === 'processing'" x-cloak class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-black text-blue-900 uppercase tracking-widest">Background Import in Progress...</span>
                    </div>
                    <span class="text-[10px] font-black text-blue-900" x-text="Math.round(stats.import_progress) + '%'"></span>
                </div>
                <div class="w-full bg-blue-200 h-1 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full transition-all duration-500" :style="'width: ' + stats.import_progress + '%'"></div>
                </div>
                <div class="mt-4">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-brand"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Channel Integrity</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 border border-gray-100 rounded-sm">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Email Coverage</p>
                                <p class="text-xl font-black text-surface-900" x-text="Math.round((stats.global_valid / stats.full_total) * 100) + '%'"></p>
                            </div>
                            <div class="p-4 border border-gray-100 rounded-sm">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">WhatsApp Coverage</p>
                                <p class="text-xl font-black text-emerald-500" x-text="Math.round((stats.whatsapp_count / stats.full_total) * 100) + '%'"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex justify-between text-[8px] font-black text-blue-400 uppercase tracking-widest">
                    <span x-text="`Processed: ${stats.import_details?.processed || 0} rows`"></span>
                    <span x-text="`Valid: ${stats.import_details?.valid || 0} • Dup: ${stats.import_details?.duplicate || 0} • Invalid: ${stats.import_details?.invalid || 0}`"></span>
                </div>
            </div>

            {{-- Metrics Summary --}}
            <div class="px-1 py-1">
                <div x-show="archived === 'no'" class="flex items-center gap-2">
                    <p class="text-xl font-bold text-surface-500 tracking-tight flex items-center gap-x-2">
                        {{-- Primary Number based on Health Filter --}}
                        <span>
                            <template x-if="filter === 'all'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                </span>
                            </template>
                            <template x-if="filter === 'valid'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-emerald-500 font-black" x-text="stats.global_valid.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">clean contacts.</span>
                                </span>
                            </template>
                            <template x-if="filter === 'risky'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-amber-500 font-black" x-text="stats.risky.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">risky contacts.</span>
                                </span>
                            </template>
                            <template x-if="filter === 'role_based'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-blue-500 font-black" x-text="stats.role_based.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">role-based emails.</span>
                                </span>
                            </template>
                            <template x-if="filter === 'disposable'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-indigo-500 font-black" x-text="stats.disposable.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">temporary emails.</span>
                                </span>
                            </template>
                            <template x-if="filter === 'suspicious'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-indigo-500 font-black" x-text="stats.suspicious.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">suspicious emails.</span>
                                </span>
                            </template>
                            <template x-if="subscription === 'hard_bounce'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-red-500 font-black" x-text="stats.hard_bounce.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">hard bounce emails.</span>
                                </span>
                            </template>
                            <template x-if="subscription === 'soft_bounce'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-amber-500 font-black" x-text="stats.soft_bounce.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">soft bounce emails.</span>
                                </span>
                            </template>
                            <template x-if="subscription === 'complaint'">
                                <span>
                                    <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">total contacts.</span>
                                    <span class="text-surface-900 font-black" x-text="stats.complaints.toLocaleString()"></span> 
                                    <span class="text-surface-600 font-medium">spam complaints.</span>
                                </span>
                            </template>


                            <template x-if="filter === 'invalid'">
                                <div class="flex items-center gap-4">
                                    <span>
                                        <span class="text-brand font-black" x-text="stats.full_total.toLocaleString()"></span> 
                                        <span class="text-surface-600 font-medium">total contacts.</span>
                                        <span class="text-red-500 font-black" x-text="stats.global_invalid.toLocaleString()"></span> 
                                        <span class="text-surface-600 font-medium">invalid contacts.</span>
                                    </span>
                                    <a href="{{ route('admin.email-lists.fix-invalid', $emailList) }}" 
                                       class="bg-red-500 hover:bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-sm flex items-center gap-2 transition-all active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Fix Invalid Records
                                    </a>
                                </div>
                            </template>
                        </span>

                        {{-- Secondary Metrics (Always visible if applicable) --}}
                        <div class="flex items-center gap-4 text-xl">
                            <template x-if="stats.subscribed > 0">
                                <span>
                                    <span class="text-emerald-500 font-black" x-text="stats.subscribed.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Subscribers</span>
                                </span>
                            </template>
                            <template x-if="stats.unsubscribed > 0">
                                <span>
                                    <span class="text-gray-500 font-black" x-text="stats.unsubscribed.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Unsubscribed</span>
                                </span>
                            </template>
                            <template x-if="stats.bounced > 0">
                                <span>
                                    <span class="text-amber-500 font-black" x-text="stats.bounced.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Bounce (Any)</span>
                                </span>
                            </template>
                            @if($emailList->isEmailList() || $emailList->isDualList())
                            <template x-if="stats.hard_bounce > 0">
                                <span>
                                    <span class="text-red-500 font-black" x-text="stats.hard_bounce.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Hard Bounce</span>
                                </span>
                            </template>
                            <template x-if="stats.soft_bounce > 0">
                                <span>
                                    <span class="text-amber-500 font-black" x-text="stats.soft_bounce.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Soft Bounce</span>
                                </span>
                            </template>
                            @endif
                            <template x-if="stats.complaints > 0">
                                <span>
                                    <span class="text-surface-900 font-black" x-text="stats.complaints.toLocaleString()"></span>
                                    <span class="text-surface-600 font-medium">Spam Complaints</span>
                                </span>
                            </template>
                        </div>
                    </p>
                </div>
                <div x-show="archived === 'yes'" x-cloak>
                    <p class="text-xl font-bold text-surface-500 tracking-tight">
                        <span class="text-brand font-black" x-text="stats.archived.toLocaleString()"></span> contacts <span class="text-surface-400 font-medium tracking-tight">Archived.</span>
                    </p>
                </div>
            </div>
            {{-- Premium Inline Bulk Action Toolbar --}}
            <div x-show="selectedIds.length > 0 || globalSelect" x-transition x-cloak
                class="mb-6 flex items-center justify-between bg-surface-900 p-4 rounded-sm text-white border overflow-hidden animate-in fade-in slide-in-from-top-4">

                {{-- Left: Selection Info --}}
                <div class="flex items-center gap-6 pl-2">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="text-xl font-black leading-none tracking-tight"
                                x-text="globalSelect ? (archived === 'yes' ? stats.archived.toLocaleString() : stats.total.toLocaleString()) : selectedIds.length"></span>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-white/40 mt-1">Contacts Selected</span>
                        </div>
                    </div>

                    <div class="h-10 w-px bg-white/10"></div>

                    {{-- Actions Container --}}
                    <div class="flex items-center gap-1">
                        <button @click="bulkAction('unsubscribe')"
                            class="flex items-center gap-2 px-4 py-2.5 hover:bg-white/5 text-[10px] font-black uppercase tracking-widest text-white/80 hover:text-white transition-all cursor-pointer rounded-sm group">
                            <svg class="w-4 h-4 text-white/20 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Unsubscribe
                        </button>
                        
                        <button @click="bulkAction(archived === 'yes' ? 'unarchive' : 'archive')"
                            class="flex items-center gap-2 px-4 py-2.5 hover:bg-white/5 text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer rounded-sm group"
                            :class="archived === 'yes' ? 'text-emerald-400 hover:text-emerald-300' : 'text-blue-400 hover:text-blue-300'">
                            <svg class="w-4 h-4 opacity-40 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="archived === 'yes' ? 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' : 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'" />
                            </svg>
                            <span x-text="archived === 'yes' ? 'Restore to Active' : 'Move to Archive'"></span>
                        </button>

                        <div class="w-px h-4 bg-white/10 mx-2"></div>

                        <button @click="bulkAction('permanent_delete')"
                            class="flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer rounded-sm shadow-lg hover:scale-[1.02] active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Permanently
                        </button>
                    </div>
                </div>

                {{-- Right: Clear Action --}}
                <div class="flex items-center gap-3 pr-2">
                    <button @click="selectedIds = []; globalSelect = false"
                        class="flex items-center gap-2 px-4 py-2 hover:bg-white/20 rounded-sm text-white transition-all cursor-pointer group">
                        <span class="text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer group">Clear Selection</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Contact Data Grid --}}
            <div class="bg-white border border-gray-100 rounded-sm overflow-hidden min-h-[600px]">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-50 border-b border-color text-xs uppercase tracking-widest text-surface-500">
                                <th class="px-8 py-4 sticky left-0 bg-white z-10">
                                    <div class="flex items-center gap-1" x-data="{ open: false }">
                                        <input type="checkbox" 
                                               @change="toggleSelectAll($event.target.checked)" 
                                               :checked="selectedIds.length > 0"
                                               class="w-4 h-4 rounded-sm border-gray-200 text-brand focus:ring-brand cursor-pointer">
                                        <div class="relative">
                                            <button @click="open = !open" class="p-1 hover:bg-gray-100 rounded-sm transition-colors cursor-pointer">
                                                <svg class="w-2.5 h-2.5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak class="absolute left-0 top-0 w-40 mt-3 bg-white border border-gray-200 rounded-sm z-70 py-1">
                                                <button @click="selectVisible(); open = false" class="w-full text-left px-3 py-2 text-xs font-black text-surface-700 hover:bg-gray-50 transition-colors uppercase tracking-widest">Select visible</button>
                                                <button @click="selectAll(); open = false" class="w-full text-left px-3 py-2 text-xs font-black text-surface-700 hover:bg-gray-50 transition-colors uppercase tracking-widest border-t border-gray-50">Select all</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-8 py-4">Email Address</th>
                                <th class="px-8 py-4">WhatsApp</th>

                                <th class="px-8 py-4">Full Name</th>
                                @foreach($displayedFields as $field)
                                    <th class="px-8 py-4">{{ str_replace(['_', 'custom_'], [' ', ''], $field) }}</th>
                                @endforeach
                                <th class="px-8 py-4 text-center">Segment</th>
                                <th class="px-8 py-4 text-center">Tag</th>
                                <th class="px-8 py-4 text-center">Health</th>
                                <th class="px-8 py-4 text-center">Status</th>
                                <th class="px-8 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="email-table-body" class="divide-y divide-gray-50">
                            @include('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])
                        </tbody>
                    </table>
                </div>

                <div class="px-8 py-6 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between" id="pagination-links">
                    <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest">
                        Showing <span x-text="stats.total > 0 ? '1-50' : '0'"></span> of <span x-text="stats.total"></span> Entries
                    </div>
                    {{ $emails->links() }}
                </div>
            </div>
        </div>

        {{-- ── HISTORY & LOGS VIEW ── --}}
        <div x-show="activeTab === 'logs'" x-cloak class="space-y-4 animate-slide-up">
            {{-- Activity History List (Mailchimp inspired) --}}
            <div class="bg-white rounded-sm">
                <div class="pb-6 border-b border-color">
                    <h3 class="text-lg font-black text-surface-900 tracking-tight">Audit Log</h3>
                    <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-1">Review your recent list activity and imports</p>
                </div>

                <div class="divide-y divide-gray-50">
                    @forelse($emailList->activityLogs as $log)
                        <div class="py-8 border-b border-color hover:bg-gray-50/30 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="space-y-4 flex-1">
                                    {{-- Header --}}
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-black text-surface-900">{{ ($log->details['status'] ?? '') === 'completed' ? 'Complete' : 'Activity' }}</span>
                                        <span class="text-xs text-surface-400 font-bold">{{ $log->created_at->format('M d, Y • g:i A') }}</span>
                                        @if($log->type === 'import')
                                            <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-sm uppercase tracking-widest">Import</span>
                                        @endif
                                    </div>

                                    {{-- Main Content --}}
                                    <div class="space-y-3">
                                        @if($log->type === 'import')
                                            <div class="flex flex-col gap-1.5">
                                                <p class="text-sm font-bold text-surface-700">
                                                    Import session: <span class="text-surface-900">{{ $log->details['source'] ?? 'File Upload' }}</span>
                                                    @if(!empty($log->details['tags'])) • <span class="text-brand font-black uppercase text-[9px]">{{ $log->details['tags'] }}</span> @endif
                                                </p>

                                                <div class="flex flex-wrap gap-x-6 gap-y-4 mt-2">
                                                    @if(isset($log->details['processed']))
                                                        <div class="flex items-center gap-2 text-xs font-bold text-surface-900">
                                                            <div class="w-5 h-5 rounded-full bg-surface-100 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['processed']) }} total rows</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['valid']))
                                                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-600">
                                                            <div class="w-5 h-5 rounded-full bg-emerald-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['valid']) }} clean</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['risky']) && $log->details['risky'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-amber-600">
                                                            <div class="w-5 h-5 rounded-full bg-amber-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['risky']) }} risky</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['duplicate']) && $log->details['duplicate'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-surface-400">
                                                            <div class="w-5 h-5 rounded-full bg-surface-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['duplicate']) }} duplicates</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['invalid']) && $log->details['invalid'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-red-600">
                                                            <div class="w-5 h-5 rounded-full bg-red-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['invalid']) }} invalid/syntax errors</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['role_based']) && $log->details['role_based'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-blue-600">
                                                            <div class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['role_based']) }} role-based</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['disposable']) && $log->details['disposable'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-indigo-600">
                                                            <div class="w-5 h-5 rounded-full bg-indigo-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['disposable']) }} disposable</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['catch_all']) && $log->details['catch_all'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-fuchsia-600">
                                                            <div class="w-5 h-5 rounded-full bg-fuchsia-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['catch_all']) }} catch-all</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['typo']) && $log->details['typo'] > 0)
                                                        <div class="flex items-center gap-2 text-xs font-bold text-rose-600">
                                                            <div class="w-5 h-5 rounded-full bg-rose-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['typo']) }} typos</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if(!empty($log->details['reasons']))
                                                    <div class="mt-2 ml-7 text-[9px] text-red-400 font-bold uppercase tracking-widest leading-relaxed max-w-lg">
                                                        Errors: {{ collect($log->details['reasons'])->map(fn($v, $k) => "$k ($v)")->join(' • ') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($log->type === 'export')
                                            <p class="text-sm font-bold text-surface-700">Exported dataset: <span class="text-surface-900">{{ $log->details['filename'] }}</span></p>
                                        @elseif($log->type === 'bulk_action')
                                            <p class="text-sm font-bold text-surface-700">
                                                Bulk action: <span class="text-brand uppercase font-black tracking-tight">{{ str_replace('_', ' ', $log->details['action']) }}</span> on {{ number_format($log->details['count']) }} records
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-3">
                                    @if(isset($log->details['undone']))
                                        <span class="px-3 py-1 bg-gray-100 text-gray-400 text-[10px] font-black uppercase tracking-widest rounded-sm">Import Reverted</span>
                                    @else
                                        <span class="inline-flex items-center gap-2 text-[10px] font-black {{ ($log->details['status'] ?? '') === 'started' ? 'text-blue-600' : 'text-emerald-600' }} uppercase tracking-widest">
                                            <span class="w-2 h-2 {{ ($log->details['status'] ?? '') === 'started' ? 'bg-blue-500 animate-pulse' : 'bg-emerald-500' }} rounded-full"></span>
                                            {{ $log->details['status'] ?? 'Completed' }}
                                        </span>

                                        @if($log->type === 'import' && ($log->details['status'] ?? '') !== 'started')
                                            <form action="{{ route('admin.email-lists.undo-import', [$emailList, $log->id]) }}" method="POST" onsubmit="return confirm('UNDO IMPORT: This will delete all contacts added in this session. Proceed?')">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 border border-red-100 text-red-500 hover:bg-red-50 text-[10px] font-black uppercase tracking-widest rounded-sm transition-colors cursor-pointer">Undo Import</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-20 text-center text-surface-400 italic text-sm">No historical data available.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Teleported Modals ── --}}
        <template x-teleport="body">
            <div>
                {{-- Export Modal --}}
                <div x-show="showExportModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/90" x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-lg overflow-hidden animate-scale-in" @click.away="showExportModal = false">
                        <div class="p-8 border-b border-gray-100 bg-surface-50/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-black text-surface-900 tracking-tight">Export Audience</h3>
                                    <p class="text-[10px] text-surface-400 font-black uppercase mt-1 tracking-widest">Generate filtered data snapshot</p>
                                </div>
                                <button @click="showExportModal = false" class="text-surface-400 hover:text-surface-600 transition-colors cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-8 space-y-8">
                            <div>
                                <label class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Custom Filename</label>
                                <div class="relative">
                                    <input type="text" x-model="exportFilename" class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-surface-300 uppercase" x-text="`.${exportFormat}`"></div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Select Format</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button @click="exportFormat = 'xlsx'" :class="exportFormat === 'xlsx' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                        <div class="flex flex-col items-center gap-3">
                                            <div :class="exportFormat === 'xlsx' ? 'bg-brand/10' : 'bg-gray-50'" class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Excel / XLSX</p>
                                        </div>
                                    </button>
                                    <button @click="exportFormat = 'csv'" :class="exportFormat === 'csv' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                        <div class="flex flex-col items-center gap-3">
                                            <div :class="exportFormat === 'csv' ? 'bg-brand/10' : 'bg-gray-50'" class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m2 0h1m-3 4h1m2 0h1m-3 4h1m2 0h1"></path></svg>
                                            </div>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Text / CSV</p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button @click="showExportModal = false" class="flex-1 py-3.5 text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-600 transition-colors cursor-pointer">Cancel</button>
                            <button @click="triggerExport()" class="flex-2 bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm transition-all hover:scale-[1.01]">Download File</button>
                        </div>
                    </div>
                </div>

                {{-- Import Modal --}}
                <div x-show="showImportMoreModal" 
                     class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/80" 
                     x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-xl overflow-hidden shadow-xl" 
                         @click.away="showImportMoreModal = false">

                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-surface-900 tracking-tight">Expand Audience</h3>
                                <p class="text-[10px] text-surface-400 font-bold uppercase mt-1 tracking-widest">Select import method</p>
                            </div>
                            <button @click="showImportMoreModal = false" class="text-surface-400 hover:text-surface-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6" x-data="{ type: 'upload' }">
                            <div class="grid grid-cols-3 gap-2 mb-6">
                                <button @click="type = 'upload'" :class="type === 'upload' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="text-[9px] font-black uppercase">CSV/Excel</p>
                                    </div>
                                </button>

                                <button @click="type = 'paste'" :class="type === 'paste' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                        <p class="text-[9px] font-black uppercase">Paste</p>
                                    </div>
                                </button>

                                <button @click="type = 'single'" :class="type === 'single' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        <p class="text-[9px] font-black uppercase">Manual</p>
                                    </div>
                                </button>
                            </div>

                            <div class="space-y-6">
                                <form x-show="type !== 'single'" action="{{ route('admin.email-lists.import-more', $emailList) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    <input type="hidden" name="import_type" :value="type">

                                    <div>
                                        <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Internal Tags</label>
                                        <input type="text" name="tags" value="{{ $emailList->tags }}" class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand">
                                        <input type="hidden" name="signup_source" value="import">
                                    </div>

                                    <div x-show="type === 'upload'">
                                        <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Select Dataset (CSV/Excel)</label>
                                        <div class="relative" 
                                             x-data="{ dragging: false, fileName: '' }" 
                                             @dragover.prevent="dragging = true" 
                                             @dragleave.prevent="dragging = false" 
                                             @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0].name">

                                            <label :class="dragging ? 'border-brand bg-brand/5' : 'border-gray-200 bg-white'" 
                                                   class="flex flex-col items-center justify-center w-full min-h-[120px] border-2 border-dashed rounded-sm cursor-pointer hover:bg-gray-50 transition-all group">

                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg :class="dragging ? 'text-brand' : 'text-surface-300 group-hover:text-brand'" class="w-10 h-10 mb-3 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    <p class="mb-1 text-xs font-black text-surface-700 uppercase tracking-widest" x-text="fileName || 'Click or Drag & Drop'"></p>
                                                    <p class="text-[9px] text-surface-400 font-bold uppercase tracking-widest" x-show="!fileName">CSV, XLSX or TXT (Max 10MB)</p>
                                                </div>

                                                <input type="file" name="file" x-ref="fileInput" class="hidden" accept=".csv,.xlsx,.txt" @change="fileName = $el.files[0].name">
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="type === 'paste'">
                                        <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Email List</label>
                                        <textarea name="emails_text" rows="4" class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-xs font-mono" placeholder="email@domain.com"></textarea>
                                    </div>

                                    <button type="submit" 
                                            class="w-full bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                            x-data="{ loading: false }"
                                            @click="setTimeout(() => loading = true, 50)"
                                            :disabled="loading">
                                        <span x-show="!loading">Start Import</span>
                                        <span x-show="loading" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Initializing...
                                        </span>
                                    </button>
                                </form>

                                <form x-show="type === 'single'" @submit.prevent="addManualContact()" class="space-y-6">
                                    <div class="space-y-4">
                                        <input type="email" x-model="newContact.email" placeholder="Email Address" class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold" required>
                                        <input type="text" x-model="newContact.name" placeholder="Full Name" class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold">
                                        <input type="text" x-model="newContact.whatsapp_number" placeholder="WhatsApp Number (e.g. 919876543210)" class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold">
                                    </div>
                                    <button type="submit" class="w-full bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm cursor-pointer" :disabled="adding">
                                        <span x-show="!adding">Register Contact</span>
                                        <span x-show="adding">Processing...</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>


    </div>

    <script>
    function emailListView() {
        return {
            filter: 'all', segment: 'all', tag: 'all', source: 'all', archived: 'no', subscription: 'all',
            search: '', searchField: 'all', selectedIds: [], activeTab: 'contacts', globalSelect: false,
            showSearchOptions: false, showEditModal: false, showImportMoreModal: false, showExportModal: false,
            exportFormat: 'xlsx', exportFilename: '{{ Str::slug($emailList->name) }}_export_{{ now()->format('Ymd') }}',
            adding: false, saving: false, scrubbing: false, importJustCompleted: false,
            newContact: { email: '', name: '', whatsapp_number: '', segment_name: '', tags: '', signup_source: 'Manual Entry' },
            editingContact: { id: null, email: '', name: '', subscription_status: '', meta: {} },
            stats: { 
                full_total: {{ $stats['total'] }},
                global_valid: {{ $stats['valid'] }}, 
                global_invalid: {{ $stats['invalid'] }}, 
                global_duplicate: {{ $stats['duplicate'] }}, 
                risky: {{ $stats['risky'] ?? 0 }},
                disposable: {{ $stats['disposable'] ?? 0 }},
                role_based: {{ $stats['role_based'] ?? 0 }},
                suspicious: {{ $stats['suspicious'] ?? 0 }},
                hard_bounce: {{ $stats['hard_bounce'] ?? 0 }},
                soft_bounce: {{ $stats['soft_bounce'] ?? 0 }},
                complaints: {{ $stats['complaints'] ?? 0 }},
                global_segment: 0,
                global_tag: 0,
                global_source: 0,
                total: {{ $stats['total'] }}, 
                valid: {{ $stats['valid'] }}, 
                invalid: {{ $stats['invalid'] }}, 
                duplicate: {{ $stats['duplicate'] }}, 
                subscribed: {{ $stats['subscribed'] }}, 
                unsubscribed: {{ $stats['unsubscribed'] ?? 0 }},
                bounced: {{ $stats['bounced'] ?? 0 }},
                archived: {{ $stats['archived'] ?? 0 }}, 
                status: '{{ $emailList->status }}' 
            },

            init() {
                if (this.stats.status === 'processing') {
                    this.pollStatus();
                }
            },

            resetFilters() {
                this.filter = 'all'; this.segment = 'all'; this.tag = 'all'; this.source = 'all'; this.archived = 'no'; this.subscription = 'all';
                this.search = ''; this.searchField = 'all'; this.selectedIds = []; this.fetchEmails();
            },

            toggleSelectAll(checked) {
                this.selectedIds = checked ? this.getCurrentPageIds() : [];
                this.globalSelect = false;
            },

            selectVisible() {
                this.selectedIds = this.getCurrentPageIds();
                this.globalSelect = false;
            },

            selectAll() {
                this.selectedIds = this.getCurrentPageIds();
                this.globalSelect = true;
            },

            getCurrentPageIds() {
                const rows = document.querySelectorAll('tbody input[type="checkbox"]');
                return Array.from(rows).map(cb => parseInt(cb.value)).filter(id => !isNaN(id));
            },

            bulkAction(action) {
                const count = this.globalSelect 
                    ? (this.archived === 'yes' ? this.stats.archived : this.stats.total) 
                    : this.selectedIds.length;

                if (!count) return;

                // Strict confirmation for permanent delete
                if (action === 'permanent_delete') {
                    const confirmation = prompt(`CRITICAL ACTION: This will PERMANENTLY DELETE ${count.toLocaleString()} contacts from the database. This CANNOT be undone.\n\nType "PERMANENT DELETE" to confirm:`);
                    if (confirmation !== 'PERMANENT DELETE') {
                        alert('Action cancelled. Confirmation text did not match.');
                        return;
                    }
                } else {
                    if (!confirm(`Proceed with ${action} for ${count.toLocaleString()} contacts?`)) return;
                }

                fetch(`{{ route('admin.email-lists.bulk-action', $emailList) }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ 
                        ids: this.selectedIds, 
                        action: action,
                        global: this.globalSelect,
                        filters: {
                            status: this.filter, search: this.search, search_field: this.searchField, 
                            segment: this.segment, tag: this.tag, source: this.source, 
                            archived: this.archived, subscription: this.subscription 
                        }
                    })
                }).then(() => { 
                    this.selectedIds = []; 
                    this.globalSelect = false; 
                    this.fetchEmails(); 
                    this.refreshStats(); 
                });
            },

            fetchEmails() {
                fetch('{{ route("admin.email-lists.filter", $emailList) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ 
                        status: this.filter, search: this.search, search_field: this.searchField, 
                        segment: this.segment, tag: this.tag, source: this.source, 
                        archived: this.archived, subscription: this.subscription 
                    })
                }).then(r => r.json()).then(data => {
                    document.getElementById('email-table-body').innerHTML = data.html;
                    document.getElementById('pagination-links').innerHTML = data.links;

                    // Update dynamic stats
                    if (data.stats) {
                        this.stats.total = data.stats.total;
                        this.stats.valid = data.stats.valid;
                        this.stats.invalid = data.stats.invalid;
                        this.stats.duplicate = data.stats.duplicate;
                        this.stats.subscribed = data.stats.subscribed;
                        this.stats.unsubscribed = data.stats.unsubscribed;
                        this.stats.bounced = data.stats.bounced;
                    }
                    if (data.global_stats) {
                        this.stats.global_valid = data.global_stats.valid;
                        this.stats.global_invalid = data.global_stats.invalid;
                        this.stats.global_duplicate = data.global_stats.duplicate;
                        this.stats.global_segment = data.global_stats.segment;
                        this.stats.global_tag = data.global_stats.tag;
                        this.stats.global_source = data.global_stats.source;
                    }
                });
            },

            triggerExport() {
                const url = new URL(`{{ route('admin.email-lists.export', $emailList) }}`);
                url.searchParams.set('format', this.exportFormat); url.searchParams.set('filename', this.exportFilename);
                url.searchParams.set('status', this.filter); url.searchParams.set('search', this.search);
                url.searchParams.set('segment', this.segment); url.searchParams.set('tag', this.tag);
                url.searchParams.set('source', this.source); url.searchParams.set('archived', this.archived);
                window.location.href = url.toString(); this.showExportModal = false;
            },

            addManualContact() {
                this.adding = true;
                fetch('{{ route("admin.email-lists.add-contact", $emailList) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.newContact)
                }).then(() => { this.adding = false; this.showImportMoreModal = false; this.fetchEmails(); this.refreshStats(); });
            },

            refreshStats() {
                fetch('{{ route("admin.email-lists.status", $emailList) }}').then(r => r.json()).then(data => {
                    this.stats = { 
                        status: data.status,
                        full_total: data.total_records,
                        total: data.total_records, 
                        valid: data.valid_count, 
                        invalid: data.invalid_count, 
                        duplicate: data.duplicate_count, 
                        subscribed: data.subscribed_count || 0,
                        unsubscribed: data.unsubscribed_count || 0,
                        bounced: data.bounced_count || 0,
                        hard_bounce: data.hard_bounce_count || 0,
                        soft_bounce: data.soft_bounce_count || 0,
                        complaints: data.complaint_count || 0,
                        risky: data.risky_count || 0,
                        disposable: data.disposable_count || 0,
                        role_based: data.role_based_count || 0,
                        suspicious: data.suspicious_count || 0,
                        archived: data.archived_count || 0,
                        import_progress: data.import_progress,
                        import_details: data.import_details
                    };
                });
            },

            pollStatus() {
                const interval = setInterval(() => {
                    fetch('{{ route("admin.email-lists.status", $emailList) }}').then(r => r.json()).then(data => {
                        const oldStatus = this.stats.status;
                        this.stats.status = data.status;
                        this.stats.full_total = data.total_records;
                        this.stats.total = data.total_records;
                        this.stats.global_valid = data.valid_count;
                        this.stats.valid = data.valid_count;
                        this.stats.global_invalid = data.invalid_count;
                        this.stats.invalid = data.invalid_count;
                        this.stats.global_duplicate = data.duplicate_count;
                        this.stats.duplicate = data.duplicate_count;
                        this.stats.subscribed = data.subscribed_count;
                        this.stats.unsubscribed = data.unsubscribed_count;
                        this.stats.bounced = data.bounced_count;
                        this.stats.hard_bounce = data.hard_bounce_count;
                        this.stats.soft_bounce = data.soft_bounce_count;
                        this.stats.complaints = data.complaint_count;
                        this.stats.risky = data.risky_count;
                        this.stats.disposable = data.disposable_count;
                        this.stats.role_based = data.role_based_count;
                        this.stats.suspicious = data.suspicious_count;
                        this.stats.archived = data.archived_count;
                        this.stats.import_progress = data.import_progress;
                        this.stats.import_details = data.import_details;

                        if (data.status === 'completed' || data.status === 'failed') { 
                            clearInterval(interval); 
                            this.fetchEmails();
                            if (oldStatus === 'processing' && data.status === 'completed') {
                                this.importJustCompleted = true;
                            }
                        }
                    });
                }, 2000);
            },


        };
    }
    </script>
@endsection
