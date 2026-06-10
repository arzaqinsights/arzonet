@extends('layouts.app')
@section('title', $emailList->name)
@php
    $switcherQuery = \App\Models\EmailList::query();
    if (app()->has('team_user')) {
        $teamUserId = app('team_user')->id;
        $switcherQuery->where(function ($q) use ($teamUserId) {
            $q->where('is_public', true)
                ->orWhere('created_by_id', $teamUserId);
        });
    }
    $switcherLists = $switcherQuery->orderBy('name')->get();
    $destinationLists = $switcherLists->where('id', '!=', $emailList->id);

    $pipelinesQuery = \App\Models\Pipeline::with('stages');
    if (app()->has('team_user')) {
        $teamUserId = app('team_user')->id;
        $pipelinesQuery->where(function ($q) use ($teamUserId) {
            $q->where('is_public', true)
                ->orWhere('created_by_id', $teamUserId);
        });
    }
    $pipelines = $pipelinesQuery->orderBy('name')->get();
@endphp
@section('heading', 'Contacts')

@section('header-actions')
    <div class="flex items-center gap-3" x-data>
        @if(!app()->has('team_user') || $emailList->created_by_id === app('team_user')->id)
            <form action="{{ route('admin.email-lists.destroy', $emailList) }}" method="POST"
                onsubmit="return confirm('WARNING: Deleting this list will permanently delete all its contacts. This action cannot be undone. Are you sure you want to delete this list?')"
                class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-3 flex items-center rounded-sm bg-red-500 hover:bg-red-650 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete List
                </button>
            </form>
        @endif
        @if($emailList->canPerformAction('edit_contact'))
            <button @click="$dispatch('open-merge-modal')"
                class="px-4 py-3 flex items-center rounded-sm bg-surface-100 hover:bg-surface-200 text-surface-700 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Merge Duplicates
            </button>
        @endif
        <button @click="$dispatch('open-export-modal')"
            class="px-4 py-3 flex items-center rounded-sm bg-white border border-gray-100 text-surface-600 hover:text-surface-900 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Export Contacts
        </button>
        @if($emailList->canPerformAction('add_contact'))
            <button @click="$dispatch('open-import-more')"
                class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Import Contacts
            </button>
        @endif
    </div>
@endsection

