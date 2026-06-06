@extends('layouts.app')
@section('title', 'Campaign Builder')

@section('content')
<div x-data="mailchimpWizard()" class="min-h-screen bg-white">
    {{-- Top Navigation --}}
    <div class="bg-white border-b border-color fixed -mx-6 top-16 z-40 w-[calc(100%-260px)] px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="{{ route('admin.campaigns.index') }}" class="text-gray-400 hover:text-gray-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="relative group flex items-center gap-2">
                <template x-if="!editingName">
                    <div @click="editingName = true; $nextTick(() => $refs.nameInput.focus())" 
                         class="flex items-center gap-3 cursor-pointer group-hover:bg-gray-50 px-2 -ml-2 py-1 rounded transition-all">
                        <h1 class="text-xl font-bold text-gray-900" x-text="campaign.name || 'Untitled Campaign'"></h1>
                        <svg class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                </template>
                <template x-if="editingName">
                    <input x-ref="nameInput" 
                           type="text" 
                           x-model="campaign.name" 
                           @blur="editingName = false; save()" 
                           @keydown.enter="editingName = false; save()"
                           class="text-xl font-bold text-gray-900 bg-white border-b-2 border-gray-900 outline-none px-1 py-0.5 min-w-[300px]">
                </template>
            </div>
            <span class="bg-gray-100 text-[10px] px-2 py-1 rounded font-bold text-gray-500 uppercase tracking-wider">Draft</span>
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ route('admin.campaigns.index') }}" class="px-3 py-1 rounded font-bold text-gray-900 text-sm transition-colors">Back to list</a>
            <span x-show="isSaving" class="text-[10px] font-bold text-gray-400 uppercase animate-pulse">Saving...</span>
            <form action="{{ route('admin.campaigns.send', $campaign) }}" method="POST">
                @csrf
                <button type="submit" 
                        :disabled="!isReady()"
                        class="px-8 py-2 rounded font-bold text-sm transition-all"
                        :class="isReady() ? 'bg-gray-900 text-white hover:bg-black' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                    Send Campaign
                </button>
            </form>
        </div>
    </div>

    <div class="-mx-6 pt-11">
        <div class="grid grid-cols-1 lg:grid-cols-12">
            
            {{-- Left Side: Checklist --}}
            <div class="lg:col-span-7 border-r border-color min-h-screen">
                <div class="px-6 py-6 space-y-6">
                    
                    {{-- Section: To --}}
                    <div class="border-b border-color pb-6">
                        <div class="flex items-start gap-6">
                            <div class="mt-1">
                                <template x-if="isAudienceReady()">
                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </template>
                                <template x-if="!isAudienceReady()">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-bold text-gray-900">To</h3>
                                    <button @click="toggleEdit('to')" class="px-6 py-1.5 border border-color rounded font-bold text-sm hover:bg-gray-50" x-text="editing === 'to' ? 'Cancel' : 'Edit'"></button>
                                </div>
                                <div x-show="editing !== 'to'">
                                    <p class="text-gray-500 text-lg" x-text="getAudienceSummary() || 'Who are you sending this campaign to?'"></p>
                                    <template x-if="estimatedRecipients !== null && list_ids.length > 0">
                                        <p class="text-sm font-bold text-gray-900 mt-2">
                                            Estimated Recipients: <span class="text-blue-600" x-text="estimatedRecipients.toLocaleString()"></span>
                                        </p>
                                    </template>
                                </div>

                                <div x-show="editing === 'to'" class="mt-8 space-y-8" x-transition>
                                    {{-- Single-List Dropdown Selector --}}
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Select Audience List</label>
                                        <select x-model="campaign.email_list_id" 
                                                @change="list_ids = campaign.email_list_id ? [parseInt(campaign.email_list_id)] : []; save()" 
                                                class="w-full p-4 border border-color rounded text-sm focus:outline-none focus:border-gray-900 bg-white cursor-pointer">
                                            <option value="">Choose an Audience...</option>
                                            @foreach($emailLists as $list)
                                            <option value="{{ $list->id }}">
                                                {{ $list->name }} ({{ number_format($list->emails_count) }} subscribers)
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="text-[10px] font-bold text-red-400 uppercase tracking-widest" x-show="list_ids.length === 0">Please select a list to continue.</div>
                                    </div>

                                    {{-- Multi-Select Tags/Segments --}}
                                    <div x-show="list_ids.length > 0" class="space-y-6 pt-6 border-t border-color" x-transition>
                                        
                                        {{-- INCLUDES --}}
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-black text-gray-900 uppercase tracking-widest">Target Specific Audience (Optional)</label>
                                                <div class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-sm text-[8px] font-black uppercase tracking-widest border border-blue-100">INCLUDES</div>
                                            </div>
                                            <p class="text-xs text-gray-500 font-medium">If left blank, sends to ALL subscribers. If selected, sends ONLY to those matching AT LEAST ONE.</p>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="space-y-2 border border-color p-3 rounded bg-white max-h-48 overflow-auto">
                                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-color z-10">Tags to Include</div>
                                                    @foreach($allTags as $tag)
                                                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                                                        <input type="checkbox" value="{{ $tag }}" x-model="include_tags" @change="save()" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        {{ $tag }}
                                                    </label>
                                                    @endforeach
                                                    @if(empty($allTags)) <div class="text-xs text-gray-400">No tags found.</div> @endif
                                                </div>
                                                <div class="space-y-2 border border-color p-3 rounded bg-white max-h-48 overflow-auto">
                                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest sticky top-0 bg-white pb-2 mb-2 border-b border-color z-10">Segments to Include</div>
                                                    @foreach($allSegments as $segment)
                                                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                                                        <input type="checkbox" value="{{ $segment }}" x-model="include_segments" @change="save()" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        {{ $segment }}
                                                    </label>
                                                    @endforeach
                                                    @if(empty($allSegments)) <div class="text-xs text-gray-400">No segments found.</div> @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- EXCLUDES --}}
                                        <div class="space-y-3 pt-4 border-t border-color/50">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-black text-gray-900 uppercase tracking-widest">Skip Specific Audience (Optional)</label>
                                                <div class="px-2 py-0.5 bg-red-50 text-red-600 rounded-sm text-[8px] font-black uppercase tracking-widest border border-red-100">EXCLUDES</div>
                                            </div>
                                            <p class="text-xs text-gray-500 font-medium">Subscribers matching ANY of these will NOT receive the campaign, overriding includes.</p>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="space-y-2 border border-color p-3 rounded bg-red-50/30 max-h-48 overflow-auto">
                                                    <div class="text-[10px] font-bold text-red-400 uppercase tracking-widest sticky top-0 bg-red-50/30 pb-2 mb-2 border-b border-color z-10">Tags to Exclude</div>
                                                    @foreach($allTags as $tag)
                                                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                                                        <input type="checkbox" value="{{ $tag }}" x-model="exclude_tags" @change="save()" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500">
                                                        {{ $tag }}
                                                    </label>
                                                    @endforeach
                                                    @if(empty($allTags)) <div class="text-xs text-gray-400">No tags found.</div> @endif
                                                </div>
                                                <div class="space-y-2 border border-color p-3 rounded bg-red-50/30 max-h-48 overflow-auto">
                                                    <div class="text-[10px] font-bold text-red-400 uppercase tracking-widest sticky top-0 bg-red-50/30 pb-2 mb-2 border-b border-color z-10">Segments to Exclude</div>
                                                    @foreach($allSegments as $segment)
                                                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                                                        <input type="checkbox" value="{{ $segment }}" x-model="exclude_segments" @change="save()" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500">
                                                        {{ $segment }}
                                                    </label>
                                                    @endforeach
                                                    @if(empty($allSegments)) <div class="text-xs text-gray-400">No segments found.</div> @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Health Filters --}}
                                        <div class="pt-6 border-t border-color space-y-4" x-transition>
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Health & Hygiene</label>
                                                <div class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-sm text-[8px] font-black uppercase tracking-widest border border-emerald-100">Deliverability Protection</div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <label class="flex items-center gap-3 p-3 border border-color rounded cursor-pointer hover:bg-gray-50 transition-all group" :class="exclude_unhealthy ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                                    <input type="checkbox" x-model="exclude_unhealthy" @change="save()" class="w-5 h-5 rounded-sm border-gray-200 text-gray-900 focus:ring-0">
                                                    <div>
                                                        <div class="text-xs font-black text-gray-900 uppercase tracking-tight">Auto-Exclude Unhealthy</div>
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Skips bounces & complaints</div>
                                                    </div>
                                                </label>
                                                <label class="flex items-center gap-3 p-3 border border-color rounded cursor-pointer hover:bg-gray-50 transition-all group" :class="exclude_risky ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                                    <input type="checkbox" x-model="exclude_risky" @change="save()" class="w-5 h-5 rounded-sm border-gray-200 text-gray-900 focus:ring-0">
                                                    <div>
                                                        <div class="text-xs font-black text-gray-900 uppercase tracking-tight">Exclude Risky Contacts</div>
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Skips low-score emails</div>
                                                    </div>
                                                </label>
                                                <label class="flex items-center gap-3 p-3 border border-color rounded cursor-pointer hover:bg-gray-50 transition-all group" :class="exclude_disposable ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                                    <input type="checkbox" x-model="exclude_disposable" @change="save()" class="w-5 h-5 rounded-sm border-gray-200 text-gray-900 focus:ring-0">
                                                    <div>
                                                        <div class="text-xs font-black text-gray-900 uppercase tracking-tight">Block Disposable</div>
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Skips temporary addresses</div>
                                                    </div>
                                                </label>
                                                <label class="flex items-center gap-3 p-3 border border-color rounded cursor-pointer hover:bg-gray-50 transition-all group" :class="exclude_role_based ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                                    <input type="checkbox" x-model="exclude_role_based" @change="save()" class="w-5 h-5 rounded-sm border-gray-200 text-gray-900 focus:ring-0">
                                                    <div>
                                                        <div class="text-xs font-black text-gray-900 uppercase tracking-tight">No Role-Based</div>
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Skips admin@, info@, etc</div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Limit Filter --}}
                                    <div class="pt-6 border-t border-color space-y-4" x-transition>
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-black text-gray-400 uppercase tracking-widest">Quantity Limit</label>
                                        </div>
                                        <div class="space-y-3">
                                            <label class="flex items-center gap-3 p-3 border border-color rounded cursor-pointer hover:bg-gray-50 transition-all group" :class="limit_enabled ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                                <input type="checkbox" x-model="limit_enabled" @change="if(!limit_enabled) limit = ''; save()" class="w-5 h-5 rounded-sm border-gray-200 text-gray-900 focus:ring-0">
                                                <div>
                                                    <div class="text-xs font-black text-gray-900 uppercase tracking-tight">Limit Number of Recipients</div>
                                                    <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Send only to a specific quantity</div>
                                                </div>
                                            </label>
                                            <div x-show="limit_enabled" class="pl-8" x-transition>
                                                <input type="number" x-model="limit" @input.debounce.1000ms="save()" placeholder="e.g. 2000" class="w-full md:w-1/2 p-3 border border-color rounded text-sm focus:outline-none focus:border-gray-900" min="1">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-4 flex items-center justify-between">
                                        <button @click="editing = null" class="px-8 py-3 bg-gray-900 text-white rounded font-bold text-sm hover:bg-black transition-colors">Save Audience</button>
                                        <template x-if="estimatedRecipients !== null && list_ids.length > 0">
                                            <div class="text-sm font-bold text-gray-500">
                                                Estimated count: <span class="text-gray-900 text-lg" x-text="estimatedRecipients.toLocaleString()"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section: From --}}
                    <div class="border-b border-color pb-6">
                        <div class="flex items-start gap-6">
                            <div class="mt-1">
                                <template x-if="campaign.sender_id">
                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </template>
                                <template x-if="!campaign.sender_id">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-bold text-gray-900">From</h3>
                                    <button @click="toggleEdit('from')" class="px-6 py-1.5 border border-color rounded font-bold text-sm hover:bg-gray-50" x-text="editing === 'from' ? 'Cancel' : 'Edit'"></button>
                                </div>
                                <div x-show="editing !== 'from'">
                                    <p class="text-gray-500 text-lg" x-text="getSelectedSenderName() || 'Who is sending this campaign?'"></p>
                                </div>

                                <div x-show="editing === 'from'" class="mt-8 space-y-4" x-transition>
                                    @foreach($senders as $sender)
                                    <label class="flex items-center gap-4 p-5 border border-color rounded cursor-pointer transition-all hover:bg-gray-50" :class="campaign.sender_id == {{ $sender->id }} ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-900' : ''">
                                        <input type="radio" x-model="campaign.sender_id" value="{{ $sender->id }}" @change="save()" class="form-radio text-gray-900 w-5 h-5">
                                        <div>
                                            <div class="font-bold text-gray-900 text-lg">{{ $sender->from_name }}</div>
                                            <div class="text-gray-500">{{ $sender->email }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                    <div class="pt-4">
                                        <button @click="editing = null" class="px-8 py-3 bg-gray-900 text-white rounded font-bold text-sm hover:bg-black transition-colors">Save Sender</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section: Subject --}}
                    <div class="border-b border-color pb-6">
                        <div class="flex items-start gap-6">
                            <div class="mt-1">
                                <template x-if="campaign.subject">
                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </template>
                                <template x-if="!campaign.subject">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-bold text-gray-900">Subject</h3>
                                    <button @click="toggleEdit('subject')" class="px-6 py-1.5 border border-color rounded font-bold text-sm hover:bg-gray-50" x-text="editing === 'subject' ? 'Cancel' : 'Edit'"></button>
                                </div>
                                <div x-show="editing !== 'subject'">
                                    <p class="text-gray-500 text-lg" x-text="campaign.subject || 'What\'s the subject line for this campaign?'"></p>
                                </div>

                                <div x-show="editing === 'subject'" class="mt-8 space-y-6" x-transition>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Subject Line</label>
                                        <input type="text" x-model="campaign.subject" @input.debounce.1000ms="save()" class="w-full p-4 border border-color rounded text-lg focus:outline-none focus:border-gray-900" placeholder="e.g. Special offer just for you!">
                                        <p class="text-xs text-gray-400">Use @{{name}} or @{{email}} for personalization.</p>
                                    </div>
                                    <div class="pt-2 text-right">
                                        <button @click="editing = null" class="px-8 py-3 bg-gray-900 text-white rounded font-bold text-sm hover:bg-black transition-colors">Save Subject</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section: Content --}}
                    <div class="pb-6">
                        <div class="flex items-start gap-6">
                            <div class="mt-1">
                                <template x-if="campaign.template_id">
                                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </template>
                                <template x-if="!campaign.template_id">
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                                </template>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-bold text-gray-900">Content</h3>
                                    <button @click="toggleEdit('content')" class="px-6 py-1.5 border border-color rounded font-bold text-sm hover:bg-gray-50" x-text="editing === 'content' ? 'Cancel' : 'Edit'"></button>
                                </div>
                                <div x-show="editing !== 'content'">
                                    <p class="text-gray-500 text-lg" x-text="getSelectedTemplateName() || 'Design the content for your email.'"></p>
                                    <div x-show="campaign.template_id" class="mt-4 w-48 h-32 border border-color rounded overflow-hidden relative">
                                        <div class="absolute inset-0 scale-[0.3] origin-top-left w-[333%] h-[333%] pointer-events-none opacity-40">
                                            <iframe :src="getPreviewUrl()" class="w-full h-full border-none"></iframe>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="editing === 'content'" class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6" x-transition>
                                    @foreach($templates as $template)
                                    <div class="border-2 rounded-xl p-3 transition-all cursor-pointer group" :class="campaign.template_id == {{ $template->id }} ? 'border-gray-900 bg-gray-50' : 'border-gray-100 hover:border-gray-300'" @click="campaign.template_id = {{ $template->id }}; save(); editing = null">
                                        <div class="h-48 bg-gray-50 rounded-lg relative overflow-hidden mb-3">
                                            <div class="absolute inset-0 scale-[0.5] origin-top-left w-[200%] h-[200%] pointer-events-none opacity-60 group-hover:opacity-100 transition-opacity">
                                                <iframe src="{{ route('admin.templates.preview', $template) }}?raw=1" class="w-full h-full border-none"></iframe>
                                            </div>
                                        </div>
                                        <div class="text-xs font-bold text-gray-900 uppercase tracking-widest">{{ $template->name }}</div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Right Side: High-Fidelity Preview --}}
            <div class="lg:col-span-5 bg-gray-50/50 min-h-screen">
                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Live Preview</h4>
                        <div class="flex gap-2">
                            <button class="px-4 py-2 border border-color rounded font-bold text-[10px] uppercase tracking-widest hover:bg-white transition-colors">Preview</button>
                            <button class="px-4 py-2 border border-color rounded font-bold text-[10px] uppercase tracking-widest hover:bg-white transition-colors">Test Email</button>
                        </div>
                    </div>

                    <div class="bg-white border border-color rounded overflow-hidden flex flex-col h-[700px] transform transition-transform hover:scale-[1.01]">
                        {{-- Browser Chrome --}}
                        <div class="p-4 border-b border-color bg-white flex items-center gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="ml-4 flex-1 bg-gray-100 rounded-lg py-1.5 px-4 text-[10px] text-gray-400 font-medium truncate">
                                Subject: <span class="text-gray-900" x-text="personalizedSubject || campaign.subject || '...'"></span>
                            </div>
                        </div>

                        {{-- Email Content --}}
                        <div class="flex-1 relative bg-gray-50 overflow-auto">
                            <iframe x-show="campaign.template_id" 
                                    :src="getPreviewUrl()" 
                                    class="w-full h-full border-none bg-white"></iframe>
                            
                            <div x-show="!campaign.template_id" class="absolute inset-0 flex flex-col items-center justify-center p-12 text-center space-y-4">
                                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-gray-400 font-medium italic text-sm">Select a template to see your design come to life here.</p>
                            </div>
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
        campaign: {
            id: {{ $campaign->id }},
            name: @json($campaign->name),
            subject: @json($campaign->subject),
            email_list_id: @json($campaign->email_list_id),
            template_id: @json($campaign->template_id),
            sender_id: @json($campaign->sender_id),
            scheduled_at: @json($campaign->scheduled_at),
        },
        // Advanced Audience State
        list_ids: @json($campaign->audience_config['list_ids'] ?? ($campaign->email_list_id ? [$campaign->email_list_id] : [])).map(Number),
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
        lists: @json($emailLists),
        senders: @json($senders),
        templates: @json($templates),

        init() {
            this.save(); // Initial load for data
        },

        toggleEdit(section) {
            this.editing = this.editing === section ? null : section;
        },

        isAudienceReady() {
            if(this.list_ids.length === 0) return false;
            return true;
        },

        isReady() {
            return this.isAudienceReady() && this.campaign.sender_id && this.campaign.subject && this.campaign.template_id;
        },

        getAudienceSummary() {
            if(this.list_ids.length === 0) return null;
            
            const list = this.lists.find(l => l.id == this.list_ids[0]);
            let summary = list ? list.name : '1 List';
            
            let filters = [];
            if(this.include_tags.length > 0) filters.push(`${this.include_tags.length} Tags`);
            if(this.include_segments.length > 0) filters.push(`${this.include_segments.length} Segments`);
            
            if(filters.length > 0) {
                summary += ` (Targeted to ${filters.join(' & ')})`;
            } else {
                summary += ' (All Subscribers)';
            }
            return summary;
        },

        save() {
            this.isSaving = true;
            
            // Merge advanced audience data into the payload
            const payload = {
                ...this.campaign,
                email_list_id: this.list_ids.length > 0 ? this.list_ids[0] : null,
                audience_config: {
                    list_ids: this.list_ids,
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
                if(this.audience_type === 'segment' && this.audience_tag) {
                    url += `&audience_tag=${this.audience_tag}`;
                }
            }
            return url;
        },

        getSelectedListName() {
            const list = this.lists.find(l => l.id == this.campaign.email_list_id);
            return list ? list.name : null;
        },

        getSelectedSenderName() {
            const sender = this.senders.find(s => s.id == this.campaign.sender_id);
            return sender ? `${sender.from_name} (${sender.email})` : null;
        },

        getSelectedTemplateName() {
            const template = this.templates.find(t => t.id == this.campaign.template_id);
            return template ? template.name : null;
        }
    }
}
</script>
@endsection
