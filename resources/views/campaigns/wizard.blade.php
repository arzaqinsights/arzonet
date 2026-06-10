@extends('layouts.fullscreen-builder')
@section('title', 'Campaign Builder')

@section('content')
<div x-data="mailchimpWizard()" class="h-screen flex flex-col overflow-hidden bg-white">
    {{-- True Full-Screen Top Header --}}
    <header class="h-16 border-b border-slate-200 bg-white px-8 flex items-center justify-between shrink-0 z-40">
        <div class="flex items-center gap-6">
            <a href="{{ route('admin.campaigns.index') }}" class="text-slate-400 hover:text-slate-900 transition-colors p-1.5 hover:bg-slate-50 rounded-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            
            <div class="flex items-center gap-3">
                <div class="relative group flex items-center gap-2">
                    <template x-if="!editingName">
                        <div @click="editingName = true; $nextTick(() => $refs.nameInput.focus())" 
                             class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 px-2 py-1 rounded-sm transition-all">
                            <span class="text-lg font-bold text-slate-800" x-text="campaign.name || 'Untitled Campaign'"></span>
                            <svg class="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </div>
                    </template>
                    <template x-if="editingName">
                        <input x-ref="nameInput" 
                               type="text" 
                               x-model="campaign.name" 
                               @blur="editingName = false; save()" 
                               @keydown.enter="editingName = false; save()"
                               class="text-lg font-bold text-slate-800 bg-white border-b-2 border-slate-900 outline-none px-1 py-0.5 min-w-[300px]">
                    </template>
                </div>
                <span class="bg-slate-100 text-[10px] px-2 py-0.5 rounded-sm font-black text-slate-500 uppercase tracking-wider" x-text="campaign.status"></span>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div x-show="isSaving" class="flex items-center gap-2 text-slate-400">
                <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-bold uppercase tracking-wider">Saving...</span>
            </div>
            
            <a href="{{ route('admin.campaigns.index') }}" class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-900 transition-colors">Finish Later</a>
            
            <form action="{{ route('admin.campaigns.send', $campaign) }}" method="POST">
                @csrf
                <button type="submit" 
                        :disabled="!isReady()"
                        class="px-8 py-2 rounded-sm font-bold text-sm transition-all"
                        :class="isReady() ? 'bg-slate-900 text-white hover:bg-black' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                        x-text="sendAction === 'schedule' ? 'Schedule Campaign' : 'Send Campaign'">
                </button>
            </form>
        </div>
    </header>

    {{-- Main Workspace Split --}}
    <div class="flex-1 flex overflow-hidden">
        
        {{-- Left Panel: Accordion Checklist (60% width) --}}
        <div class="w-full lg:w-7/12 border-r border-slate-200 overflow-y-auto bg-slate-50/50 px-8 py-8">
            <div class="max-w-3xl mx-auto space-y-6">
                
                {{-- 1. TO SECTION --}}
                <div class="bg-white rounded-sm border transition-all duration-300"
                     :class="editing === 'to' ? 'border-slate-800 ring-1 ring-slate-800' : 'border-slate-200 hover:border-slate-300'">
                    <div class="p-6">
                        {{-- Header Row --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Check Status Icon --}}
                                <div class="shrink-0">
                                    <template x-if="isAudienceReady()">
                                        <div class="w-7 h-7 rounded-sm bg-emerald-50 flex items-center justify-center border border-emerald-200 text-emerald-600">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!isAudienceReady()">
                                        <div class="w-7 h-7 rounded-sm border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-xs">1</div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">To</h3>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">Who are you sending this campaign to?</p>
                                </div>
                            </div>
                            <div>
                                <button @click="toggleEdit('to')" class="px-4 py-1.5 border border-slate-200 hover:border-slate-900 rounded-sm font-bold text-xs transition-colors bg-white hover:bg-slate-50" x-text="editing === 'to' ? 'Cancel' : 'Edit'"></button>
                            </div>
                        </div>

                        {{-- Collapsed State Info --}}
                        <div x-show="editing !== 'to'" class="mt-4 pl-11">
                            <p class="text-sm text-slate-700 font-medium" x-text="getAudienceSummary() || 'Define your target audience'"></p>
                            <template x-if="estimatedRecipients !== null">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-slate-100 text-slate-800 text-xs font-bold mt-2 border border-slate-200">
                                    <span>Estimated Recipients:</span>
                                    <span class="text-slate-900 font-black" x-text="estimatedRecipients.toLocaleString()"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Expanded Edit State --}}
                        <div x-show="editing === 'to'" class="mt-6 pl-11 pt-6 border-t border-slate-100 space-y-6" x-transition>
                            {{-- Email List Selection --}}
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block">Select Target Email List</label>
                                <select x-model="campaign.email_list_id" 
                                        @change="changeList()" 
                                        class="w-full px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950 bg-white cursor-pointer">
                                    @foreach($emailLists as $list)
                                    <option value="{{ $list->id }}">
                                        {{ $list->name }} ({{ $list->emails_count ?? 0 }} subscribers)
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Topic Selection --}}
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block">Subscription Topic (Preference Center)</label>
                                <select x-model="campaign.subscription_topic_id" 
                                        @change="save()" 
                                        class="w-full px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-900 bg-white cursor-pointer">
                                    <option value="">All Topics (Sends to general audience)</option>
                                    @foreach($subscriptionTopics as $topic)
                                    <option value="{{ $topic->id }}">
                                        {{ $topic->name }}@if($topic->description) — {{ $topic->description }}@endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Target Specific Audience --}}
                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-950 uppercase tracking-widest">Target Specific Audience (Optional)</label>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-sm text-[9px] font-black uppercase tracking-wider border border-blue-100">INCLUDES</span>
                                </div>
                                <p class="text-xs text-slate-500 font-medium">If left blank, sends to ALL subscribers. If selected, sends ONLY to matching tag or segment.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Include Tags --}}
                                    <div class="border border-slate-200 p-4 rounded-sm bg-white max-h-48 overflow-auto">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-slate-100">Tags to Include</div>
                                        <div class="space-y-2">
                                            @foreach($allTags as $tag)
                                            <label class="flex items-center gap-2.5 cursor-pointer text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                <input type="checkbox" value="{{ $tag }}" x-model="include_tags" @change="save()" class="rounded-sm border-slate-300 text-slate-900 focus:ring-0 focus:ring-offset-0">
                                                <span class="font-medium">{{ $tag }}</span>
                                            </label>
                                            @endforeach
                                            @if(empty($allTags)) <div class="text-xs text-slate-400 italic">No tags found.</div> @endif
                                        </div>
                                    </div>

                                    {{-- Include Segments --}}
                                    <div class="border border-slate-200 p-4 rounded-sm bg-white max-h-48 overflow-auto">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-slate-100">Segments to Include</div>
                                        <div class="space-y-2">
                                            @foreach($allSegments as $segment)
                                            <label class="flex items-center gap-2.5 cursor-pointer text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                <input type="checkbox" value="{{ $segment }}" x-model="include_segments" @change="save()" class="rounded-sm border-slate-300 text-slate-900 focus:ring-0 focus:ring-offset-0">
                                                <span class="font-medium">{{ $segment }}</span>
                                            </label>
                                            @endforeach
                                            @if(empty($allSegments)) <div class="text-xs text-slate-400 italic">No segments found.</div> @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Skip Specific Audience --}}
                            <div class="space-y-4 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-950 uppercase tracking-widest">Skip Specific Audience (Optional)</label>
                                    <span class="px-2 py-0.5 bg-rose-50 text-rose-600 rounded-sm text-[9px] font-black uppercase tracking-wider border border-rose-100">EXCLUDES</span>
                                </div>
                                <p class="text-xs text-slate-500 font-medium">Subscribers matching ANY of these will NOT receive the campaign, overriding includes.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Exclude Tags --}}
                                    <div class="border border-slate-200 p-4 rounded-sm bg-white max-h-48 overflow-auto">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-slate-100">Tags to Exclude</div>
                                        <div class="space-y-2">
                                            @foreach($allTags as $tag)
                                            <label class="flex items-center gap-2.5 cursor-pointer text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                <input type="checkbox" value="{{ $tag }}" x-model="exclude_tags" @change="save()" class="rounded-sm border-slate-300 text-rose-600 focus:ring-0 focus:ring-offset-0">
                                                <span class="font-medium">{{ $tag }}</span>
                                            </label>
                                            @endforeach
                                            @if(empty($allTags)) <div class="text-xs text-slate-400 italic">No tags found.</div> @endif
                                        </div>
                                    </div>

                                    {{-- Exclude Segments --}}
                                    <div class="border border-slate-200 p-4 rounded-sm bg-white max-h-48 overflow-auto">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-slate-100">Segments to Exclude</div>
                                        <div class="space-y-2">
                                            @foreach($allSegments as $segment)
                                            <label class="flex items-center gap-2.5 cursor-pointer text-sm text-slate-700 hover:text-slate-900 transition-colors">
                                                <input type="checkbox" value="{{ $segment }}" x-model="exclude_segments" @change="save()" class="rounded-sm border-slate-300 text-rose-600 focus:ring-0 focus:ring-offset-0">
                                                <span class="font-medium">{{ $segment }}</span>
                                            </label>
                                            @endforeach
                                            @if(empty($allSegments)) <div class="text-xs text-slate-400 italic">No segments found.</div> @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Quantity Limit --}}
                            <div class="pt-4 border-t border-slate-100 space-y-4">
                                <label class="flex items-center gap-3 p-4 border border-slate-250 hover:border-slate-400 rounded-sm cursor-pointer hover:bg-slate-50/50 transition-all" :class="limit_enabled ? 'bg-slate-50 border-slate-900 ring-1 ring-slate-900' : ''">
                                    <input type="checkbox" x-model="limit_enabled" @change="if(!limit_enabled) limit = ''; save()" class="w-5 h-5 rounded-sm border-slate-350 text-slate-950 focus:ring-0 focus:ring-offset-0">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">Limit Recipient Quantity</div>
                                        <p class="text-xs text-slate-400 mt-0.5">Send only to a specific number of random contacts</p>
                                    </div>
                                </label>
                                <div x-show="limit_enabled" class="pl-8" x-transition>
                                    <input type="number" x-model="limit" @input.debounce.1000ms="save()" placeholder="e.g. 2500" class="w-full md:w-1/2 px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950" min="1">
                                </div>
                            </div>

                            {{-- Save Button --}}
                            <div class="pt-4 flex items-center justify-end">
                                <button @click="editing = null" class="px-6 py-2.5 bg-slate-900 text-white rounded-sm font-bold text-xs hover:bg-black transition-colors">Save Audience</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. FROM SECTION --}}
                <div class="bg-white rounded-sm border transition-all duration-300"
                     :class="editing === 'from' ? 'border-slate-800 ring-1 ring-slate-800' : 'border-slate-200 hover:border-slate-300'">
                    <div class="p-6">
                        {{-- Header Row --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Check Status Icon --}}
                                <div class="shrink-0">
                                    <template x-if="isFromReady()">
                                        <div class="w-7 h-7 rounded-sm bg-emerald-50 flex items-center justify-center border border-emerald-200 text-emerald-600">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!isFromReady()">
                                        <div class="w-7 h-7 rounded-sm border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-xs">2</div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">From</h3>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">Who is sending this campaign?</p>
                                </div>
                            </div>
                            <div>
                                <button @click="toggleEdit('from')" class="px-4 py-1.5 border border-slate-200 hover:border-slate-900 rounded-sm font-bold text-xs transition-colors bg-white hover:bg-slate-50" x-text="editing === 'from' ? 'Cancel' : 'Edit'"></button>
                            </div>
                        </div>

                        {{-- Collapsed State Info --}}
                        <div x-show="editing !== 'from'" class="mt-4 pl-11">
                            <p class="text-sm text-slate-700 font-medium" x-text="getFromSummary() || 'Define who emails are sent from'"></p>
                        </div>

                        {{-- Expanded Edit State --}}
                        <div x-show="editing === 'from'" class="mt-6 pl-11 pt-6 border-t border-slate-100 space-y-5" x-transition>
                            
                            {{-- Suggestions Chips --}}
                            <div class="space-y-2" x-show="senders.length > 0">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Quick Select Verified Senders</label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="s in senders">
                                        <button type="button" 
                                                @click="campaign.from_name = s.from_name || ''; campaign.from_email = s.email; checkDomainAndSave()" 
                                                class="px-3 py-1.5 text-xs border border-slate-200 hover:border-slate-800 rounded-sm bg-white text-slate-700 font-medium transition-all" 
                                                x-text="s.from_name ? s.from_name + ' <' + s.email + '>' : s.email">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sender Name</label>
                                    <input type="text" x-model="campaign.from_name" @input.debounce.500ms="save()" class="w-full px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950" placeholder="e.g. Indrajit from Arzonet">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sender Email</label>
                                    <input type="email" x-model="campaign.from_email" @input.debounce.500ms="checkDomainAndSave()" class="w-full px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950" placeholder="e.g. hello@yourdomain.com">
                                </div>
                            </div>

                            {{-- Domain Warning --}}
                            <div x-show="domainWarning" class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-sm text-xs flex items-start gap-2.5" x-transition>
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5 shrink-0"></i>
                                <span class="font-bold" x-text="domainWarning"></span>
                            </div>

                            {{-- Save Button --}}
                            <div class="pt-2 flex items-center justify-end">
                                <button @click="editing = null" class="px-6 py-2.5 bg-slate-900 text-white rounded-sm font-bold text-xs hover:bg-black transition-colors">Save Sender</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. SUBJECT SECTION --}}
                <div class="bg-white rounded-sm border transition-all duration-300"
                     :class="editing === 'subject' ? 'border-slate-800 ring-1 ring-slate-800' : 'border-slate-200 hover:border-slate-300'">
                    <div class="p-6">
                        {{-- Header Row --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Check Status Icon --}}
                                <div class="shrink-0">
                                    <template x-if="campaign.subject">
                                        <div class="w-7 h-7 rounded-sm bg-emerald-50 flex items-center justify-center border border-emerald-200 text-emerald-600">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!campaign.subject">
                                        <div class="w-7 h-7 rounded-sm border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-xs">3</div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Subject</h3>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">What is the subject line for this email?</p>
                                </div>
                            </div>
                            <div>
                                <button @click="toggleEdit('subject')" class="px-4 py-1.5 border border-slate-200 hover:border-slate-900 rounded-sm font-bold text-xs transition-colors bg-white hover:bg-slate-50" x-text="editing === 'subject' ? 'Cancel' : 'Edit'"></button>
                            </div>
                        </div>

                        {{-- Collapsed State Info --}}
                        <div x-show="editing !== 'subject'" class="mt-4 pl-11">
                            <p class="text-sm text-slate-700 font-medium" x-text="campaign.subject || 'Define campaign subject line'"></p>
                        </div>

                        {{-- Expanded Edit State --}}
                        <div x-show="editing === 'subject'" class="mt-6 pl-11 pt-6 border-t border-slate-100 space-y-4" x-transition>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Subject Line</label>
                                <input type="text" x-model="campaign.subject" @input.debounce.1000ms="save()" class="w-full px-4 py-3.5 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950" placeholder="e.g. Something exciting inside just for you!">
                                <p class="text-[10px] text-slate-400 font-medium leading-relaxed">
                                    <i class="fa-solid fa-wand-magic-sparkles text-slate-400 mr-1"></i> Personalization tag: Use <code class="bg-slate-100 text-slate-800 px-1 py-0.5 rounded-sm font-mono font-bold">@{{name}}</code> or <code class="bg-slate-100 text-slate-800 px-1 py-0.5 rounded-sm font-mono font-bold">@{{email}}</code> to customize the subject dynamically.
                                </p>
                            </div>

                            {{-- Save Button --}}
                            <div class="pt-2 flex items-center justify-end">
                                <button @click="editing = null" class="px-6 py-2.5 bg-slate-900 text-white rounded-sm font-bold text-xs hover:bg-black transition-colors">Save Subject</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. SEND TIME SECTION --}}
                <div class="bg-white rounded-sm border transition-all duration-300"
                     :class="editing === 'time' ? 'border-slate-800 ring-1 ring-slate-800' : 'border-slate-200 hover:border-slate-300'">
                    <div class="p-6">
                        {{-- Header Row --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Check Status Icon --}}
                                <div class="shrink-0">
                                    <template x-if="isTimeReady()">
                                        <div class="w-7 h-7 rounded-sm bg-emerald-50 flex items-center justify-center border border-emerald-200 text-emerald-600">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!isTimeReady()">
                                        <div class="w-7 h-7 rounded-sm border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-xs">4</div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Send Time</h3>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">When should we send this campaign?</p>
                                </div>
                            </div>
                            <div>
                                <button @click="toggleEdit('time')" class="px-4 py-1.5 border border-slate-200 hover:border-slate-900 rounded-sm font-bold text-xs transition-colors bg-white hover:bg-slate-50" x-text="editing === 'time' ? 'Cancel' : 'Edit'"></button>
                            </div>
                        </div>

                        {{-- Collapsed State Info --}}
                        <div x-show="editing !== 'time'" class="mt-4 pl-11">
                            <p class="text-sm text-slate-700 font-medium" x-text="getTimeSummary()"></p>
                        </div>

                        {{-- Expanded Edit State --}}
                        <div x-show="editing === 'time'" class="mt-6 pl-11 pt-6 border-t border-slate-100 space-y-5" x-transition>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Send Now --}}
                                <label class="flex flex-col gap-2 p-5 border border-slate-200 rounded-sm cursor-pointer transition-all hover:bg-slate-50/50" :class="sendAction === 'now' ? 'bg-slate-50 border-slate-900 ring-1 ring-slate-900' : ''">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" x-model="sendAction" value="now" @change="campaign.scheduled_at = null; save()" class="form-radio text-slate-900 w-5 h-5 focus:ring-0">
                                        <span class="font-bold text-slate-900">Send Now</span>
                                    </div>
                                    <p class="text-xs text-slate-400 ml-8">Dispatch the email campaign immediately upon scheduling/clicking Send</p>
                                </label>
                                
                                {{-- Schedule Later --}}
                                <label class="flex flex-col gap-2 p-5 border border-slate-200 rounded-sm cursor-pointer transition-all hover:bg-slate-50/50" :class="sendAction === 'schedule' ? 'bg-slate-50 border-slate-900 ring-1 ring-slate-900' : ''">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" x-model="sendAction" value="schedule" class="form-radio text-slate-900 w-5 h-5 focus:ring-0">
                                        <span class="font-bold text-slate-900">Schedule for later</span>
                                    </div>
                                    <p class="text-xs text-slate-400 ml-8">Choose a future date and time to dispatch the emails automatically</p>
                                </label>
                            </div>

                            {{-- Datetime Picker --}}
                            <div x-show="sendAction === 'schedule'" class="space-y-2 pt-2" x-transition>
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block">Choose Date & Time</label>
                                <input type="datetime-local" x-model="campaign.scheduled_at" @change="save()" class="w-full md:w-1/2 px-4 py-3 border border-slate-200 rounded-sm text-sm focus:outline-none focus:border-slate-950">
                            </div>

                            {{-- Save Button --}}
                            <div class="pt-2 flex items-center justify-end">
                                <button @click="editing = null" class="px-6 py-2.5 bg-slate-900 text-white rounded-sm font-bold text-xs hover:bg-black transition-colors">Save Send Time</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. CONTENT / TEMPLATE SECTION --}}
                <div class="bg-white rounded-sm border transition-all duration-300"
                     :class="editing === 'content' ? 'border-slate-800 ring-1 ring-slate-800' : 'border-slate-200 hover:border-slate-300'">
                    <div class="p-6">
                        {{-- Header Row --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Check Status Icon --}}
                                <div class="shrink-0">
                                    <template x-if="campaign.template_id">
                                        <div class="w-7 h-7 rounded-sm bg-emerald-50 flex items-center justify-center border border-emerald-200 text-emerald-600">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    </template>
                                    <template x-if="!campaign.template_id">
                                        <div class="w-7 h-7 rounded-sm border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 font-bold text-xs">5</div>
                                    </template>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Content</h3>
                                    <p class="text-xs text-slate-400 font-medium mt-0.5">Design the content for your email</p>
                                </div>
                            </div>
                            <div>
                                <button @click="toggleEdit('content')" class="px-4 py-1.5 border border-slate-200 hover:border-slate-900 rounded-sm font-bold text-xs transition-colors bg-white hover:bg-slate-50" x-text="editing === 'content' ? 'Cancel' : 'Edit'"></button>
                            </div>
                        </div>

                        {{-- Collapsed State Info --}}
                        <div x-show="editing !== 'content'" class="mt-4 pl-11">
                            <template x-if="campaign.template_id">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-slate-700 font-bold" x-text="getTemplateName()"></span>
                                    <a :href="'/templates/' + campaign.template_id + '/edit?return_to_campaign=' + campaign.id" class="px-3 py-1 bg-slate-900 text-white rounded-sm font-bold text-[10px] uppercase tracking-wider hover:bg-black transition-colors">Edit Design</a>
                                </div>
                            </template>
                            <template x-if="!campaign.template_id">
                                <p class="text-sm text-slate-500 font-medium">No design or template selected yet.</p>
                            </template>
                        </div>

                        {{-- Expanded Edit State --}}
                        <div x-show="editing === 'content'" class="mt-6 pl-11 pt-6 border-t border-slate-100 space-y-4" x-transition>
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Choose Email Design Template</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($templates as $template)
                                <div class="border-2 rounded-sm p-3 transition-all cursor-pointer group bg-white" 
                                     :class="campaign.template_id == {{ $template->id }} ? 'border-slate-800 bg-slate-50/50' : 'border-slate-200 hover:border-slate-350'" 
                                     @click="campaign.template_id = {{ $template->id }}; save(); editing = null">
                                    
                                    <div class="h-36 bg-slate-100 rounded-sm relative overflow-hidden mb-3 border border-slate-200 flex items-center justify-center">
                                        <div class="absolute inset-0 scale-[0.4] origin-top-left w-[250%] h-[250%] pointer-events-none opacity-50 group-hover:opacity-90 transition-opacity">
                                            <iframe src="{{ route('admin.templates.preview', $template) }}?raw=1" class="w-full h-full border-none bg-white"></iframe>
                                        </div>
                                    </div>
                                    <div class="text-xs font-bold text-slate-800 uppercase tracking-widest truncate">{{ $template->name }}</div>
                                </div>
                                @endforeach
                                @if(empty($templates))
                                <div class="col-span-2 p-8 text-center text-slate-400 text-sm border border-dashed rounded-sm bg-slate-50">
                                    No templates found. Go to Templates list to create one first.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Right Panel: Sticky Live HTML Preview (40% width) --}}
        <div class="hidden lg:flex lg:w-5/12 bg-slate-100 flex-col h-full overflow-hidden">
            
            {{-- Panel Toolbar Header --}}
            <div class="h-14 border-b border-slate-200 bg-white px-6 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Live Preview</span>
                    <!-- <span x-show="campaign.template_id" class="px-2 py-0.5 rounded-sm bg-emerald-50 border border-emerald-200 text-emerald-600 font-bold text-[9px] uppercase tracking-wider" x-text="getTemplateName()"></span> -->
                </div>
                
                <div class="flex gap-2">
                    {{-- Edit Layout Button --}}
                    <a x-show="campaign.template_id" 
                       :href="'/templates/' + campaign.template_id + '/edit?return_to_campaign=' + campaign.id" 
                       class="px-4 py-1.5 bg-slate-900 hover:bg-black text-white rounded-sm font-bold text-[10px] uppercase tracking-wider transition-colors flex items-center gap-1.5">
                       <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                       Edit Layout
                    </a>
                    
                    {{-- Choose/Change Template Button --}}
                    <button @click="toggleEdit('content')" 
                            class="px-4 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 rounded-sm font-bold text-[10px] uppercase tracking-wider transition-colors"
                            x-text="campaign.template_id ? 'Change Template' : 'Choose Layout'">
                    </button>
                </div>
            </div>

            {{-- Preview Container Mockup --}}
            <div class="flex-1 p-6 overflow-hidden flex flex-col bg-slate-100">
                <div class="flex-1 bg-white border border-slate-200 rounded-sm overflow-hidden flex flex-col">
                    
                    {{-- Browser chrome layout --}}
                    <div class="px-4 py-3 border-b border-slate-150 bg-slate-50/50 flex items-center gap-3 shrink-0">
                        <div class="flex gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-slate-300"></div>
                            <div class="w-2.5 h-2.5 rounded-sm bg-slate-300"></div>
                            <div class="w-2.5 h-2.5 rounded-sm bg-slate-300"></div>
                        </div>
                        <div class="flex-1 bg-white border border-slate-200 rounded-sm py-1 px-3.5 text-[10px] text-slate-400 font-medium truncate flex items-center gap-1">
                            <span class="text-slate-400 select-none">Subject:</span>
                            <span class="text-slate-800 font-bold" x-text="personalizedSubject || campaign.subject || 'Empty Subject Line'"></span>
                        </div>
                    </div>

                    {{-- Preview Frame --}}
                    <div class="flex-1 bg-slate-50 relative overflow-hidden">
                        <iframe x-show="campaign.template_id" 
                                :src="getPreviewUrl()" 
                                class="w-full h-full border-none bg-white">
                        </iframe>
                        
                        {{-- Empty State Placeholder --}}
                        <div x-show="!campaign.template_id" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center space-y-4 bg-slate-50/70">
                            <div class="w-16 h-16 rounded-sm bg-slate-100 flex items-center justify-center text-slate-400 border border-slate-200">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">Select a template to preview</p>
                                <p class="text-xs text-slate-400 mt-1">Choose a layout from the checklist steps on the left.</p>
                            </div>
                            <button @click="toggleEdit('content')" class="px-5 py-2 border border-slate-200 bg-white hover:bg-slate-50 rounded-sm font-bold text-xs transition-colors">Choose Template</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function mailchimpWizard() {
    return {
        editing: 'to',
        editingName: false,
        isSaving: false,
        sendAction: 'now',
        domainWarning: '',
        campaign: {
            id: {{ $campaign->id }},
            name: @json($campaign->name),
            from_name: @json($campaign->from_name),
            from_email: @json($campaign->from_email ?? ''),
            subject: @json($campaign->subject),
            email_list_id: @json($campaign->email_list_id),
            template_id: @json($campaign->template_id),
            sender_id: @json($campaign->sender_id),
            scheduled_at: @json($campaign->scheduled_at),
            subscription_topic_id: @json($campaign->subscription_topic_id),
        },
        // Advanced Audience State
        include_tags: @json($campaign->audience_config['include_tags'] ?? []),
        include_segments: @json($campaign->audience_config['include_segments'] ?? []),
        exclude_tags: @json($campaign->audience_config['exclude_tags'] ?? []),
        exclude_segments: @json($campaign->audience_config['exclude_segments'] ?? []),
        
        exclude_unhealthy: @json($campaign->audience_config['exclude_unhealthy'] ?? true),
        exclude_risky: @json($campaign->audience_config['exclude_risky'] ?? false),
        exclude_disposable: @json($campaign->audience_config['exclude_disposable'] ?? false),
        exclude_role_based: @json($campaign->audience_config['exclude_role_based'] ?? false),
        limit_enabled: @json(isset($campaign->audience_config['limit']) && $campaign->audience_config['limit'] > 0),
        limit: @json($campaign->audience_config['limit'] ?? ''),
        
        estimatedRecipients: null,
        personalizedSubject: '',
        sampleContact: null,
        senders: @json($senders),
        templates: @json($templates),
        topics: @json($subscriptionTopics),
        verifiedDomains: @json($verifiedDomains->pluck('domain')),
        emailLists: @json($emailLists),

        init() {
            if (this.campaign.scheduled_at) {
                this.sendAction = 'schedule';
            }
            this.save(); // Initial load for data
        },

        toggleEdit(section) {
            this.editing = this.editing === section ? null : section;
        },

        changeList() {
            this.isSaving = true;
            
            // Re-fetch and update campaign data, resetting include/exclude tags & segments
            const payload = {
                ...this.campaign,
                email_list_id: this.campaign.email_list_id,
                audience_config: {
                    include_tags: [],
                    include_segments: [],
                    exclude_tags: [],
                    exclude_segments: [],
                    exclude_unhealthy: this.exclude_unhealthy,
                    exclude_risky: this.exclude_risky,
                    exclude_disposable: this.exclude_disposable,
                    exclude_role_based: this.exclude_role_based,
                    limit: this.limit_enabled ? this.limit : null
                }
            };

            fetch('{{ route('admin.campaigns.save-step', $campaign) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                fetch(`/switch-workspace/${this.campaign.email_list_id}`)
                    .then(() => {
                        window.location.reload();
                    })
                    .catch(() => {
                        window.location.reload();
                    });
            })
            .catch(err => {
                this.isSaving = false;
            });
        },

        isAudienceReady() {
            return true;
        },

        isFromReady() {
            return this.campaign.from_name && this.campaign.from_email && !this.domainWarning;
        },

        isTimeReady() {
            if (this.sendAction === 'schedule' && !this.campaign.scheduled_at) return false;
            return true;
        },

        isReady() {
            return this.isAudienceReady() && this.isFromReady() && this.campaign.subject && this.campaign.template_id && this.isTimeReady();
        },

        getAudienceSummary() {
            let summary = '';
            const list = this.emailLists.find(l => l.id == this.campaign.email_list_id);
            if (list) {
                summary = list.name;
            } else {
                summary = 'Workspace Contacts';
            }
            
            let filters = [];
            if(this.include_tags.length > 0) filters.push(`${this.include_tags.length} Tags`);
            if(this.include_segments.length > 0) filters.push(`${this.include_segments.length} Segments`);
            
            if(filters.length > 0) {
                summary += ` (Targeted to ${filters.join(' & ')})`;
            } else {
                summary += ' (All Subscribers)';
            }

            if(this.campaign.subscription_topic_id) {
                const topic = this.topics.find(t => t.id == this.campaign.subscription_topic_id);
                if(topic) {
                    summary += ` [Topic: ${topic.name}]`;
                }
            }
            return summary;
        },

        getFromSummary() {
            if (this.campaign.from_name && this.campaign.from_email) {
                return `${this.campaign.from_name} <${this.campaign.from_email}>`;
            }
            return null;
        },

        getTimeSummary() {
            if (this.sendAction === 'schedule' && this.campaign.scheduled_at) {
                return `Scheduled for ${new Date(this.campaign.scheduled_at).toLocaleString()}`;
            }
            return 'Send immediately';
        },

        getTemplateName() {
            const tmpl = this.templates.find(t => t.id == this.campaign.template_id);
            return tmpl ? tmpl.name : 'Unknown Template';
        },

        checkDomainAndSave() {
            this.domainWarning = '';
            if (this.campaign.from_email) {
                const parts = this.campaign.from_email.split('@');
                if (parts.length === 2) {
                    const domain = parts[1].toLowerCase();
                    if (!this.verifiedDomains.includes(domain)) {
                        this.domainWarning = `Domain ${domain} is not verified. Please verify this domain first.`;
                    }
                }
            }
            this.save();
        },

        save() {
            this.isSaving = true;
            
            // Merge advanced audience data into the payload
            const payload = {
                ...this.campaign,
                audience_config: {
                    include_tags: this.include_tags,
                    include_segments: this.include_segments,
                    exclude_tags: this.exclude_tags,
                    exclude_segments: this.exclude_segments,
                    exclude_unhealthy: this.exclude_unhealthy,
                    exclude_risky: this.exclude_risky,
                    exclude_disposable: this.exclude_disposable,
                    exclude_role_based: this.exclude_role_based,
                    limit: this.limit_enabled ? this.limit : null
                }
            };

            fetch('{{ route('admin.campaigns.save-step', $campaign) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                this.isSaving = false;
                if(data.campaign) {
                    // Update campaign with server returned attributes (like sender_id, etc.)
                    this.campaign = data.campaign;
                }
                if(data.sample_contact) {
                    this.sampleContact = data.sample_contact;
                    this.personalizedSubject = data.personalized_subject;
                }
                if(data.estimated_recipients !== undefined) {
                    this.estimatedRecipients = data.estimated_recipients;
                }
            })
            .catch(err => {
                this.isSaving = false;
            });
        },

        getPreviewUrl() {
            if(!this.campaign.template_id) return '';
            let url = `/templates/${this.campaign.template_id}/preview?raw=1`;
            if(this.campaign.email_list_id) {
                url += `&list_id=${this.campaign.email_list_id}`;
            }
            return url;
        }
    }
}
</script>
@endsection
