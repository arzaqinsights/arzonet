@once
    <style>
        .segment-tooltip {
            position: relative;
            display: inline-block;
        }

        .segment-tooltip .tooltip-content {
            visibility: hidden;
            position: absolute;
            bottom: 130%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #0f172a;
            /* slate-900 */
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #334155;
            /* slate-700 */
            z-index: 100;
            width: max-content;
            max-width: 240px;
            text-align: left;
            display: flex;
            flex-direction: column;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s ease-in-out, transform 0.15s ease-in-out;
        }

        .segment-tooltip .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: #0f172a transparent transparent transparent;
        }

        .segment-tooltip:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(-2px);
        }
    </style>
@endonce

@php
    $groupedEmails = $emails->groupBy(function ($email) {
        if (!empty(trim($email->name))) {
            return 'name_' . strtolower(trim($email->name));
        }
        if ($email->original_row_id) {
            return 'orig_' . $email->original_row_id;
        }
        return 'id_' . $email->id;
    });
@endphp

@foreach($groupedEmails as $groupKey => $groupItems)
    @php
        $groupItems = $groupItems->sortBy('id')->values();
        $firstItem = $groupItems->first();
        $groupSize = $groupItems->count();
        $mapping = $emailList->column_mapping ?? [];

        $displayedFields = [];
        foreach (['company', 'job_title', 'phone', 'city'] as $field) {
            if (isset($mapping[$field]))
                $displayedFields[] = $field;
        }

        if (isset($mapping['whatsapp_number']) && !in_array('phone', $displayedFields)) {
            $displayedFields[] = 'phone';
        }

        foreach ($mapping as $key => $val) {
            if (str_starts_with($key, 'custom_'))
                $displayedFields[] = $key;
        }
    @endphp

    @foreach($groupItems as $index => $email)
        @php
            $isMaster = ($index === 0);
            $isLastSub = $loop->last;
        @endphp
        <tr x-data="{ 
                                        editing: false, 
                                        saving: false,
                                        row: {
                                            id: {{ $email->id }},
                                            email: '{{ $email->email }}',
                                            whatsapp_number: '{{ $email->whatsapp_number ?? '' }}',
                                            name: '{{ $email->name ?? '' }}',
                                            segment_name: '{{ $email->segment_name ?? '' }}',
                                            tags: '{{ is_array($email->tags) ? implode(', ', $email->tags) : ($email->tags ?? '') }}',
                                            subscription_status: '{{ $email->subscription_status ?? 'subscribed' }}',
                                            unsubscribe_duration: 'forever',
                                            whatsapp_subscription_status: '{{ $email->whatsapp_subscription_status ?? 'subscribed' }}',
                                            is_archived: {{ $email->is_archived ? 'true' : 'false' }},
                                            original_row_id: '{{ $email->original_row_id }}' || '{{ $email->id }}',
                                            meta: {{ json_encode($email->meta ?? new \stdClass()) }},
                                            subscribed_topics: {{ json_encode(is_array($email->subscribed_topics) ? array_map('strval', $email->subscribed_topics) : (json_decode($email->subscribed_topics ?? '[]', true) ?: [])) }},
                                            added_by: '{{ addslashes($email->user->name ?? "System") }}',
                                            get tagsArray() {
                                                if (!this.tags) return [];
                                                return this.tags.split(',').map(t => t.trim()).filter(t => t);
                                            },
                                            get topicNames() {
                                                let map = {
                                                    @foreach($topics ?? [] as $t)
                                                        '{{ (string) $t->id }}': '{{ addslashes($t->name) }}',
                                                    @endforeach
                                                };
                                                return this.subscribed_topics.map(id => map[id] || ('Topic ' + id));
                                            }
                                        },
                                        save() {
                                             this.saving = true;
                                             fetch(`{{ route('admin.email-lists.update-email', [$emailList, ':id']) }}`.replace(':id', this.row.id), {
                                                 method: 'PUT',
                                                 headers: {
                                                     'Content-Type': 'application/json',
                                                     'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content,
                                                 },
                                                 body: JSON.stringify(this.row)
                                             })
                                             .then(r => {
                                                 if (!r.ok) {
                                                     return r.json().then(err => { throw err; });
                                                 }
                                                 return r.json();
                                             })
                                             .then(() => {
                                                 this.saving = false;
                                                 this.editing = false;
                                                 fetchEmails();
                                                 refreshStats();
                                             })
                                             .catch(err => {
                                                 this.saving = false;
                                                 let msg = 'Failed to save contact.';
                                                 if (err && err.message) {
                                                     msg = err.message;
                                                 } else if (err && err.errors) {
                                                     msg = Object.values(err.errors).flat().join('\n');
                                                 }
                                                 alert(msg);
                                             });
                                         }
                                    }" class="group transition-all duration-200 border-y" :class="[
                                        editing ? 'bg-[#fafafa]' : (selectedIds.includes({{ $email->id }}) ? 'selected bg-[#f6f1ec]' : ({{ $isMaster ? 'true' : 'false' }} ? 'bg-white' : 'bg-slate-100')),
                                        editing ? 'hover:bg-[#fafafa]' : (selectedIds.includes({{ $email->id }}) ? 'hover:bg-[#f6f1ec]' : ({{ $isMaster ? 'true' : 'false' }} ? 'hover:bg-slate-50/50' : 'hover:bg-slate-150')),
                                        {{ $isLastSub ? 'true' : 'false' }} ? 'border-gray-200' : 'border-dashed border-gray-100/80'
                                    ]">

            {{-- Checkbox Column --}}
            <td
                class="px-8 py-4 whitespace-nowrap sticky left-0 z-9 group-[.selected]:bg-[#f6f1ec] transition-colors relative {{ $isMaster ? 'bg-white group-hover:bg-slate-50/50' : 'pl-12 bg-slate-50/60 group-hover:bg-slate-100/50' }}">
                @if(!$isMaster)
                    <!-- Vertical tree line connecting from the parent checkbox -->
                    <div class="absolute left-[40px] top-0 {{ $isLastSub ? 'h-1/2' : 'h-full' }} w-px bg-slate-300"></div>
                    <!-- Horizontal connector to the nested checkbox -->
                    <div class="absolute left-[40px] top-1/2 w-3.5 h-px bg-slate-300"></div>
                @endif
                <div class="flex items-center {{ !$isMaster ? 'pl-4' : '' }}">
                    <input type="checkbox" :value="{{ $email->id }}" x-model="selectedIds"
                        class="rounded-sm border-gray-200 text-brand focus:ring-brand focus:ring-offset-0 cursor-pointer {{ !$isMaster ? 'w-3.5 h-3.5' : 'w-4 h-4' }}">
                </div>
            </td>

            {{-- Full Name Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                @if($isMaster)
                    <template x-if="!editing">
                        <div class="text-xs text-surface-900 font-bold" x-text="row.name || '—'"></div>
                    </template>
                    <template x-if="editing">
                        <input type="text" x-model="row.name"
                            class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                    </template>
                @else
                    <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400 select-none">
                        <span class="text-slate-300 font-bold">└─</span>
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[8px] font-black uppercase tracking-wider border border-slate-200">
                            <i class="fa-solid fa-link text-[7px] text-slate-400"></i> Alt Channel
                        </span>
                    </div>
                @endif
            </td>

            {{-- Dynamic CRM Columns --}}
            @foreach($displayedFields as $field)
                <td class="px-8 py-4 whitespace-nowrap">
                    @if($isMaster)
                        <template x-if="!editing">
                            <div class="text-xs text-surface-500 font-bold" x-text="row.meta['{{ $field }}'] || '—'"></div>
                        </template>
                        <template x-if="editing">
                            <input type="text" x-model="row.meta['{{ $field }}']"
                                class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                        </template>
                    @endif
                </td>
            @endforeach

            {{-- Segment Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div class="flex items-center justify-center">
                        <template x-if="row.segment_name">
                            <span
                                class="inline-flex px-1.5 py-0.5 rounded-sm bg-blue-50 text-blue-600 text-[8px] font-black uppercase tracking-wider border border-blue-100/50"
                                x-text="row.segment_name"></span>
                        </template>
                        <template x-if="!row.segment_name">
                            <span class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">—</span>
                        </template>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="row.segment_name"
                        class="w-24 px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- Tag Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div class="flex items-center justify-center">
                        <template x-if="row.tagsArray.length === 0">
                            <span class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">—</span>
                        </template>
                        <template x-if="row.tagsArray.length > 0">
                            <div class="segment-tooltip">
                                <div class="inline-flex items-center gap-1">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-sm bg-brand/5 text-brand text-[9px] font-black uppercase tracking-widest border border-brand/10"
                                        x-text="row.tagsArray[0]"></span>
                                    <template x-if="row.tagsArray.length > 1">
                                        <span
                                            class="inline-flex px-1.5 py-0.5 rounded-sm bg-brand/10 text-brand text-[8px] font-black border border-brand/20"
                                            x-text="'+' + (row.tagsArray.length - 1)"></span>
                                    </template>
                                </div>
                                <template x-if="row.tagsArray.length > 1">
                                    <div class="tooltip-content flex flex-col gap-1">
                                        <template x-for="tag in row.tagsArray" :key="tag">
                                            <span class="text-[9px] uppercase tracking-widest" x-text="tag"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="row.tags" placeholder="tag1, tag2"
                        class="w-24 px-2 py-1 bg-white border border-gray-100 rounded-sm text-[9px] font-bold uppercase focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- Email Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                <template x-if="!editing">
                    <div class="flex items-center gap-2">
                        <span
                            class="{{ $isMaster ? 'font-bold text-surface-900 text-sm' : 'font-semibold text-surface-600 text-sm' }}"
                            x-text="row.email || '—'"></span>
                        <template x-if="row.is_archived">
                            <span
                                class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest rounded-sm border border-gray-200">Archived</span>
                        </template>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="email" x-model="row.email"
                        class="w-full px-2 py-1 bg-white border border-gray-200 rounded-sm text-sm font-bold focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- WhatsApp Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                <template x-if="!editing">
                    <div
                        class="flex items-center gap-1.5 {{ $isMaster ? 'text-xs text-surface-600 font-bold' : 'text-xs text-surface-500 font-semibold' }}">
                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                        <span x-text="row.whatsapp_number || '—'"></span>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="row.whatsapp_number" placeholder="WhatsApp"
                        class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- Health/Status Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @php
                    $statusVal = $email->email_status ?? $email->status;
                    $status = match ($statusVal) {
                        'clean', 'valid' => ['label' => 'Clean', 'cls' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
                        'cross_duplicate' => ['label' => 'Cross-List Dup', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200'],
                        'risky', 'suspicious' => ['label' => 'Risky', 'cls' => 'bg-amber-50 text-amber-600 border-amber-100'],
                        'role_based' => ['label' => 'Role', 'cls' => 'bg-blue-50 text-blue-600 border-blue-100'],
                        'disposable' => ['label' => 'Temp', 'cls' => 'bg-indigo-50 text-indigo-600 border-indigo-100'],
                        'invalid', 'hard_bounce' => ['label' => 'Dead', 'cls' => 'bg-red-50 text-red-600 border-red-100'],
                        'complaint' => ['label' => 'Spam', 'cls' => 'bg-black text-white border-transparent'],
                        'blocked' => ['label' => 'Banned', 'cls' => 'bg-surface-900 text-white border-transparent'],
                        default => ['label' => 'Unknown', 'cls' => 'bg-surface-100 text-surface-600 border-surface-200'],
                    };

                    $score = $email->email_score ?? 3;
                    $scoreColor = match (true) {
                        $score >= 4 => 'text-emerald-500',
                        $score == 3 => 'text-amber-500',
                        default => 'text-red-500',
                    };
                @endphp
                <div class="flex flex-col items-center gap-1">
                    <div
                        class="inline-flex items-center px-2 py-0.5 rounded-sm text-[8px] font-black uppercase tracking-widest border {{ $status['cls'] }}">
                        {{ $status['label'] }}
                    </div>
                    <div class="flex items-center gap-0.5 mt-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <div
                                class="w-1.75 h-1.75 rounded-full {{ $i <= $score ? str_replace('text', 'bg', $scoreColor) : 'bg-gray-200' }}">
                            </div>
                        @endfor
                    </div>
                    @if($email->validation_reason)
                        <div class="text-[7px] text-gray-400 font-bold max-w-[80px] truncate"
                            title="{{ $email->validation_reason }}">
                            {{ $email->validation_reason }}
                        </div>
                    @endif
                </div>
            </td>

            {{-- Subscription Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div class="flex flex-col items-center justify-center gap-1">
                        <template x-if="row.topicNames.length === 0">
                            <div class="flex items-center justify-center gap-2 text-red-500">
                                <span class="text-[10px] font-black uppercase tracking-tighter">Unsubscribed</span>
                            </div>
                        </template>
                        <template x-if="row.topicNames.length > 0">
                            <div class="segment-tooltip">
                                <div class="inline-flex items-center gap-1">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-sm bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase tracking-widest border border-emerald-100/50"
                                        x-text="row.topicNames[0]"></span>
                                    <template x-if="row.topicNames.length > 1">
                                        <span
                                            class="inline-flex px-1 py-0.5 rounded-sm bg-emerald-100 text-emerald-700 text-[8px] font-black border border-emerald-200"
                                            x-text="'+' + (row.topicNames.length - 1)"></span>
                                    </template>
                                </div>
                                <template x-if="row.topicNames.length > 1">
                                    <div class="tooltip-content flex flex-col gap-1">
                                        <template x-for="t in row.topicNames" :key="t">
                                            <span class="text-[9px] uppercase tracking-widest text-emerald-300"
                                                x-text="t"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        @if($email->subscription_status === 'unsubscribed' && $email->unsubscribe_expires_at)
                            <span
                                class="text-[8px] font-bold text-amber-600 bg-amber-50 border border-amber-100/50 rounded-sm px-1.5 py-0.5 mt-1 block max-w-fit mx-auto"
                                title="Expires at: {{ $email->unsubscribe_expires_at->format('Y-m-d H:i') }}">
                                Snoozed: {{ $email->unsubscribe_expires_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </template>
                <template x-if="editing">
                    <div class="flex flex-col gap-1 items-center justify-center relative" x-data="{ openTopicSelect: false }">
                        <button @click="openTopicSelect = !openTopicSelect" type="button"
                            class="bg-white border border-gray-100 text-[10px] font-black uppercase rounded-sm focus:ring-0 focus:outline-none px-2 py-1 flex items-center justify-between gap-1 w-[110px] shadow-sm">
                            <span
                                x-text="row.subscribed_topics.length > 0 && row.subscription_status !== 'bounced' ? row.subscribed_topics.length + ' Topics' : (row.subscription_status === 'bounced' ? 'Bounced' : 'Unsubscribed')"></span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="openTopicSelect" @click.away="openTopicSelect = false"
                            class="absolute z-[100] top-full mt-1 left-1/2 -translate-x-1/2 w-48 bg-white border border-gray-200 rounded-sm shadow-xl text-left p-1"
                            style="display: none;">
                            <div class="max-h-40 overflow-y-auto p-1 space-y-0.5">
                                @if(isset($topics) && $topics->isNotEmpty())
                                    @foreach($topics as $t)
                                        <label
                                            class="flex items-start gap-2 text-[10px] font-bold text-gray-700 cursor-pointer hover:bg-gray-50 p-1.5 rounded-sm transition-colors w-full">
                                            <input type="checkbox" x-model="row.subscribed_topics" value="{{ (string) $t->id }}"
                                                @change="row.subscription_status = row.subscribed_topics.length > 0 ? 'subscribed' : 'unsubscribed'"
                                                class="rounded-sm border-gray-300 text-brand focus:ring-brand w-3 h-3 mt-0.5 cursor-pointer">
                                            <span class="leading-tight">{{ $t->name }}</span>
                                        </label>
                                    @endforeach
                                @else
                                    <div class="text-[10px] text-gray-500 font-medium text-center py-2">No topics created.</div>
                                @endif
                            </div>
                            <div class="mt-1 pt-1 border-t border-gray-100 p-1">
                                <label
                                    class="flex items-center gap-2 text-[10px] font-bold text-red-600 cursor-pointer hover:bg-red-50 p-1.5 rounded-sm transition-colors w-full">
                                    <input type="checkbox" :checked="row.subscription_status === 'bounced'"
                                        @change="row.subscription_status = $event.target.checked ? 'bounced' : (row.subscribed_topics.length > 0 ? 'subscribed' : 'unsubscribed')"
                                        class="rounded-sm border-red-300 text-red-500 focus:ring-red-500 w-3 h-3 cursor-pointer">
                                    Mark as Bounced
                                </label>
                            </div>
                        </div>

                        <template x-if="row.subscription_status === 'unsubscribed'">
                            <select x-model="row.unsubscribe_duration"
                                class="bg-white border border-gray-150 text-[9px] font-bold rounded-sm focus:ring-0 focus:outline-none p-1 w-[110px] cursor-pointer mt-1">
                                <option value="forever">Forever</option>
                                <option value="1">1 Day</option>
                                <option value="3">3 Days</option>
                                <option value="7">7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                                <option value="90">90 Days</option>
                                <option value="365">1 Year</option>
                            </select>
                        </template>
                    </div>
                </template>
            </td>

            {{-- WA Status Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div>
                        <template x-if="row.whatsapp_subscription_status === 'subscribed'">
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                <span
                                    class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Subscribed</span>
                            </div>
                        </template>
                        <template x-if="row.whatsapp_subscription_status === 'unsubscribed'">
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">Opt-out</span>
                            </div>
                        </template>
                        <template x-if="!row.whatsapp_subscription_status">
                            <span class="text-[10px] font-black text-surface-300 uppercase tracking-widest">—</span>
                        </template>
                    </div>
                </template>
                <template x-if="editing">
                    <select x-model="row.whatsapp_subscription_status"
                        class="bg-white border border-gray-100 text-[10px] font-black uppercase rounded-sm focus:ring-0 focus:outline-none p-1">
                        <option value="subscribed">Subscribed</option>
                        <option value="unsubscribed">Opt-out</option>
                    </select>
                </template>
            </td>

            {{-- Email Lead Score Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @if(!empty($email->email))
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xs font-black text-primary-600">{{ $email->email_lead_score ?? 1 }}/10</span>
                        <div class="w-16 bg-surface-200/50 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-primary-500 to-indigo-500 h-full rounded-full"
                                style="width: {{ ($email->email_lead_score ?? 1) * 10 }}%"></div>
                        </div>
                    </div>
                @else
                    <span class="text-surface-300 text-xs">—</span>
                @endif
            </td>

            {{-- WhatsApp Lead Score Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @if(!empty($email->whatsapp_number))
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xs font-black text-emerald-600">{{ $email->whatsapp_lead_score ?? 1 }}/10</span>
                        <div class="w-16 bg-surface-200/50 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full"
                                style="width: {{ ($email->whatsapp_lead_score ?? 1) * 10 }}%"></div>
                        </div>
                    </div>
                @else
                    <span class="text-surface-300 text-xs">—</span>
                @endif
            </td>

            {{-- Pipeline / Stage Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                @if($email->deals && $email->deals->isNotEmpty())
                    <div class="flex flex-col gap-1.5">
                        @foreach($email->deals as $deal)
                            @if($deal->stage && $deal->stage->pipeline)
                                <a href="{{ route('admin.pipelines.show', $deal->stage->pipeline) }}#deal-{{ $deal->id }}"
                                    class="flex items-center gap-1.5 text-xs font-bold text-surface-700 hover:scale-[1.02] transition-transform active:scale-[0.98]">
                                    <span
                                        class="px-1.5 py-0.5 rounded-sm bg-brand/5 border border-brand/10 text-brand text-[9px] font-black uppercase tracking-wider">
                                        {{ $deal->stage->pipeline->name }}
                                    </span>
                                    <span class="text-surface-400">/</span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded-sm text-[9px] font-black uppercase tracking-wider border"
                                        style="background-color: {{ $deal->stage->color }}10; border-color: {{ $deal->stage->color }}30; color: {{ $deal->stage->color }}">
                                        {{ $deal->stage->name }}
                                    </span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <span class="text-surface-300 text-xs">—</span>
                @endif
            </td>

            {{-- Deal Notes Column --}}
            <td class="px-8 py-4 max-w-[200px] truncate">
                @if($email->deals && $email->deals->isNotEmpty())
                    <div class="flex flex-col gap-1">
                        @foreach($email->deals as $deal)
                            @if($deal->notes)
                                <div class="text-xs font-semibold text-surface-600 truncate max-w-[180px]" title="{{ $deal->notes }}">
                                    {{ $deal->notes }}
                                </div>
                            @else
                                <span class="text-surface-300 text-xs">—</span>
                            @endif
                        @endforeach
                    </div>
                @else
                    <span class="text-surface-300 text-xs">—</span>
                @endif
            </td>

            {{-- Added By Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-[11px] font-semibold text-surface-500 text-center">
                <span x-text="row.added_by"></span>
            </td>

            {{-- Actions Column --}}
            <td class="px-8 py-4 text-right">
                <div class="flex justify-end gap-1">
                    <template x-if="!editing">
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button @click="open = !open"
                                class="p-2 text-surface-400 hover:text-brand hover:bg-white border border-transparent hover:border-gray-100 rounded-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" x-transition x-cloak style="display: none;"
                                class="absolute right-0 top-full mt-1 w-48 bg-white rounded-sm shadow-lg border border-gray-100 py-1 z-50 text-left">

                                <button @click="$dispatch('open-profile', row.id); open = false"
                                    class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 hover:text-brand transition-colors">
                                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    View Profile
                                </button>

                                @if($emailList->canPerformAction('edit_contact'))
                                    <button @click="editing = true; open = false"
                                        class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 hover:text-brand transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Edit Profile
                                    </button>
                                @endif

                                @if($emailList->canPerformAction('add_contact'))
                                    @if($isMaster)
                                        <button
                                            @click="$dispatch('open-add-contact', { original_row_id: row.original_row_id, type: 'email' }); open = false"
                                            class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Add Email
                                        </button>
                                        <button
                                            @click="$dispatch('open-add-contact', { original_row_id: row.original_row_id, type: 'whatsapp' }); open = false"
                                            class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            Add WhatsApp
                                        </button>
                                    @endif
                                @endif

                                @if($emailList->canPerformAction('delete_contact'))
                                    <template x-if="!row.is_archived">
                                        <button @click="$dispatch('archive-email', { id: row.id }); open = false"
                                            class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-amber-600 hover:bg-amber-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                            Archive
                                        </button>
                                    </template>

                                    <template x-if="row.is_archived">
                                        <button @click="$dispatch('unarchive-email', { id: row.id }); open = false"
                                            class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-emerald-600 hover:bg-emerald-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Restore Active
                                        </button>
                                    </template>
                                    @if($isMaster)
                                        <button @click="$dispatch('open-transfer-contact', { contact: row }); open = false"
                                            class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            Transfer Contact
                                        </button>
                                    @endif

                                    <div class="h-px bg-gray-100 my-1"></div>

                                    <button @click="$dispatch('open-single-permanent-delete', { id: row.id }); open = false"
                                        class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Permanent Delete
                                    </button>
                                @endif

                                @if($isMaster)
                                    <button @click="$dispatch('open-send-pipeline', { contact: row }); open = false"
                                        class="w-full flex items-center px-4 py-2 text-[10px] font-black uppercase tracking-widest text-surface-600 hover:bg-surface-50 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                        </svg>
                                        Send to Pipeline
                                    </button>
                                @endif
                            </div>
                        </div>
                    </template>
                    <template x-if="editing">
                        <div class="flex gap-1">
                            <button @click="save()" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-sm"
                                :disabled="saving">
                                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            <button @click="editing = false" class="p-2 text-red-500 hover:bg-red-50 rounded-sm"
                                :disabled="saving">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </td>
        </tr>
    @endforeach
@endforeach