@section('content')
    <div class="space-y-4 animate-slide-up" x-data="emailListView()"
        @keydown.escape.window="showEditModal = false; showImportMoreModal = false; showExportModal = false; showTransferModal = false; showSendPipelineModal = false"
        @open-import-more.window="showImportMoreModal = true" @open-export-modal.window="showExportModal = true"
        @open-single-permanent-delete.window="openPermanentDeleteModal($event.detail.id)"
        @open-permanent-delete.window="openPermanentDeleteModal()" @archive-email.window="archiveEmail($event.detail.id)"
        @unarchive-email.window="unarchiveEmail($event.detail.id)" @open-add-contact.window="openAddContact($event.detail)"
        @open-transfer-contact.window="openTransferContact($event.detail.contact)"
        @open-send-pipeline.window="openSendPipeline($event.detail.contact)"
        @open-profile.window="openProfile($event.detail)"
        @open-merge-modal.window="showMergeModal = true"
        x-init="@if($emailList->status === 'processing') pollStatus() @endif">

        {{-- Tabs Navigation --}}
        <div class="bg-white border-b border-color flex items-center gap-8 -mx-6 -mt-6 mb-6 px-6">
            <button @click="activeTab = 'contacts'"
                :class="activeTab === 'contacts' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
                class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none cursor-pointer">
                Contacts
            </button>
            <button @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
                class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none flex items-center gap-2 cursor-pointer">
                History & Logs
                <span
                    class="px-1.5 py-0.5 bg-surface-200 text-surface-700 rounded-full text-[10px]">{{ $emailList->activityLogs()->count() }}</span>
            </button>
            <button @click="activeTab = 'forms'"
                :class="activeTab === 'forms' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
                class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none cursor-pointer">
                Signup Forms
            </button>
            <button @click="activeTab = 'opt_out'"
                :class="activeTab === 'opt_out' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
                class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none cursor-pointer">
                Opt-Out Analytics
            </button>
        </div>

        @php
            $mapping = $emailList->column_mapping ?? [];
            $displayedFields = [];
            foreach (['company', 'job_title', 'phone', 'city', 'country'] as $field) {
                if (isset($mapping[$field]))
                    $displayedFields[] = $field;
            }
            // Auto-display phone if whatsapp_number is mapped, so that landlines/extra numbers extracted are visible
            if (isset($mapping['whatsapp_number']) && !in_array('phone', $displayedFields)) {
                $displayedFields[] = 'phone';
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
            @php
                $healthOptions = [
                    'valid' => 'Clean / Valid',
                    'suspicious' => 'Suspicious',
                    'role_based' => 'Role-Based',
                    'disposable' => 'Disposable',
                    'invalid' => 'Invalid/Broken',
                    'cross_duplicate' => 'Cross-List Duplicates'
                ];
                
                $segmentOptions = [];
                foreach($segments as $s) { $segmentOptions[$s] = $s; }

                $tagOptions = [];
                foreach($tags as $t) { $tagOptions[$t] = $t; }

                $topicOptions = [];
                if(isset($topics)) { foreach($topics as $t) { $topicOptions[$t->id] = $t->name; } }

                $subscriptionOptions = [
                    'subscribed' => 'Subscribed',
                    'unsubscribed' => 'Unsubscribed',
                    'bounced' => 'Bounced (Any)',
                    'hard_bounce' => 'Hard Bounces',
                    'soft_bounce' => 'Soft Bounces',
                    'complaint' => 'Spam Complaints'
                ];

                $channelOptions = [
                    'only_email' => 'Only Email',
                    'only_whatsapp' => 'Only WhatsApp'
                ];

                $waStatusOptions = [
                    'subscribed' => 'Subscribed',
                    'unsubscribed' => 'Opt-out'
                ];

                $viewOptions = [
                    'no' => 'Active Only',
                    'yes' => 'Archived Only'
                ];
            @endphp
            <div class="mb-8 space-y-3" x-data="{
                filterNames: {
                    filter: @js($healthOptions),
                    segment: @js($segmentOptions),
                    tag: @js($tagOptions),
                    topic: @js($topicOptions),
                    subscription: @js($subscriptionOptions),
                    channel: @js($channelOptions),
                    wa_status: @js($waStatusOptions),
                    archived: @js($viewOptions),
                    added_by: @js($addedByOptions ?? [])
                }
            }">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Search Input --}}
                    <div class="relative w-76 shrink-0 group" x-data="{
                        showSearchFields:false,
                        searchField:'name'
                    }">
                        <!-- Search Icon -->
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-surface-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Search Input -->
                        <input type="text" x-model="search" @input.debounce.150ms="fetchEmails()"
                            class="w-full pl-9 pr-24 py-2 bg-white border border-gray-200 hover:border-gray-300 rounded-sm text-sm placeholder:text-surface-500 focus:bg-white focus:border-brand focus:ring-0 focus:outline-none transition-all"
                            placeholder="Search contacts...">

                        <!-- Search Field Button -->
                        <div class="absolute inset-y-0 right-1 flex items-center">
                            <button type="button" @click.stop="showSearchFields = !showSearchFields"
                                class="flex items-center gap-1 px-1 py-1.5 text-xs uppercase text-brand rounded-sm font-semibold transition-colors">
                                <span x-text="
                                    searchField === 'name' ? 'Name' :
                                    searchField === 'email' ? 'Email' :
                                    searchField === 'whatsapp_number' ? 'WhatsApp' :
                                    searchField
                                "></span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Popup -->
                        <div x-show="showSearchFields" x-transition x-cloak @click.outside="showSearchFields = false"
                            class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-sm shadow-xl z-50 p-1">

                            <button @click="searchField='name'; showSearchFields=false; fetchEmails();" 
                                :class="searchField==='name' ? 'bg-brand text-white' : 'hover:bg-gray-50 text-surface-700'"
                                class="w-full text-left px-3 py-2 rounded-sm text-xs font-semibold">Name</button>

                            <button @click="searchField='email'; showSearchFields=false; fetchEmails();" 
                                :class="searchField==='email' ? 'bg-brand text-white' : 'hover:bg-gray-50 text-surface-700'"
                                class="w-full text-left px-3 py-2 rounded-sm text-xs font-semibold">Email</button>

                            <button @click="searchField='whatsapp_number'; showSearchFields=false; fetchEmails();" 
                                :class="searchField==='whatsapp_number' ? 'bg-brand text-white' : 'hover:bg-gray-50 text-surface-700'"
                                class="w-full text-left px-3 py-2 rounded-sm text-xs font-semibold">WhatsApp Number</button>

                            @foreach($displayedFields as $field)
                                <button @click="searchField='{{ $field }}'; showSearchFields=false; fetchEmails();" 
                                    :class="searchField==='{{ $field }}' ? 'bg-brand text-white' : 'hover:bg-gray-50 text-surface-700'"
                                    class="w-full text-left px-3 py-2 rounded-sm text-xs font-semibold">
                                    {{ ucwords(str_replace('_', ' ', preg_replace('/^custom_/', '', $field))) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <x-multi-select label="Email Health" model="filter" :options="$healthOptions" />
                    <x-multi-select label="Segment" model="segment" :options="$segmentOptions" />
                    <x-multi-select label="Tag" model="tag" :options="$tagOptions" />
                    @if(isset($topics) && $topics->isNotEmpty())
                        <x-multi-select label="Subscription" model="topic" :options="$topicOptions" />
                    @endif
                    @if(isset($addedByOptions) && count($addedByOptions) > 0)
                        <x-multi-select label="Added By" model="added_by" :options="$addedByOptions" />
                    @endif

                    {{-- Advanced Filters Modal Trigger --}}
                    <div>
                        <button type="button" @click="showAdvancedFilterModal = true" class="flex items-center gap-2 px-4 cursor-pointer transition-colors">
                            <span class="text-xs text-surface-900 tracking-widest flex items-center gap-2">
                                Dynamic Rules
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                            </span>
                            <template x-if="advancedRules.length > 0">
                                <span class="w-4 h-4 flex items-center justify-center rounded-full bg-brand text-white text-[9px] font-black" x-text="advancedRules.length"></span>
                            </template>
                        </button>
                    </div>
                </div>

                {{-- Active Filters Badges Row --}}
                <div x-show="filter.length > 0 || segment.length > 0 || tag.length > 0 || topic.length > 0 || subscription.length > 0 || channel.length > 0 || wa_status.length > 0 || archived.length > 0" 
                    class="flex flex-wrap items-center gap-2 mt-6" x-cloak>
                    
                    <span class="text-xs text-surface-600 uppercase tracking-widest px-1 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filters Applied:
                    </span>
                    
                    <template x-for="val in filter" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Email Health: ' + filterNames.filter[val]"></span>
                            <button @click="filter = filter.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in segment" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Segment: ' + filterNames.segment[val]"></span>
                            <button @click="segment = segment.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in tag" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Tag: ' + filterNames.tag[val]"></span>
                            <button @click="tag = tag.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in topic" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Subscription: ' + filterNames.topic[val]"></span>
                            <button @click="topic = topic.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in subscription" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Email Status: ' + filterNames.subscription[val]"></span>
                            <button @click="subscription = subscription.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in channel" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Channel: ' + filterNames.channel[val]"></span>
                            <button @click="channel = channel.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in wa_status" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'WA Status: ' + filterNames.wa_status[val]"></span>
                            <button @click="wa_status = wa_status.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>
                    <template x-for="val in archived" :key="val">
                        <div class="flex items-center gap-1.5 text-brand px-4 py-1.5 rounded-full text-xs font-bold">
                            <span x-text="'Audience View: ' + filterNames.archived[val]"></span>
                            <button @click="archived = archived.filter(i => i !== val); fetchEmails()" class="hover:text-red-500 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>

                    <button @click="resetFilters()"
                        class="ml-2 text-xs text-surface-600 font-semibold hover:text-surface-900 tracking-widest px-2 py-1 transition-colors flex items-center gap-1">
                        Clear All
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            {{-- Active Import Progress --}}
            <div x-show="stats.import_progress !== undefined && stats.status === 'processing'" x-cloak
                class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-black text-blue-900 uppercase tracking-widest">Background Import in
                            Progress...</span>
                    </div>
                    <span class="text-[10px] font-black text-blue-900"
                        x-text="Math.round(stats.import_progress) + '%'"></span>
                </div>
                <div class="w-full bg-blue-200 h-1 rounded-full overflow-hidden">
                    <div class="bg-blue-600 h-full transition-all duration-500"
                        :style="'width: ' + stats.import_progress + '%'"></div>
                </div>
                <!-- <div class="mt-4">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-brand"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Channel
                                Integrity</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 border border-gray-100 rounded-sm">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Email
                                    Coverage</p>
                                <p class="text-xl font-black text-surface-900"
                                    x-text="Math.round((stats.global_valid / stats.full_total) * 100) + '%'"></p>
                            </div>
                            <div class="p-4 border border-gray-100 rounded-sm">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">WhatsApp
                                    Coverage</p>
                                <p class="text-xl font-black text-emerald-500"
                                    x-text="Math.round((stats.whatsapp_count / stats.full_total) * 100) + '%'"></p>
                            </div>
                        </div>
                    </div>
                </div> -->
                <div class="mt-2 flex justify-between text-[8px] font-black text-blue-400 uppercase tracking-widest">
                    <span x-text="`Processed: ${stats.import_details?.processed || 0} rows`"></span>
                    <span
                        x-text="`Valid: ${stats.import_details?.valid || 0} • Dup: ${stats.import_details?.duplicate || 0} • Invalid: ${stats.import_details?.invalid || 0}`"></span>
                </div>
            </div>

            {{-- Premium Counts & Advanced Metrics Dashboard --}}
            <div class="">
                {{-- Simple Stats Text --}}
                <div class="mb-6 text-sm text-surface-600 bg-gray-50 py-3 px-4 rounded-sm border border-gray-100 flex items-center">
                    <template x-if="!stats.is_filtered && archived !== 'yes'">
                        <div class="flex flex-wrap gap-x-4 gap-y-2 items-center">
                            <span class="flex items-baseline gap-1.5">
                                <span class="text-xl font-black text-surface-900" x-text="stats.global_main_rows.toLocaleString()"></span>
                                <span class="font-medium text-lg tracking-wide">profiles</span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="flex items-baseline gap-1.5">
                                <span class="text-xl font-black text-surface-900" x-text="stats.full_total.toLocaleString()"></span>
                                <span class="font-medium text-lg tracking-wide">rows</span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="flex items-baseline gap-1.5">
                                <span class="text-xl font-black text-blue-600" x-text="stats.subscribed_emails.toLocaleString()"></span>
                                <span class="font-medium text-lg tracking-wide">email subscribers</span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="flex items-baseline gap-1.5">
                                <span class="text-xl font-black text-emerald-600" x-text="stats.subscribed_whatsapps.toLocaleString()"></span>
                                <span class="font-medium text-lg tracking-wide">whatsapp opt-in</span>
                            </span>
                        </div>
                    </template>
                    <template x-if="stats.is_filtered || archived === 'yes'">
                        <div class="flex items-baseline gap-2">
                            <span class="text-surface-600 font-medium">Total</span>
                            <span class="text-xl font-black text-brand" x-text="stats.filtered_main_rows.toLocaleString()"></span>
                            <span class="text-brand font-bold tracking-wide">contacts</span>
                        </div>
                    </template>
                </div>

                <!-- Alert Banner for Cross-List Duplicates -->
                <div x-show="stats.cross_duplicate > 0" x-cloak
                    class="p-4 bg-amber-50 border border-amber-100 rounded-sm flex items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="shrink-0 w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-amber-800 font-black uppercase text-[10px] tracking-widest">Cross-list Duplicates
                                Detected</p>
                            <p class="text-[11px] text-amber-700 font-medium mt-0.5">
                                We found <span class="font-bold" x-text="stats.cross_duplicate"></span> contact record(s)
                                that already exist in your other lists. Choose how to handle them.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.email-lists.duplicates.index', $emailList) }}"
                        class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-sm flex items-center gap-2 transition-all active:scale-95 hover:shadow-md cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 00-2 2h2a2 2 0 002-2" />
                        </svg>
                        Resolve Duplicates
                    </a>
                </div>

                <!-- Alert Banner for Invalid Contacts -->
                <div x-show="stats.invalid > 0" x-cloak
                    class="p-4 bg-red-50 border border-red-100 rounded-sm flex items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-red-800 font-black uppercase text-[10px] tracking-widest">Invalid Contacts
                                Detected</p>
                            <p class="text-[11px] text-red-700 font-medium mt-0.5">
                                We found <span class="font-bold" x-text="stats.invalid"></span> contact record(s) with
                                invalid email or phone formats. Click fix to clean them up.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.email-lists.fix-invalid', $emailList) }}"
                        class="shrink-0 bg-red-500 hover:bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-sm flex items-center gap-2 transition-all active:scale-95 hover:shadow-md cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Fix Invalid Records
                    </a>
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
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-white/40 mt-1">Contacts
                                Selected</span>
                        </div>
                    </div>

                    <div class="h-10 w-px bg-white/10"></div>

                    {{-- Actions Container --}}
                    <div class="flex items-center gap-1">
                        <button
                            @click="showBulkActionModal = true; bulkActionType = ''; bulkUpdateColumn = ''; bulkUpdateValue = '';"
                            class="flex items-center gap-2 px-5 py-2.5 bg-brand hover:bg-brand-600 text-white text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer rounded-sm shadow-lg hover:scale-[1.02] active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            Bulk Actions
                        </button>
                    </div>
                </div>

                {{-- Right: Clear Action --}}
                <div class="flex items-center gap-3 pr-2">
                    <button @click="selectedIds = []; globalSelect = false"
                        class="flex items-center gap-2 px-4 py-2 hover:bg-white/20 rounded-sm text-white transition-all cursor-pointer group">
                        <span
                            class="text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer group">Clear
                            Selection</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Contact Data Grid --}}
            <div class="bg-white border border-gray-100 rounded-sm overflow-hidden min-h-[600px]">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-surface-50 border-b border-color text-xs uppercase tracking-widest text-surface-500">
                                <th class="px-8 py-4 sticky left-0 bg-white z-10">
                                    <div class="flex items-center gap-1" x-data="{ open: false }">
                                        <input type="checkbox" @change="toggleSelectAll($event.target.checked)"
                                            :checked="selectedIds.length > 0"
                                            class="w-4 h-4 rounded-sm border-gray-200 text-brand focus:ring-brand cursor-pointer">
                                        <div class="relative">
                                            <button @click="open = !open"
                                                class="p-1 hover:bg-gray-100 rounded-sm transition-colors cursor-pointer">
                                                <svg class="w-2.5 h-2.5 text-surface-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="absolute left-0 top-0 w-40 mt-3 bg-white border border-gray-200 rounded-sm z-70 py-1">
                                                <button @click="selectVisible(); open = false"
                                                    class="w-full text-left px-3 py-2 text-xs font-black text-surface-700 hover:bg-gray-50 transition-colors uppercase tracking-widest">Select
                                                    visible</button>
                                                <button @click="selectAll(); open = false"
                                                    class="w-full text-left px-3 py-2 text-xs font-black text-surface-700 hover:bg-gray-50 transition-colors uppercase tracking-widest border-t border-gray-50">Select
                                                    all</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-8 py-4 whitespace-nowrap">Full Name</th>
                                @foreach($displayedFields as $field)
                                    <th class="px-8 py-4 whitespace-nowrap">
                                        {{ $mapping[$field] ?? str_replace(['_', 'custom_'], [' ', ''], $field) }}</th>
                                @endforeach
                                <th class="px-8 py-4 text-center whitespace-nowrap">Segment</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">Tag</th>
                                <th class="px-8 py-4 whitespace-nowrap">Email Address</th>
                                <th class="px-8 py-4 whitespace-nowrap">WhatsApp Number</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">Email Health</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">Subscriptions</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">WA Status</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">Email Score</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">WA Score</th>
                                <th class="px-8 py-4 whitespace-nowrap">Pipeline / Stage</th>
                                <th class="px-8 py-4 whitespace-nowrap">Deal Notes</th>
                                <th class="px-8 py-4 text-center whitespace-nowrap">Added By</th>
                                <th class="px-8 py-4 text-right">
                                    <button @click="showAddCustomColumnModal = true; newCustomColumnName = ''"
                                        class="p-1 hover:bg-gray-100 rounded-sm text-surface-400 hover:text-brand transition-colors"
                                        title="Add Custom Column">
                                        <svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="email-table-body" class="divide-y divide-gray-200">
                            @include('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])
                        </tbody>
                    </table>
                </div>

                <div class="px-8 py-6 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between"
                    id="pagination-links">
                    <div class="text-[10px] font-black text-surface-400 uppercase tracking-widest">
                        Showing <span x-text="stats.total > 0 ? '1-50' : '0'"></span> of <span x-text="stats.total"></span>
                        Entries
                    </div>
                    {{ $emails->links() }}
                </div>
            </div>
        </div>

        {{-- ── HISTORY & LOGS VIEW ── --}}
        <div x-show="activeTab === 'logs'" x-cloak class="space-y-4 animate-slide-up">
            {{-- Activity History List (Mailchimp inspired) --}}
            <div class="rounded-sm -m-6">
                <div class="p-6 border-b border-color">
                    <h3 class="text-lg text-surface-900 tracking-tight">Audit Log</h3>
                    <p class="text-[10px] text-surface-700 uppercase tracking-widest mt-1">Review your recent list activity
                        and imports</p>
                </div>

                <div class="divide-y divide-gray-200">
                    @forelse($emailList->activityLogs as $log)
                        <div class="p-6 border-b border-color hover:bg-gray-200 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="space-y-4 flex-1">
                                    {{-- Header --}}
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-xs text-surface-900">{{ ($log->details['status'] ?? '') === 'completed' ? 'Complete' : 'Activity' }}</span>
                                        <span
                                            class="text-xs text-surface-500">{{ $log->created_at->format('M d, Y • g:i A') }}</span>
                                        @if($log->type === 'import')
                                            <span
                                                class="text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-sm uppercase tracking-widest">Import</span>
                                        @endif
                                    </div>

                                    {{-- Main Content --}}
                                    <div class="space-y-3">
                                        @if($log->type === 'import')
                                            <div class="flex flex-col gap-1.5">
                                                <p class="text-sm text-surface-700">
                                                    Import session: <span
                                                        class="text-surface-900">{{ $log->details['source'] ?? 'File Upload' }}</span>
                                                    @if(!empty($log->details['tags'])) • <span
                                                        class="text-brand font-semibold uppercase text-[9px]">{{ $log->details['tags'] }}</span>
                                                    @endif
                                                </p>

                                                <div class="flex flex-wrap gap-x-6 gap-y-4 mt-2">
                                                    @if(isset($log->details['processed']))
                                                        <div class="flex items-center gap-2 text-xs text-surface-900">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-surface-100 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M4 6h16M4 12h16M4 18h16" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['processed']) }} total rows</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['valid']))
                                                        <div class="flex items-center gap-2 text-xs text-emerald-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-emerald-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['valid']) }} clean</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['risky']) && $log->details['risky'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-amber-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-amber-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['risky']) }} risky</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['duplicate']) && $log->details['duplicate'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-surface-500">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-surface-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['duplicate']) }} duplicates</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['invalid']) && $log->details['invalid'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-red-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-red-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['invalid']) }} invalid/syntax
                                                                errors</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['role_based']) && $log->details['role_based'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-blue-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-blue-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['role_based']) }} role-based</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['disposable']) && $log->details['disposable'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-indigo-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-indigo-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['disposable']) }} disposable</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['catch_all']) && $log->details['catch_all'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-fuchsia-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-fuchsia-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['catch_all']) }} catch-all</span>
                                                        </div>
                                                    @endif

                                                    @if(isset($log->details['typo']) && $log->details['typo'] > 0)
                                                        <div class="flex items-center gap-2 text-xs text-rose-600">
                                                            <div
                                                                class="w-5 h-5 rounded-full bg-rose-50 flex items-center justify-center">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3"
                                                                        d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                                </svg>
                                                            </div>
                                                            <span>{{ number_format($log->details['typo']) }} typos</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if(!empty($log->details['reasons']))
                                                    <div
                                                        class="mt-2 ml-7 text-[9px] text-red-400 uppercase tracking-widest leading-relaxed max-w-lg">
                                                        Errors:
                                                        {{ collect($log->details['reasons'])->map(fn($v, $k) => "$k ($v)")->join(' • ') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($log->type === 'export')
                                            <p class="text-sm text-surface-700">Exported dataset: <span
                                                    class="text-surface-900">{{ $log->details['filename'] }}</span></p>
                                        @elseif($log->type === 'bulk_action')
                                            <p class="text-sm text-surface-700">
                                                Bulk action: <span
                                                    class="text-brand uppercase font-semibold tracking-tight">{{ str_replace('_', ' ', $log->details['action']) }}</span>
                                                on {{ number_format($log->details['count']) }} records
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-3">
                                    @if(isset($log->details['undone']))
                                        <span
                                            class="px-3 py-1 bg-gray-100 text-gray-400 text-[10px] uppercase tracking-widest rounded-sm">Import
                                            Reverted</span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-2 text-[10px] {{ ($log->details['status'] ?? '') === 'started' ? 'text-blue-600' : 'text-emerald-600' }} uppercase tracking-widest">
                                            <span
                                                class="w-2 h-2 {{ ($log->details['status'] ?? '') === 'started' ? 'bg-blue-500 animate-pulse' : 'bg-emerald-500' }} rounded-full"></span>
                                            {{ $log->details['status'] ?? 'Completed' }}
                                        </span>

                                        @if($log->type === 'import' && ($log->details['status'] ?? '') !== 'started')
                                            <form action="{{ route('admin.email-lists.undo-import', [$emailList, $log->id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('UNDO IMPORT: This will delete all contacts added in this session. Proceed?')">
                                                @csrf
                                                <button type="submit"
                                                    class="py-2 text-red-500 text-[10px] uppercase tracking-widest hover:underline cursor-pointer">Undo
                                                    Import</button>
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

        {{-- ── SIGNUP FORMS TAB ── --}}
        <div x-show="activeTab === 'forms'" x-cloak class="space-y-6 animate-slide-up">
            <div class="glass-card rounded-md">
                <div class="p-8 space-y-6">
                    <div>
                        <h3 class="text-xl font-bold text-surface-900 tracking-tight">Public Signup Forms</h3>
                        <p class="text-sm text-surface-500 mt-1">Grow your list organically by placing signup forms on your
                            site or sharing direct signup page links.</p>
                    </div>

                    {{-- Settings Form --}}
                    <form action="{{ route('admin.email-lists.update-settings', $emailList) }}" method="POST"
                        class="space-y-6 pt-4 border-t border-surface-100">
                        @csrf
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="double_opt_in" id="double_opt_in" value="1" {{ $emailList->double_opt_in ? 'checked' : '' }} onchange="this.form.submit()"
                                class="mt-1 w-4 h-4 text-brand border-surface-300 rounded focus:ring-brand">
                            <div>
                                <label for="double_opt_in" class="text-sm font-bold text-surface-750">Enable Double Opt-In
                                    Verification</label>
                                <p class="text-xs text-surface-450 mt-1">If enabled, new contacts will receive a
                                    confirmation email and will remain in a pending state until verified. Works perfectly
                                    with visual workflows.</p>
                            </div>
                        </div>
                    </form>

                    {{-- Public Links --}}
                    <div class="space-y-6 pt-6 border-t border-surface-100"
                        x-data="{ copiedLink: false, copiedEmbed: false }">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest block">Hosted Signup
                                Page Link</label>
                            <div class="flex gap-2 max-w-2xl">
                                <input type="text" readonly
                                    value="{{ route('public.forms.show', $emailList->signup_form_token) }}"
                                    id="signup-link-input"
                                    class="form-input rounded-md !bg-surface-50 border-surface-200 text-xs font-mono py-2.5">
                                <button type="button" @click="
                                        navigator.clipboard.writeText(document.getElementById('signup-link-input').value);
                                        copiedLink = true;
                                        setTimeout(() => copiedLink = false, 2000);
                                    "
                                    class="btn btn-secondary rounded-md px-6 py-2.5 text-xs font-black uppercase tracking-widest shrink-0 border border-surface-200 bg-white">
                                    <span x-show="!copiedLink">Copy Link</span>
                                    <span x-show="copiedLink" class="text-emerald-600">Copied!</span>
                                </button>
                                <a href="{{ route('public.forms.show', $emailList->signup_form_token) }}" target="_blank"
                                    class="btn btn-secondary rounded-md px-6 py-2.5 text-xs font-black uppercase tracking-widest shrink-0 border border-surface-200 bg-white hover:bg-surface-50 flex items-center justify-center">
                                    Preview
                                </a>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest block">Embed iframe
                                Widget HTML</label>
                            <div class="flex gap-2 max-w-2xl">
                                <input type="text" readonly
                                    value='<iframe src="{{ route('public.forms.show', $emailList->signup_form_token) }}" width="100%" height="650px" style="border:none;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);"></iframe>'
                                    id="signup-embed-input"
                                    class="form-input rounded-md !bg-surface-50 border-surface-200 text-xs font-mono py-2.5">
                                <button type="button" @click="
                                        navigator.clipboard.writeText(document.getElementById('signup-embed-input').value);
                                        copiedEmbed = true;
                                        setTimeout(() => copiedEmbed = false, 2000);
                                    "
                                    class="btn btn-secondary rounded-md px-6 py-2.5 text-xs font-black uppercase tracking-widest shrink-0 border border-surface-200 bg-white">
                                    <span x-show="!copiedEmbed">Copy Code</span>
                                    <span x-show="copiedEmbed" class="text-emerald-600">Copied!</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── OPT-OUT ANALYTICS TAB ── --}}
        <div x-show="activeTab === 'opt_out'" x-cloak class="space-y-6 animate-slide-up">
            @php
                $listTopics = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)->get();
                $topicStats = [];

                foreach ($listTopics as $topic) {
                    $subCount = \App\Models\Email::where('email_list_id', $emailList->id)
                        ->where('subscription_status', 'subscribed')
                        ->whereJsonContains('subscribed_topics', (string) $topic->id)
                        ->count();

                    $unsubCount = \App\Models\Email::where('email_list_id', $emailList->id)
                        ->where('subscription_status', 'unsubscribed')
                        ->whereJsonContains('subscribed_topics', (string) $topic->id)
                        ->count();

                    $total = $subCount + $unsubCount;
                    $unsubRate = $total > 0 ? round(($unsubCount / $total) * 100, 1) : 0;

                    $topicStats[] = [
                        'topic' => $topic,
                        'sub_count' => $subCount,
                        'unsub_count' => $unsubCount,
                        'unsub_rate' => $unsubRate,
                    ];
                }

                $totalUnsubscribed = \App\Models\Email::where('email_list_id', $emailList->id)->where('subscription_status', 'unsubscribed')->count();
                $totalContacts = \App\Models\Email::where('email_list_id', $emailList->id)->where('is_archived', false)->count();
                $overallOptOutRate = $totalContacts > 0 ? round(($totalUnsubscribed / $totalContacts) * 100, 1) : 0;
            @endphp

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 border border-surface-200/80 rounded-md">
                    <h5 class="text-[10px] font-black text-surface-450 uppercase tracking-widest leading-none">Total List
                        Subscribers</h5>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2">
                        {{ number_format($totalContacts - $totalUnsubscribed) }}</p>
                </div>
                <div class="bg-white p-6 border border-surface-200/80 rounded-md">
                    <h5 class="text-[10px] font-black text-surface-450 uppercase tracking-widest leading-none">Total
                        Opt-Outs</h5>
                    <p class="text-3xl font-extrabold text-surface-900 mt-2">{{ number_format($totalUnsubscribed) }}</p>
                </div>
                <div class="bg-white p-6 border border-surface-200/80 rounded-md">
                    <h5 class="text-[10px] font-black text-surface-450 uppercase tracking-widest leading-none">Overall List
                        Opt-Out Rate</h5>
                    <p
                        class="text-3xl font-extrabold mt-2 {{ $overallOptOutRate > 15 ? 'text-red-650' : ($overallOptOutRate > 5 ? 'text-amber-600' : 'text-emerald-600') }}">
                        {{ $overallOptOutRate }}%
                    </p>
                </div>
            </div>

            {{-- Topics Analytics List --}}
            <div class="glass-card rounded-md">
                <div class="p-8 space-y-6">
                    <div>
                        <h3 class="text-xl font-bold text-surface-900 tracking-tight">Opt-Out Rates by Topic</h3>
                        <p class="text-sm text-surface-500 mt-1">Review subscription breakdown and opt-out rates across
                            different email categories.</p>
                    </div>

                    <div class="divide-y divide-surface-100 pt-4">
                        @forelse($topicStats as $stat)
                            <div class="py-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                <div class="space-y-1 md:w-1/3">
                                    <h4 class="font-bold text-surface-900 text-sm">{{ $stat['topic']->name }}</h4>
                                    <p class="text-xs text-surface-450">{{ Str::limit($stat['topic']->description, 80) }}</p>
                                </div>

                                <div class="flex-1 w-full space-y-2">
                                    <div class="flex justify-between text-xs font-bold text-surface-700">
                                        <span>{{ number_format($stat['sub_count']) }} Subscribed</span>
                                        <span>{{ number_format($stat['unsub_count']) }} Opted Out
                                            ({{ $stat['unsub_rate'] }}%)</span>
                                    </div>
                                    <div class="w-full h-2.5 bg-surface-100 rounded-full overflow-hidden flex">
                                        <div class="bg-emerald-500 h-full" style="width: {{ 100 - $stat['unsub_rate'] }}%">
                                        </div>
                                        <div class="h-full {{ $stat['unsub_rate'] > 15 ? 'bg-red-500' : ($stat['unsub_rate'] > 5 ? 'bg-amber-500' : 'bg-emerald-400') }}"
                                            style="width: {{ $stat['unsub_rate'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-surface-500 italic py-6">No subscription topics configured for this list.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Teleported Modals ── --}}
        <template x-teleport="body">
            <div>
                @include('email-lists.partials.contact-profile-slideover')
                
                {{-- Modals Wrapper --}}
                <div class="font-sans antialiased text-surface-900 selection:bg-brand selection:text-white">
                {{-- Export Modal --}}
                <div x-show="showExportModal"
                    class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/90" x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-lg overflow-hidden animate-scale-in"
                        @click.away="showExportModal = false">
                        <div class="p-8 border-b border-gray-100 bg-surface-50/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-black text-surface-900 tracking-tight">Export Audience</h3>
                                    <p class="text-[10px] text-surface-400 font-black uppercase mt-1 tracking-widest">
                                        Generate filtered data snapshot</p>
                                </div>
                                <button @click="showExportModal = false"
                                    class="text-surface-400 hover:text-surface-600 transition-colors cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-8 space-y-8">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Custom
                                    Filename</label>
                                <div class="relative">
                                    <input type="text" x-model="exportFilename"
                                        class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-surface-300 uppercase"
                                        x-text="`.${exportFormat}`"></div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Select
                                    Format</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button @click="exportFormat = 'xlsx'"
                                        :class="exportFormat === 'xlsx' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'"
                                        class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                        <div class="flex flex-col items-center gap-3">
                                            <div :class="exportFormat === 'xlsx' ? 'bg-brand/10' : 'bg-gray-50'"
                                                class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Excel / XLSX</p>
                                        </div>
                                    </button>
                                    <button @click="exportFormat = 'csv'"
                                        :class="exportFormat === 'csv' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'"
                                        class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                        <div class="flex flex-col items-center gap-3">
                                            <div :class="exportFormat === 'csv' ? 'bg-brand/10' : 'bg-gray-50'"
                                                class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                    </path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 9h1m2 0h1m-3 4h1m2 0h1m-3 4h1m2 0h1"></path>
                                                </svg>
                                            </div>
                                            <p class="text-[10px] font-black uppercase tracking-widest">Text / CSV</p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {{-- Consolidate Option --}}
                            <div class="p-4 bg-amber-50 border border-amber-100 rounded-sm">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="consolidate"
                                        class="w-5 h-5 rounded-sm border-amber-300 text-amber-500 focus:ring-amber-500 cursor-pointer">
                                    <div>
                                        <p class="text-[11px] font-black text-amber-900 uppercase tracking-tight">
                                            Consolidate Split Rows</p>
                                        <p class="text-[10px] text-amber-700/70 font-bold mt-0.5">Merge multiple
                                            emails/numbers back into a single row per person.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button @click="showExportModal = false"
                                class="flex-1 py-3.5 text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-600 transition-colors cursor-pointer">Cancel</button>
                            <button @click="triggerExport()"
                                class="flex-2 bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm transition-all hover:scale-[1.01]">Download
                                File</button>
                        </div>
                    </div>
                </div>

                {{-- Import Modal --}}
                <div x-show="showImportMoreModal"
                    class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/80" x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-xl overflow-hidden shadow-xl"
                        @click.away="showImportMoreModal = false">

                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-surface-900 tracking-tight">Expand Audience</h3>
                                <p class="text-[10px] text-surface-400 font-bold uppercase mt-1 tracking-widest">Select
                                    import method</p>
                            </div>
                            <button @click="showImportMoreModal = false" class="text-surface-400 hover:text-surface-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6" x-data="{ type: 'upload' }" @change-import-tab.window="type = $event.detail.type">
                            <div class="grid grid-cols-3 gap-2 mb-6">
                                <button @click="type = 'upload'"
                                    :class="type === 'upload' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'"
                                    class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-[9px] font-black uppercase">CSV/Excel</p>
                                    </div>
                                </button>

                                <button @click="type = 'paste'"
                                    :class="type === 'paste' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'"
                                    class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <p class="text-[9px] font-black uppercase">Paste</p>
                                    </div>
                                </button>

                                <button @click="type = 'single'"
                                    :class="type === 'single' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'"
                                    class="p-4 border rounded-sm transition-colors cursor-pointer">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        <p class="text-[9px] font-black uppercase">Manual</p>
                                    </div>
                                </button>
                            </div>

                            <div class="space-y-6">
                                <form x-show="type !== 'single'"
                                    action="{{ route('admin.email-lists.import-more', $emailList) }}" method="POST"
                                    enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    <input type="hidden" name="import_type" :value="type">

                                    <div>
                                        <label
                                            class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Internal
                                            Tags</label>
                                        <input type="text" name="tags" value="{{ $emailList->tags }}"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand">
                                        <input type="hidden" name="signup_source" value="import">
                                    </div>

                                    <div x-show="type === 'upload'">
                                        <label
                                            class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Select
                                            Dataset (CSV/Excel)</label>
                                        <div class="relative" x-data="{ dragging: false, fileName: '' }"
                                            @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                            @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0].name">

                                            <label
                                                :class="dragging ? 'border-brand bg-brand/5' : 'border-gray-200 bg-white'"
                                                class="flex flex-col items-center justify-center w-full min-h-[120px] border-2 border-dashed rounded-sm cursor-pointer hover:bg-gray-50 transition-all group">

                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg :class="dragging ? 'text-brand' : 'text-surface-300 group-hover:text-brand'"
                                                        class="w-10 h-10 mb-3 transition-colors" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                        </path>
                                                    </svg>
                                                    <p class="mb-1 text-xs font-black text-surface-700 uppercase tracking-widest"
                                                        x-text="fileName || 'Click or Drag & Drop'"></p>
                                                    <p class="text-[9px] text-surface-400 font-bold uppercase tracking-widest"
                                                        x-show="!fileName">CSV, XLSX or TXT (Max 10MB)</p>
                                                </div>

                                                <input type="file" name="file" x-ref="fileInput" class="hidden"
                                                    accept=".csv,.xlsx,.txt" @change="fileName = $el.files[0].name">
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="type === 'paste'">
                                        <label
                                            class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Email
                                            List</label>
                                        <textarea name="emails_text" rows="4"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-xs font-mono"
                                            placeholder="email@domain.com"></textarea>
                                    </div>

                                    <button type="submit"
                                        class="w-full bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                        x-data="{ loading: false }" @click="setTimeout(() => loading = true, 50)"
                                        :disabled="loading">
                                        <span x-show="!loading">Start Import</span>
                                        <span x-show="loading" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Initializing...
                                        </span>
                                    </button>
                                </form>

                                <form x-show="type === 'single'" @submit.prevent="addManualContact()" class="space-y-6">
                                    <div class="space-y-4">
                                        <input type="email" x-model="newContact.email" placeholder="Email Address"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold"
                                            required>
                                        <input type="text" x-model="newContact.name" placeholder="Full Name"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold">
                                        <input type="text" x-model="newContact.whatsapp_number"
                                            placeholder="WhatsApp Number (e.g. 919876543210)"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold">
                                    </div>
                                    <button type="submit"
                                        class="w-full bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm cursor-pointer"
                                        :disabled="adding">
                                        <span x-show="!adding">Register Contact</span>
                                        <span x-show="adding">Processing...</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bulk Unsubscribe Modal --}}
                <div x-show="showUnsubscribeModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-md overflow-hidden shadow-xl"
                        @click.away="showUnsubscribeModal = false">

                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-surface-900 tracking-tight">Bulk Unsubscribe</h3>
                                <p class="text-[10px] text-surface-400 font-bold uppercase mt-1 tracking-widest">Select
                                    unsubscribe options</p>
                            </div>
                            <button @click="showUnsubscribeModal = false" class="text-surface-400 hover:text-surface-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-6">
                            <p class="text-xs font-bold text-surface-600">
                                You have selected <span class="text-brand font-black"
                                    x-text="globalSelect ? stats.total.toLocaleString() : selectedIds.length"></span>
                                contact(s) to unsubscribe.
                                Select how long they should be unsubscribed:
                            </p>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Duration</label>
                                <select x-model="unsubscribeDuration"
                                    class="w-full p-4 border border-gray-150 rounded-sm bg-gray-50 text-xs font-bold focus:bg-white focus:border-brand focus:ring-0 focus:outline-none cursor-pointer">
                                    <option value="forever">Permanently (Forever)</option>
                                    <option value="1">Temporary Unsubscribe (1 Day)</option>
                                    <option value="3">Temporary Unsubscribe (3 Days)</option>
                                    <option value="7">Temporary Unsubscribe (7 Days)</option>
                                    <option value="14">Temporary Unsubscribe (14 Days)</option>
                                    <option value="30">Temporary Unsubscribe (30 Days)</option>
                                    <option value="90">Temporary Unsubscribe (90 Days)</option>
                                    <option value="365">Temporary Unsubscribe (1 Year)</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button @click="showUnsubscribeModal = false"
                                class="flex-1 py-3.5 text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-600 transition-colors cursor-pointer">Cancel</button>
                            <button @click="bulkUnsubscribe()"
                                class="flex-2 bg-red-500 hover:bg-red-600 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm transition-all hover:scale-[1.01]">Confirm
                                Unsubscribe</button>
                        </div>
                    </div>
                </div>

                {{-- Permanent Delete Modal --}}
                <div x-show="showPermanentDeleteModal"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-surface-900/90 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-md overflow-hidden shadow-2xl border border-red-500/20"
                        @click.away="showPermanentDeleteModal = false">

                        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-red-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-red-600 tracking-tight">Permanent Deletion</h3>
                                    <p class="text-[10px] text-red-500/80 font-bold uppercase mt-1 tracking-widest">
                                        Irreversible Action</p>
                                </div>
                            </div>
                            <button @click="showPermanentDeleteModal = false" class="text-red-400 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-6">
                            <div class="p-4 bg-red-50 border border-red-100 rounded-sm">
                                <p class="text-xs font-bold text-red-700">
                                    <span class="block mb-2 font-black">⚠️ DANGER ZONE</span>
                                    You are about to permanently delete
                                    <span class="text-red-900 font-black text-sm"
                                        x-text="deleteTargetId ? '1 contact' : (globalSelect ? stats.total.toLocaleString() : selectedIds.length) + ' contacts'"></span>.
                                </p>
                                <ul class="list-disc list-inside mt-3 text-[11px] text-red-600 space-y-1 font-medium">
                                    <li>These contacts cannot be re-imported later.</li>
                                    <li>They will be added to the suppression list.</li>
                                    <li>This action is completely irreversible.</li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 p-4 border border-gray-200 rounded-sm">
                                <p class="text-xs font-bold text-surface-600 mb-2">
                                    <i class="fa-solid fa-lightbulb text-amber-500 mr-1"></i>
                                    Recommendation: Archive Instead
                                </p>
                                <p class="text-[10px] text-surface-500 mb-3">
                                    Archiving hides the contact and removes it from billing and active audience counts
                                    without losing the data forever.
                                </p>
                                <button
                                    @click="showPermanentDeleteModal = false; deleteTargetId ? archiveEmail(deleteTargetId) : bulkAction('archive')"
                                    class="w-full bg-white border border-gray-300 text-surface-700 text-[10px] font-black uppercase tracking-widest py-3 rounded-sm hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-box-archive mr-2"></i> Archive Contacts Instead
                                </button>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Type
                                    "PERMANENT DELETE" to confirm</label>
                                <input type="text" x-model="permanentDeleteConfirmText"
                                    class="w-full px-3 py-2 border border-red-200 rounded-sm bg-red-50/50 text-sm font-bold text-red-900 focus:bg-white focus:border-red-500 focus:ring-0 focus:outline-none mb-3"
                                    placeholder="PERMANENT DELETE">

                                <label
                                    class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Deletion
                                    Reason (Required)</label>
                                <input type="text" x-model="permanentDeleteReason"
                                    class="w-full px-3 py-2 border border-red-200 rounded-sm bg-red-50/50 text-sm font-bold text-red-900 focus:bg-white focus:border-red-500 focus:ring-0 focus:outline-none"
                                    placeholder="e.g. GDPR Request, Invalid Data">
                            </div>

                            <button @click="confirmPermanentDelete()"
                                class="w-full bg-red-600 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm cursor-pointer shadow-lg hover:bg-red-700 active:scale-95 transition-all disabled:opacity-50"
                                :disabled="permanentDeleteConfirmText !== 'PERMANENT DELETE' || !permanentDeleteReason.trim()">
                                Yes, Permanently Delete Now
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Add Alternate Channel Modal --}}
                <div x-show="showAddChannelModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-sm overflow-hidden shadow-xl"
                        @click.away="showAddChannelModal = false">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest"
                                x-text="'Add ' + (addChannelType === 'email' ? 'Email Address' : 'WhatsApp Number')"></h3>
                            <button @click="showAddChannelModal = false" class="text-surface-400 hover:text-surface-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="submitAddChannel()">
                                <div class="mb-4">
                                    <label
                                        class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2"
                                        x-text="(addChannelType === 'email' ? 'Email Address' : 'WhatsApp Number') + ' *'"></label>
                                    <input :type="addChannelType === 'email' ? 'email' : 'text'" x-model="addChannelValue"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand"
                                        required>
                                </div>
                                <button type="submit"
                                    class="w-full bg-brand text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-sm cursor-pointer"
                                    :disabled="addingChannel">
                                    <span x-show="!addingChannel">Save Channel</span>
                                    <span x-show="addingChannel">Saving...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Add Custom Column Modal --}}
                {{-- Bulk Actions Modal --}}
                <div x-show="showBulkActionModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-md overflow-hidden shadow-xl flex flex-col max-h-[90vh] border border-surface-200"
                        @click.away="showBulkActionModal = false">

                        {{-- Header --}}
                        <div
                            class="p-5 border-b border-surface-100 bg-surface-50/50 flex items-start justify-between shrink-0">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-surface-900 tracking-tight uppercase">Bulk Actions
                                    </h3>
                                    <p class="text-[10px] text-surface-500 mt-1 uppercase font-bold tracking-widest">
                                        <span class="text-brand"
                                            x-text="globalSelect ? (archived === 'yes' ? stats.archived.toLocaleString() : stats.total.toLocaleString()) : selectedIds.length"></span>
                                        Contacts Selected
                                    </p>
                                </div>
                            </div>
                            <button @click="showBulkActionModal = false"
                                class="text-surface-400 hover:text-surface-900 p-1 hover:bg-surface-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="p-6 overflow-y-auto bg-white">
                            <div class="space-y-5">

                                {{-- Action Selector --}}
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-2">Select
                                        Operation</label>
                                    <div class="relative">
                                        <select x-model="bulkActionType"
                                            class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-surface-900 text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all appearance-none cursor-pointer">
                                            <option value="">-- Choose an action --</option>
                                            @if($emailList->canPerformAction('edit_contact'))
                                                <optgroup label="Basic">
                                                    <option value="subscribe">Subscribe Contacts</option>
                                                    <option value="update_column">Update Column Data</option>
                                                </optgroup>
                                                <optgroup label="Tags & Topics">
                                                    <option value="add_tags">Add Tags</option>
                                                    <option value="remove_tags">Remove Tags</option>
                                                    <option value="add_topics">Add Subscriptions (Topics)</option>
                                                    <option value="remove_topics">Remove Subscriptions (Topics)</option>
                                                </optgroup>
                                                <optgroup label="CRM Actions">
                                                    <option value="add_note">Add Note</option>
                                                    <option value="add_task">Add Task</option>
                                                    <option value="create_deals">Send to Pipeline (Create Deals)</option>
                                                    <option value="transfer">Transfer to Another List</option>
                                                </optgroup>
                                            @endif
                                            @if($emailList->canPerformAction('delete_contact'))
                                                <optgroup label="Danger Zone">
                                                    <option value="unsubscribe">Unsubscribe Contacts</option>
                                                    <option :value="archived === 'yes' ? 'unarchive' : 'archive'"
                                                        x-text="archived === 'yes' ? 'Restore to Active' : 'Move to Archive'">
                                                    </option>
                                                    <option value="delete">Delete Permanently</option>
                                                </optgroup>
                                            @endif
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-surface-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        </div>
                                    </div>

                                    <template x-if="!bulkActionType">
                                        <div
                                            class="mt-3 p-3 bg-blue-50/50 border border-blue-100 rounded-sm flex gap-2 text-blue-800">
                                            <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="text-[10px] font-semibold">Select an action from the dropdown above to
                                                see more options. Updates apply to all selected contacts.</p>
                                        </div>
                                    </template>
                                </div>

                                {{-- Action Specific Fields --}}
                                <template x-if="bulkActionType === 'unsubscribe'">
                                    <div
                                        class="bg-amber-50 border border-amber-200 p-4 rounded-sm animate-fade-in">
                                        <div class="flex items-center gap-2 mb-3 text-amber-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <h4 class="font-bold text-xs">Unsubscribe Config</h4>
                                        </div>
                                        <label
                                            class="block text-[10px] font-black text-amber-900 uppercase tracking-widest mb-1.5">Duration</label>
                                        <select x-model="unsubscribeDuration"
                                            class="w-full px-3 py-2 bg-white border border-amber-200 rounded-sm text-sm font-bold focus:border-amber-500 focus:ring-0 text-amber-900 transition-all">
                                            <option value="permanent">Permanent</option>
                                            <option value="7_days">Temporary (7 Days)</option>
                                            <option value="30_days">Temporary (30 Days)</option>
                                        </select>
                                        <p class="text-[10px] text-amber-700 mt-2 font-semibold">Contacts won't receive
                                            campaigns during this period.</p>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'delete'">
                                    <div
                                        class="bg-red-50 border border-red-200 p-4 rounded-sm animate-fade-in space-y-3">
                                        <div class="flex items-start gap-2">
                                            <div
                                                class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-black text-red-800 text-xs tracking-tight">Warning</h4>
                                                <p class="text-[10px] text-red-700 font-semibold mt-0.5">Contacts will be
                                                    permanently deleted and blocked from future imports.</p>
                                            </div>
                                        </div>

                                        <div class="space-y-3 pt-3 border-t border-red-200/50">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-black text-red-900 uppercase tracking-widest mb-1.5">Type
                                                    "PERMANENT DELETE"</label>
                                                <input type="text" x-model="permanentDeleteConfirmText"
                                                    class="w-full px-3 py-2 border border-red-300 rounded-sm bg-white text-sm font-bold text-red-900 focus:border-red-500 focus:ring-0 transition-all placeholder:text-red-300"
                                                    placeholder="PERMANENT DELETE">
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-[10px] font-black text-red-900 uppercase tracking-widest mb-1.5">Deletion
                                                    Reason</label>
                                                <input type="text" x-model="permanentDeleteReason"
                                                    class="w-full px-3 py-2 border border-red-300 rounded-sm bg-white text-sm font-bold text-red-900 focus:border-red-500 focus:ring-0 transition-all placeholder:text-red-300"
                                                    placeholder="e.g. GDPR Request">
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'update_column'">
                                    <div
                                        class="bg-blue-50 border border-blue-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div class="flex items-center gap-2 text-blue-800 mb-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <h4 class="font-bold text-xs">Column Data</h4>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-1.5">Select
                                                Column</label>
                                            <div class="relative">
                                                <select x-model="bulkUpdateColumn"
                                                    class="w-full pl-3 pr-8 py-2 bg-white border border-blue-200 rounded-sm text-sm font-bold focus:border-blue-500 focus:ring-0 text-blue-900 transition-all appearance-none">
                                                    <option value="">-- Select Column --</option>
                                                    <option value="name">Full Name</option>
                                                    <option value="company">Company</option>
                                                    <option value="job_title">Job Title</option>
                                                    <option value="phone">Phone / Landline</option>
                                                    <option value="city">City</option>
                                                    <option value="country">Country</option>
                                                    @foreach($displayedFields as $field)
                                                        @if(!in_array($field, ['name', 'company', 'job_title', 'phone', 'city', 'country']))
                                                            <option value="{{ $field }}">
                                                                {{ $mapping[$field] ?? str_replace(['_', 'custom_'], [' ', ''], $field) }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <div
                                                    class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-blue-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-1.5">New
                                                Value</label>
                                            <input type="text" x-model="bulkUpdateValue"
                                                class="w-full px-3 py-2 border border-blue-300 rounded-sm bg-white text-sm font-bold text-blue-900 focus:border-blue-500 focus:ring-0 transition-all placeholder:text-blue-300"
                                                placeholder="Leave blank to clear data">
                                            <p class="text-[10px] text-blue-700 mt-1.5 font-semibold">Leaving this blank
                                                will empty the column.</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                                <!-- Advanced Bulk Action Templates -->
                                <template x-if="['add_tags', 'remove_tags'].includes(bulkActionType)">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Enter Tags (Comma Separated)</label>
                                            <input type="text" x-model="bulkPayload.tags" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all" placeholder="e.g. VIP, Follow Up, 2024">
                                            <p class="text-[10px] text-gray-500 mt-1.5 font-semibold">Multiple tags can be separated by commas.</p>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="['add_topics', 'remove_topics'].includes(bulkActionType)">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Select Topics</label>
                                            <select x-model="bulkPayload.topics" multiple class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all h-32">
                                                @foreach($topics as $topic)
                                                    <option value="{{ $topic->name }}">{{ $topic->name }}</option>
                                                @endforeach
                                            </select>
                                            <p class="text-[10px] text-gray-500 mt-1.5 font-semibold">Hold Ctrl (Windows) or Cmd (Mac) to select multiple topics.</p>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'create_deals'">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Pipeline</label>
                                            <select x-model="bulkPayload.pipeline_id" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all">
                                                <option value="">-- Select Pipeline --</option>
                                                @foreach($pipelines as $pipeline)
                                                    <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Stage</label>
                                            <select x-model="bulkPayload.stage_id" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all">
                                                <option value="">-- Select Stage --</option>
                                                @foreach($pipelines as $pipeline)
                                                    <optgroup label="{{ $pipeline->name }}">
                                                        @foreach($pipeline->stages as $stage)
                                                            <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'transfer'">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Destination List</label>
                                            <select x-model="bulkPayload.target_list_id" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all">
                                                <option value="">-- Select Target List --</option>
                                                @foreach($destinationLists as $list)
                                                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'add_note'">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Note Content</label>
                                            <textarea x-model="bulkPayload.note" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all" placeholder="Enter note..."></textarea>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="bulkActionType === 'add_task'">
                                    <div class="bg-gray-50 border border-gray-200 p-4 rounded-sm animate-fade-in space-y-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Task Title</label>
                                            <input type="text" x-model="bulkPayload.title" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all" placeholder="Task title...">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Task Description</label>
                                            <textarea x-model="bulkPayload.description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all" placeholder="Optional description..."></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1.5">Due Date</label>
                                            <input type="date" x-model="bulkPayload.due_date" class="w-full px-3 py-2 border border-gray-300 rounded-sm bg-white text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 transition-all">
                                        </div>
                                    </div>
                                </template>
                        </div>

                        {{-- Footer Actions --}}
                        <div class="p-5 border-t border-surface-100 bg-surface-50 flex justify-end gap-2 shrink-0">
                            <button @click="showBulkActionModal = false"
                                class="px-5 py-2 bg-white border border-surface-200 text-surface-600 text-[10px] font-black uppercase tracking-widest rounded-sm hover:bg-surface-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="executeBulkAction()"
                                class="px-5 py-2 text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95 flex items-center gap-2"
                                :class="bulkActionType === 'delete' ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-600'"
                                :disabled="!bulkActionType || (bulkActionType === 'delete' && (permanentDeleteConfirmText !== 'PERMANENT DELETE' || !permanentDeleteReason.trim())) || (bulkActionType === 'update_column' && !bulkUpdateColumn)">
                                <span x-text="bulkActionType === 'delete' ? 'Delete Permanently' : 'Apply Action'"></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="showAddCustomColumnModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-sm overflow-hidden shadow-xl"
                        @click.away="showAddCustomColumnModal = false">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">Add Custom Column</h3>
                            <button @click="showAddCustomColumnModal = false"
                                class="text-surface-400 hover:text-surface-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="addCustomColumn()">
                                <div class="mb-4">
                                    <label
                                        class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Column
                                        Name *</label>
                                    <input type="text" x-model="newCustomColumnName"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand"
                                        placeholder="e.g. Industry, Company Size" required>
                                </div>
                                <button type="submit"
                                    class="w-full bg-brand text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-sm cursor-pointer"
                                    :disabled="addingCustomColumn">
                                    <span x-show="!addingCustomColumn">Add Column</span>
                                    <span x-show="addingCustomColumn">Adding...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Transfer Contact Modal --}}
                <div x-show="showTransferModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-md overflow-hidden shadow-xl flex flex-col border border-surface-200"
                        @click.away="showTransferModal = false">

                        {{-- Header --}}
                        <div
                            class="p-5 border-b border-surface-100 bg-surface-50/50 flex items-start justify-between shrink-0">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-surface-900 tracking-tight uppercase">Transfer
                                        Contact</h3>
                                    <p class="text-[10px] text-surface-500 mt-1 uppercase font-bold tracking-widest"
                                        x-text="transferContact.name || transferContact.email"></p>
                                </div>
                            </div>
                            <button @click="showTransferModal = false"
                                class="text-surface-400 hover:text-surface-900 p-1 hover:bg-surface-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="p-6 bg-white space-y-4">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-2">Select
                                    Target List</label>
                                <div class="relative">
                                    <select x-model="transferContact.target_list_id"
                                        class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-surface-900 text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all appearance-none cursor-pointer">
                                        <option value="">-- Select Destination List --</option>
                                        @foreach($destinationLists as $list)
                                            <option value="{{ $list->id }}">{{ $list->name }}</option>
                                        @endforeach
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-surface-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-surface-500 mt-2 font-medium">This contact will be moved out of
                                    the current list and added to the selected list. Statistics for both lists will be
                                    updated automatically.</p>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="p-5 border-t border-surface-100 bg-surface-50 flex justify-end gap-2 shrink-0">
                            <button @click="showTransferModal = false"
                                class="px-5 py-2 bg-white border border-surface-200 text-surface-600 text-[10px] font-black uppercase tracking-widest rounded-sm hover:bg-surface-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="submitTransferContact()"
                                class="px-5 py-2 bg-brand hover:bg-brand-600 text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all active:scale-95 flex items-center gap-2"
                                :disabled="transferring || !transferContact.target_list_id">
                                <span x-show="!transferring">Transfer Contact</span>
                                <span x-show="transferring">Transferring...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Merge Duplicates Modal --}}
                <div x-show="showMergeModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/90" x-cloak>
                    <div x-show="showMergeModal" @click.outside="showMergeModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                        class="bg-white w-full max-w-md rounded-sm shadow-2xl overflow-hidden border border-surface-200">
                        
                        <div class="p-5 border-b border-surface-100 bg-surface-50/50 flex items-start justify-between shrink-0">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-surface-900 tracking-tight uppercase">Merge Duplicates</h3>
                                    <p class="text-[10px] text-surface-500 mt-1 uppercase font-bold tracking-widest">Find & combine duplicates</p>
                                </div>
                            </div>
                            <button @click="showMergeModal = false" class="text-surface-400 hover:text-surface-900 p-1 hover:bg-surface-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6 bg-white space-y-4">
                            <p class="text-xs text-surface-600 font-medium">This process scans the entire list and merges contacts that have identical values in the selected field. The most recently updated record will be kept, and tags/topics will be combined.</p>
                            <div>
                                <label class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-2">Merge By Field</label>
                                <select x-model="mergeBy" class="w-full text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand uppercase tracking-widest h-10">
                                    <option value="email">Email Address</option>
                                    <option value="whatsapp">WhatsApp Number</option>
                                </select>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-surface-50 border-t border-surface-100 flex items-center justify-end gap-3">
                            <button @click="showMergeModal = false" class="px-4 py-2 text-[10px] font-black text-surface-500 hover:text-surface-900 uppercase tracking-widest transition-colors">Cancel</button>
                            <button @click="submitMergeDuplicates()" :disabled="mergingDuplicates" class="px-5 py-2.5 bg-brand hover:bg-brand/90 text-white rounded-sm text-[10px] font-black uppercase tracking-widest transition-all disabled:opacity-50">
                                <span x-show="!mergingDuplicates">Start Merge</span>
                                <span x-show="mergingDuplicates">Merging...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Send to Pipeline Modal --}}
                <div x-show="showSendPipelineModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80 animate-fade-in"
                    x-cloak>
                    <div class="bg-white rounded-sm w-full max-w-md overflow-hidden shadow-xl flex flex-col border border-surface-200"
                        @click.away="showSendPipelineModal = false">

                        {{-- Header --}}
                        <div
                            class="p-5 border-b border-surface-100 bg-surface-50/50 flex items-start justify-between shrink-0">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-surface-900 tracking-tight uppercase">Send to
                                        Pipeline</h3>
                                    <p class="text-[10px] text-surface-500 mt-1 uppercase font-bold tracking-widest"
                                        x-text="sendPipelineContact.name || sendPipelineContact.email"></p>
                                </div>
                            </div>
                            <button @click="showSendPipelineModal = false"
                                class="text-surface-400 hover:text-surface-900 p-1 hover:bg-surface-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="p-6 bg-white space-y-4">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Deal
                                    Title *</label>
                                <input type="text" x-model="sendPipelineContact.title"
                                    class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all">
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Select
                                    Pipeline *</label>
                                <div class="relative">
                                    <select x-model="sendPipelineContact.pipeline_id"
                                        @change="sendPipelineContact.pipeline_stage_id = ''"
                                        class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-surface-900 text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all appearance-none cursor-pointer">
                                        <option value="">-- Choose Pipeline --</option>
                                        <template x-for="pipe in pipelines" :key="pipe.id">
                                            <option :value="pipe.id" x-text="pipe.name"></option>
                                        </template>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-surface-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Select
                                    Stage *</label>
                                <div class="relative">
                                    <select x-model="sendPipelineContact.pipeline_stage_id"
                                        :disabled="!sendPipelineContact.pipeline_id"
                                        class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-surface-900 text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all appearance-none cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        <option value="">-- Choose Stage --</option>
                                        <template x-for="stage in selectedPipelineStages" :key="stage.id">
                                            <option :value="stage.id" x-text="stage.name"></option>
                                        </template>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-surface-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Deal
                                    Value (INR)</label>
                                <input type="number" step="0.01" min="0" x-model="sendPipelineContact.value"
                                    class="w-full px-3 py-2 bg-surface-50 border border-surface-200 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all">
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="p-5 border-t border-surface-100 bg-surface-50 flex justify-end gap-2 shrink-0">
                            <button @click="showSendPipelineModal = false"
                                class="px-5 py-2 bg-white border border-surface-200 text-surface-600 text-[10px] font-black uppercase tracking-widest rounded-sm hover:bg-surface-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="submitSendPipeline()"
                                class="px-5 py-2 bg-brand hover:bg-brand-600 text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all active:scale-95 flex items-center gap-2"
                                :disabled="sendingToPipeline || !sendPipelineContact.pipeline_id || !sendPipelineContact.pipeline_stage_id || !sendPipelineContact.title.trim()">
                                <span x-show="!sendingToPipeline">Create Deal</span>
                                <span x-show="sendingToPipeline">Creating...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Advanced Filter Modal --}}
                <div x-show="showAdvancedFilterModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/90" x-cloak>
                    <div x-show="showAdvancedFilterModal" @click.outside="showAdvancedFilterModal = false"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                        class="bg-white w-full max-w-3xl rounded-sm shadow-2xl overflow-hidden border border-surface-200 flex flex-col max-h-[90vh]">
                        
                        <div class="p-5 border-b border-surface-100 bg-surface-50/50 flex items-start justify-between shrink-0">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-surface-900 tracking-tight uppercase">Dynamic Advanced Filters</h3>
                                    <p class="text-[10px] text-surface-500 mt-1 uppercase font-bold tracking-widest">Build complex multi-condition rules</p>
                                </div>
                            </div>
                            <button @click="showAdvancedFilterModal = false" class="text-surface-400 hover:text-surface-900 p-1 hover:bg-surface-100 rounded-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6 bg-white space-y-4 overflow-y-auto flex-1">
                            <template x-for="(rule, index) in advancedRules" :key="index">
                                <div class="flex items-center gap-3 bg-surface-50 p-3 rounded-sm border border-surface-200">
                                    <select x-model="rule.field" class="w-1/3 text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                                        <option value="">-- Select Field --</option>
                                        <option value="name">Name</option>
                                        <option value="email">Email</option>
                                        <option value="whatsapp_number">WhatsApp Number</option>
                                        <option value="status">Status</option>
                                        <option value="subscription_status">Email Subscription</option>
                                        <option value="whatsapp_subscription_status">WhatsApp Subscription</option>
                                        <option value="created_at">Created Date</option>
                                        <optgroup label="Custom Fields">
                                            @foreach($displayedFields as $field)
                                                <option value="{{ $field }}">{{ ucwords(str_replace('_', ' ', preg_replace('/^custom_/', '', $field))) }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    <select x-model="rule.operator" class="w-1/4 text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                                        <option value="equals">Equals</option>
                                        <option value="not_equals">Not Equals</option>
                                        <option value="contains">Contains</option>
                                        <option value="not_contains">Not Contains</option>
                                        <option value="starts_with">Starts With</option>
                                        <option value="ends_with">Ends With</option>
                                        <option value="is_empty">Is Empty</option>
                                        <option value="is_not_empty">Is Not Empty</option>
                                        <option value="greater_than">Greater Than</option>
                                        <option value="less_than">Less Than</option>
                                    </select>
                                    <input type="text" x-model="rule.value" x-show="!['is_empty', 'is_not_empty'].includes(rule.operator)" class="w-1/3 text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand" placeholder="Value...">
                                    <button @click="removeAdvancedRule(index)" class="p-2 text-red-500 hover:bg-red-50 rounded-sm transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </template>
                            
                            <button @click="addAdvancedRule()" class="px-4 py-2 text-[10px] font-black text-brand uppercase tracking-widest border border-dashed border-brand/50 hover:border-brand hover:bg-brand/5 rounded-sm w-full transition-colors flex items-center justify-center gap-2 mt-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                Add Another Rule
                            </button>
                            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest text-center mt-4">All rules must be met (AND logic)</p>
                        </div>

                        <div class="px-6 py-4 bg-surface-50 border-t border-surface-100 flex items-center justify-between gap-3 shrink-0">
                            <button @click="advancedRules = []; applyAdvancedFilters()" class="px-4 py-2 text-[10px] font-black text-red-500 hover:text-red-700 uppercase tracking-widest transition-colors">Clear All</button>
                            <div class="flex gap-3">
                                <button @click="showAdvancedFilterModal = false" class="px-4 py-2 text-[10px] font-black text-surface-500 hover:text-surface-900 uppercase tracking-widest transition-colors">Cancel</button>
                                <button @click="applyAdvancedFilters()" class="px-5 py-2.5 bg-brand hover:bg-brand/90 text-white rounded-sm text-[10px] font-black uppercase tracking-widest transition-all">
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- Closing teleport wrapper div -->
        </template>


    </div>

    <script>
        function emailListView() {
            return {
                filter: [], segment: [], tag: [], source: [], archived: ['no'], subscription: [], channel: [], wa_status: [], cross_duplicate: [], topic: [], added_by: [],
                search: '', searchField: 'name', selectedIds: [], activeTab: 'contacts', globalSelect: false,
                showSearchOptions: false, showEditModal: false, showImportMoreModal: false, showExportModal: false,
                showAddCustomColumnModal: false, newCustomColumnName: '', addingCustomColumn: false,

                showUnsubscribeModal: false,
                unsubscribeDuration: 'permanent',

                showPermanentDeleteModal: false,
                permanentDeleteReason: 'User requested permanent deletion',
                permanentDeleteConfirmText: '',
                deleteTargetId: null,

                showBulkActionModal: false,
                bulkActionType: '',
                bulkUpdateColumn: '',
                bulkUpdateValue: '',
                bulkPayload: { tags: [], topics: [], pipeline_id: '', stage_id: '', target_list_id: '', note: '', title: '', description: '', due_date: '' },

                showAddChannelModal: false,
                addChannelType: 'email',
                addChannelValue: '',
                addChannelOriginalRowId: '',
                addingChannel: false,

                // Transfer Contact state
                showTransferModal: false,
                transferContact: { id: null, name: '', email: '', target_list_id: '' },
                transferring: false,

                // Send to Pipeline state
                showSendPipelineModal: false,
                sendPipelineContact: { id: null, name: '', email: '', title: '', value: 0, pipeline_id: '', pipeline_stage_id: '' },
                sendingToPipeline: false,
                pipelines: @js($pipelines),

                // Contact Profile State
                showProfileSlideover: false,
                profileLoading: false,
                profileContact: null,
                profileActiveTab: 'details',
                newNoteContent: '',
                newTask: { title: '', description: '', due_date: '' },
                addingNote: false,
                addingTask: false,

                // Merge State
                showMergeModal: false,
                mergeBy: 'email',
                mergingDuplicates: false,

                // Advanced Filter State
                showAdvancedFilterModal: false,
                advancedRules: [],

                exportFormat: 'xlsx', exportFilename: '{{ Str::slug($emailList->name) }}_export_{{ now()->format('Ymd') }}',
                consolidate: false,
                adding: false, saving: false, scrubbing: false, importJustCompleted: false,
                newContact: { email: '', name: '', whatsapp_number: '', segment_name: '', tags: '', signup_source: 'Manual Entry' },
                editingContact: { id: null, email: '', name: '', subscription_status: '', meta: {} },
                stats: {
                    full_total: {{ $stats['total'] }},
                    global_valid: {{ $stats['valid'] }},
                    global_invalid: {{ $stats['invalid'] }},
                    global_duplicate: {{ $stats['duplicate'] }},
                    cross_duplicate: {{ $emailList->cross_duplicate_count ?? 0 }},
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
                    whatsapp_unsubscribed: {{ $stats['whatsapp_unsubscribed'] ?? 0 }},
                    bounced: {{ $stats['bounced'] ?? 0 }},
                    archived: {{ $stats['archived'] ?? 0 }},
                    status: '{{ $emailList->status }}',

                    // Advanced CRM metrics
                    global_main_rows: {{ $stats['global_main_rows'] ?? 0 }},
                    total_emails: {{ $stats['total_emails'] ?? 0 }},
                    subscribed_emails: {{ $stats['subscribed_emails'] ?? 0 }},
                    total_whatsapps: {{ $stats['total_whatsapps'] ?? 0 }},
                    subscribed_whatsapps: {{ $stats['subscribed_whatsapps'] ?? 0 }},
                    is_filtered: false,
                    filtered_main_rows: 0
                },

                init() {
                    if (this.stats.status === 'processing') {
                        this.pollStatus();
                    }
                },

                resetFilters() {
                    this.filter = []; this.segment = []; this.tag = []; this.topic = []; this.source = []; this.archived = []; this.subscription = []; this.channel = []; this.wa_status = []; this.added_by = [];
                    this.advancedRules = [];
                    this.search = ''; this.searchField = 'name'; this.selectedIds = []; this.fetchEmails();
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
                            payload: this.bulkPayload,
                            filters: {
                                status: this.filter, search: this.search, search_field: this.searchField,
                                segment: this.segment, tag: this.tag, source: this.source,
                                archived: this.archived, subscription: this.subscription,
                                channel: this.channel, wa_status: this.wa_status, topic: this.topic, added_by: this.added_by
                            }
                        })
                    }).then(r => r.json()).then((res) => {
                        this.selectedIds = [];
                        this.globalSelect = false;
                        this.showBulkActionModal = false;
                        if(res.success && res.message) alert(res.message);
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
                            archived: this.archived, subscription: this.subscription,
                            channel: this.channel, wa_status: this.wa_status, topic: this.topic, added_by: this.added_by,
                            advanced_rules: this.advancedRules
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
                            this.stats.whatsapp_unsubscribed = data.stats.whatsapp_unsubscribed;
                            this.stats.bounced = data.stats.bounced;

                            // Advanced CRM metrics
                            this.stats.global_main_rows = data.stats.global_main_rows;
                            this.stats.total_emails = data.stats.total_emails;
                            this.stats.subscribed_emails = data.stats.subscribed_emails;
                            this.stats.total_whatsapps = data.stats.total_whatsapps;
                            this.stats.subscribed_whatsapps = data.stats.subscribed_whatsapps;
                            this.stats.is_filtered = data.stats.is_filtered;
                            this.stats.filtered_main_rows = data.stats.filtered_main_rows;
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
                    url.searchParams.set('format', this.exportFormat); 
                    url.searchParams.set('filename', this.exportFilename);
                    url.searchParams.set('search', this.search);
                    url.searchParams.set('search_field', this.searchField);
                    url.searchParams.set('consolidate', this.consolidate ? '1' : '0');
                    
                    const appendArray = (key, arr) => {
                        if (Array.isArray(arr)) {
                            arr.forEach(v => url.searchParams.append(`${key}[]`, v));
                        } else if (arr) {
                            url.searchParams.set(key, arr);
                        }
                    };

                    appendArray('status', this.filter);
                    appendArray('segment', this.segment);
                    appendArray('tag', this.tag);
                    appendArray('source', this.source);
                    appendArray('archived', this.archived);
                    appendArray('topic', this.topic);
                    appendArray('subscription', this.subscription);
                    appendArray('channel', this.channel);
                    appendArray('wa_status', this.wa_status);
                    appendArray('added_by', this.added_by);

                    window.location.href = url.toString(); 
                    this.showExportModal = false;
                },

                executeBulkAction() {
                    const count = this.globalSelect
                        ? this.stats.total
                        : this.selectedIds.length;

                    if (!count) return;

                    let actionName = this.bulkActionType;
                    let confirmMsg = `Are you sure you want to ${actionName} ${count.toLocaleString()} contact(s)?`;

                    if (actionName === 'delete') {
                        if (this.permanentDeleteConfirmText !== 'PERMANENT DELETE' || !this.permanentDeleteReason.trim()) return;
                        confirmMsg = `WARNING: You are about to PERMANENTLY DELETE ${count.toLocaleString()} contact(s). Proceed?`;
                    } else if (actionName === 'update_column') {
                        if (!this.bulkUpdateColumn) return;
                        confirmMsg = `Are you sure you want to update the column '${this.bulkUpdateColumn}' for ${count.toLocaleString()} contact(s)?`;
                    } else if (actionName === 'archive') {
                        confirmMsg = `Are you sure you want to move ${count.toLocaleString()} contact(s) to archive?`;
                    } else if (actionName === 'unarchive') {
                        confirmMsg = `Are you sure you want to restore ${count.toLocaleString()} contact(s) to active?`;
                    }

                    if (!confirm(confirmMsg)) return;

                    fetch(`{{ route('admin.email-lists.bulk-action', $emailList) }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            ids: this.selectedIds,
                            action: actionName,
                            duration: this.unsubscribeDuration, // for unsubscribe
                            delete_reason: this.permanentDeleteReason, // for delete
                            target_column: this.bulkUpdateColumn, // for update_column
                            new_value: this.bulkUpdateValue, // for update_column
                            global: this.globalSelect,
                            filters: {
                                status: this.filter, search: this.search, search_field: this.searchField,
                                segment: this.segment, tag: this.tag, source: this.source,
                                archived: this.archived, subscription: this.subscription,
                                channel: this.channel, wa_status: this.wa_status, topic: this.topic, added_by: this.added_by
                            }
                        })
                    }).then(() => {
                        this.selectedIds = [];
                        this.globalSelect = false;
                        this.showBulkActionModal = false;

                        // Reset modal state
                        this.bulkActionType = '';
                        this.bulkUpdateColumn = '';
                        this.bulkUpdateValue = '';
                        this.permanentDeleteConfirmText = '';
                        this.permanentDeleteReason = 'User requested permanent deletion';

                        this.fetchEmails();
                        this.refreshStats();
                    });
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
                            cross_duplicate: data.cross_duplicate_count || 0,
                            import_progress: data.import_progress,
                            import_details: data.import_details,
                            global_main_rows: data.global_main_rows || 0,
                            total_emails: data.total_emails || 0,
                            subscribed_emails: data.subscribed_emails || 0,
                            total_whatsapps: data.total_whatsapps || 0,
                            subscribed_whatsapps: data.subscribed_whatsapps || 0,
                            whatsapp_unsubscribed: data.whatsapp_unsubscribed || 0,
                            is_filtered: this.stats.is_filtered,
                            filtered_main_rows: this.stats.filtered_main_rows
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
                            this.stats.whatsapp_unsubscribed = data.whatsapp_unsubscribed_count;
                            this.stats.bounced = data.bounced_count;
                            this.stats.hard_bounce = data.hard_bounce_count;
                            this.stats.soft_bounce = data.soft_bounce_count;
                            this.stats.complaints = data.complaint_count;
                            this.stats.risky = data.risky_count;
                            this.stats.disposable = data.disposable_count;
                            this.stats.role_based = data.role_based_count;
                            this.stats.suspicious = data.suspicious_count;
                            this.stats.archived = data.archived_count;
                            this.stats.cross_duplicate = data.cross_duplicate_count || 0;
                            this.stats.import_progress = data.import_progress;
                            this.stats.import_details = data.import_details;

                            this.stats.global_main_rows = data.global_main_rows || 0;
                            this.stats.total_emails = data.total_emails || 0;
                            this.stats.subscribed_emails = data.subscribed_emails || 0;
                            this.stats.total_whatsapps = data.total_whatsapps || 0;
                            this.stats.subscribed_whatsapps = data.subscribed_whatsapps || 0;

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

                singleAction(action, id) {
                    fetch('{{ route("admin.email-lists.bulk-action", $emailList) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            ids: [id],
                            action: action,
                            global: false,
                            filters: {}
                        })
                    }).then(() => {
                        this.fetchEmails();
                        this.refreshStats();
                    });
                },

                addCustomColumn() {
                    if (!this.newCustomColumnName.trim()) {
                        alert('Please enter a column name.');
                        return;
                    }
                    this.addingCustomColumn = true;
                    fetch('{{ route("admin.email-lists.add-custom-column", $emailList) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ column_name: this.newCustomColumnName })
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error adding column');
                            this.addingCustomColumn = false;
                        }
                    }).catch(() => {
                        alert('Network error');
                        this.addingCustomColumn = false;
                    });
                },

                archiveEmail(id) {
                    if (!confirm('Are you sure you want to archive this contact? It will no longer be visible in your active lists or billing counts.')) return;
                    this.singleAction('archive', id);
                },

                unarchiveEmail(id) {
                    this.singleAction('unarchive', id);
                },

                openPermanentDeleteModal(id = null) {
                    this.deleteTargetId = id;
                    this.permanentDeleteReason = 'User requested permanent deletion';
                    this.permanentDeleteConfirmText = '';
                    this.showPermanentDeleteModal = true;
                },

                openAddContact(detail) {
                    if (!detail.original_row_id) {
                        alert('Cannot add sub-row because main row ID is missing.');
                        return;
                    }
                    this.addChannelType = detail.type; // 'email' or 'whatsapp'
                    this.addChannelOriginalRowId = detail.original_row_id;
                    this.addChannelValue = '';
                    this.showAddChannelModal = true;
                },

                submitAddChannel() {
                    this.addingChannel = true;
                    const payload = { original_row_id: this.addChannelOriginalRowId };
                    if (this.addChannelType === 'email') payload.email = this.addChannelValue;
                    else payload.whatsapp_number = this.addChannelValue;

                    fetch('{{ route("admin.email-lists.add-alternate-channel", $emailList) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(payload)
                    }).then(r => r.json()).then(res => {
                        this.addingChannel = false;
                        if (res.success) {
                            this.showAddChannelModal = false;
                            this.fetchEmails();
                            this.refreshStats();
                        } else {
                            alert(res.message || 'An error occurred.');
                        }
                    }).catch(() => { this.addingChannel = false; });
                },

                openTransferContact(contact) {
                    this.transferContact = {
                        id: contact.id,
                        name: contact.name || '',
                        email: contact.email || '',
                        target_list_id: ''
                    };
                    this.showTransferModal = true;
                },

                submitTransferContact() {
                    if (!this.transferContact.target_list_id) {
                        alert('Please select a destination list.');
                        return;
                    }
                    this.transferring = true;
                    fetch(`{{ route('admin.email-lists.transfer-contact', [$emailList, ':id']) }}`.replace(':id', this.transferContact.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ target_list_id: this.transferContact.target_list_id })
                    })
                        .then(r => r.json())
                        .then(res => {
                            this.transferring = false;
                            if (res.success) {
                                this.showTransferModal = false;
                                this.fetchEmails();
                                this.refreshStats();
                            } else {
                                alert(res.message || 'An error occurred during transfer.');
                            }
                        })
                        .catch(() => { this.transferring = false; alert('Failed to transfer contact.'); });
                },

                openSendPipeline(contact) {
                    this.sendPipelineContact = {
                        id: contact.id,
                        name: contact.name || '',
                        email: contact.email || '',
                        title: (contact.name || contact.email || 'Contact') + ' Deal',
                        value: 0,
                        pipeline_id: '',
                        pipeline_stage_id: ''
                    };
                    this.showSendPipelineModal = true;
                },

                submitSendPipeline() {
                    if (!this.sendPipelineContact.pipeline_id || !this.sendPipelineContact.pipeline_stage_id) {
                        alert('Please select a pipeline and stage.');
                        return;
                    }
                    this.sendingToPipeline = true;
                    fetch(`{{ route('admin.email-lists.send-to-pipeline', [$emailList, ':id']) }}`.replace(':id', this.sendPipelineContact.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.sendPipelineContact)
                    })
                        .then(r => r.json())
                        .then(res => {
                            this.sendingToPipeline = false;
                            if (res.success) {
                                this.showSendPipelineModal = false;
                                alert('Deal created successfully in the pipeline stage!');
                            } else {
                                alert(res.message || 'An error occurred while creating the deal.');
                            }
                        })
                        .catch(() => { this.sendingToPipeline = false; alert('Failed to send contact to pipeline.'); });
                },

                get selectedPipelineStages() {
                    if (!this.sendPipelineContact.pipeline_id) return [];
                    const p = this.pipelines.find(x => x.id == this.sendPipelineContact.pipeline_id);
                    return p ? (p.stages || []) : [];
                },

                confirmPermanentDelete() {
                    if (this.permanentDeleteConfirmText !== 'PERMANENT DELETE') {
                        alert('Please type PERMANENT DELETE to confirm.');
                        return;
                    }
                    if (!this.permanentDeleteReason.trim()) {
                        alert('Please provide a reason for permanent deletion.');
                        return;
                    }

                    if (this.deleteTargetId) {
                        // Single delete
                        fetch(`{{ route('admin.email-lists.destroy-email', [$emailList, ':id']) }}`.replace(':id', this.deleteTargetId), {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ reason: this.permanentDeleteReason })
                        }).then(r => r.json()).then(data => {
                            this.showPermanentDeleteModal = false;
                            if (data.success) {
                                this.fetchEmails();
                                this.refreshStats();
                            }
                        });
                    } else {
                        // Bulk delete
                        fetch('{{ route("admin.email-lists.bulk-action", $emailList) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({
                                ids: this.selectedIds,
                                action: 'permanent_delete',
                                reason: this.permanentDeleteReason,
                                global: this.globalSelect,
                                filters: {
                                    status: this.filter, search: this.search, search_field: this.searchField,
                                    segment: this.segment, tag: this.tag, source: this.source,
                                    archived: this.archived, subscription: this.subscription,
                                    channel: this.channel, wa_status: this.wa_status
                                }
                            })
                        }).then(() => {
                            this.selectedIds = [];
                            this.globalSelect = false;
                            this.showPermanentDeleteModal = false;
                            this.fetchEmails();
                            this.refreshStats();
                        });
                    }
                },

                // Profile Methods
                openProfile(id) {
                    this.profileLoading = true;
                    this.showProfileSlideover = true;
                    this.profileActiveTab = 'details';
                    this.profileContact = null;
                    
                    fetch(`{{ route('admin.email-lists.contact.profile', [$emailList, ':id']) }}`.replace(':id', id))
                        .then(r => r.json())
                        .then(data => {
                            this.profileContact = data;
                            this.profileLoading = false;
                        })
                        .catch(() => {
                            alert('Failed to load profile.');
                            this.showProfileSlideover = false;
                            this.profileLoading = false;
                        });
                },

                closeProfile() {
                    this.showProfileSlideover = false;
                    setTimeout(() => { this.profileContact = null; }, 300);
                },

                addNote() {
                    if (!this.newNoteContent.trim() || !this.profileContact) return;
                    this.addingNote = true;
                    fetch(`{{ route('admin.email-lists.contact.note', [$emailList, ':id']) }}`.replace(':id', this.profileContact.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ content: this.newNoteContent })
                    }).then(r => r.json()).then(res => {
                        this.addingNote = false;
                        if (res.success) {
                            this.newNoteContent = '';
                            this.profileContact.notes.unshift(res.note);
                        }
                    }).catch(() => this.addingNote = false);
                },

                addTask() {
                    if (!this.newTask.title.trim() || !this.profileContact) return;
                    this.addingTask = true;
                    fetch(`{{ route('admin.email-lists.contact.task', [$emailList, ':id']) }}`.replace(':id', this.profileContact.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify(this.newTask)
                    }).then(r => r.json()).then(res => {
                        this.addingTask = false;
                        if (res.success) {
                            this.newTask = { title: '', description: '', due_date: '' };
                            this.profileContact.tasks.unshift(res.task);
                        }
                    }).catch(() => this.addingTask = false);
                },

                submitMergeDuplicates() {
                    this.mergingDuplicates = true;
                    fetch('{{ route("admin.email-lists.merge-duplicates", $emailList) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ merge_by: this.mergeBy })
                    }).then(r => r.json()).then(res => {
                        this.mergingDuplicates = false;
                        this.showMergeModal = false;
                        if (res.success) {
                            alert(res.message);
                            this.fetchEmails();
                            this.refreshStats();
                        } else {
                            alert(res.message || 'Error merging duplicates.');
                        }
                    }).catch(() => {
                        this.mergingDuplicates = false;
                        alert('Failed to merge duplicates.');
                    });
                },

                addAdvancedRule() {
                    this.advancedRules.push({ field: '', operator: 'equals', value: '' });
                },

                removeAdvancedRule(index) {
                    this.advancedRules.splice(index, 1);
                },

                applyAdvancedFilters() {
                    // Filter out empty rules
                    this.advancedRules = this.advancedRules.filter(r => r.field !== '' && r.operator !== '');
                    this.showAdvancedFilterModal = false;
                    this.fetchEmails();
                }

            };
        }
    </script>
@endsection