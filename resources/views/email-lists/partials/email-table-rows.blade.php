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
        $firstItem = $groupItems->first();
        $groupSize = $groupItems->count();
        $mapping = $emailList->column_mapping ?? [];
        
        $displayedFields = [];
        foreach(['company', 'job_title', 'phone', 'city'] as $field) {
            if (isset($mapping[$field])) $displayedFields[] = $field;
        }
        
        if (isset($mapping['whatsapp_number']) && !in_array('phone', $displayedFields)) {
            $displayedFields[] = 'phone';
        }
        
        foreach($mapping as $key => $val) {
            if (str_starts_with($key, 'custom_')) $displayedFields[] = $key;
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
                    whatsapp_subscription_status: '{{ $email->whatsapp_subscription_status ?? 'subscribed' }}',
                    is_archived: {{ $email->is_archived ? 'true' : 'false' }},
                    meta: @js($email->meta ?? [])
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
                    .then(r => r.json())
                    .then(() => {
                        this.saving = false;
                        this.editing = false;
                        this.fetchEmails();
                        this.refreshStats();
                    });
                }
            }" 
            class="group transition-all duration-200 border-y"
            :class="[
                editing ? 'bg-[#fafafa]' : (selectedIds.includes({{ $email->id }}) ? 'selected bg-[#f6f1ec]' : ({{ $isMaster ? 'true' : 'false' }} ? 'bg-white' : 'bg-slate-100')),
                editing ? 'hover:bg-[#fafafa]' : (selectedIds.includes({{ $email->id }}) ? 'hover:bg-[#f6f1ec]' : ({{ $isMaster ? 'true' : 'false' }} ? 'hover:bg-slate-50/50' : 'hover:bg-slate-150')),
                {{ $isLastSub ? 'true' : 'false' }} ? 'border-gray-200' : 'border-dashed border-gray-100/80'
            ]">
            
            {{-- Checkbox Column --}}
            <td class="px-8 py-4 whitespace-nowrap sticky left-0 z-9 group-[.selected]:bg-[#f6f1ec] transition-colors relative {{ $isMaster ? 'bg-white group-hover:bg-slate-50/50' : 'pl-12 bg-slate-50/60 group-hover:bg-slate-100/50' }}">
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
                        <input type="text" x-model="row.name" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                    </template>
                @else
                    <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400 select-none">
                        <span class="text-slate-300 font-bold">└─</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[8px] font-black uppercase tracking-wider border border-slate-200">
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
                            <input type="text" x-model="row.meta['{{ $field }}']" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                        </template>
                    @endif
                </td>
            @endforeach

            {{-- Segment Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @if($isMaster)
                    <template x-if="!editing">
                        <div class="text-[10px] font-bold text-surface-600 uppercase tracking-widest" x-text="row.segment_name || '—'"></div>
                    </template>
                    <template x-if="editing">
                        <input type="text" x-model="row.segment_name" class="w-20 px-2 py-1 bg-white border border-gray-100 rounded-sm text-[10px] font-bold focus:ring-0 focus:outline-none">
                    </template>
                @endif
            </td>

            {{-- Tag Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @if($isMaster)
                    <template x-if="!editing">
                        <div class="inline-flex px-2 py-0.5 rounded-sm bg-brand/5 text-brand text-[9px] font-black uppercase tracking-widest border border-brand/10" x-text="row.tags || '—'"></div>
                    </template>
                    <template x-if="editing">
                        <input type="text" x-model="row.tags" class="w-20 px-2 py-1 bg-white border border-gray-100 rounded-sm text-[9px] font-bold uppercase focus:ring-0 focus:outline-none">
                    </template>
                @endif
            </td>

            {{-- Email Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                <template x-if="!editing">
                    <div class="flex items-center gap-2">
                        <span class="{{ $isMaster ? 'font-bold text-surface-900 text-sm' : 'font-semibold text-surface-600 text-sm' }}" x-text="row.email || '—'"></span>
                        <template x-if="row.is_archived">
                            <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[8px] font-black uppercase tracking-widest rounded-sm border border-gray-200">Archived</span>
                        </template>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="email" x-model="row.email" class="w-full px-2 py-1 bg-white border border-gray-200 rounded-sm text-sm font-bold focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- WhatsApp Column --}}
            <td class="px-8 py-4 whitespace-nowrap">
                <template x-if="!editing">
                    <div class="flex items-center gap-1.5 {{ $isMaster ? 'text-xs text-surface-600 font-bold' : 'text-xs text-surface-500 font-semibold' }}">
                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                        <span x-text="row.whatsapp_number || '—'"></span>
                    </div>
                </template>
                <template x-if="editing">
                    <input type="text" x-model="row.whatsapp_number" placeholder="WhatsApp" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
                </template>
            </td>

            {{-- Health/Status Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                @php
                    $statusVal = $email->email_status ?? $email->status;
                    $status = match($statusVal) {
                        'clean', 'valid' => ['label' => 'Clean', 'cls' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
                        'risky', 'suspicious' => ['label' => 'Risky', 'cls' => 'bg-amber-50 text-amber-600 border-amber-100'],
                        'role_based' => ['label' => 'Role', 'cls' => 'bg-blue-50 text-blue-600 border-blue-100'],
                        'disposable' => ['label' => 'Temp', 'cls' => 'bg-indigo-50 text-indigo-600 border-indigo-100'],
                        'invalid', 'hard_bounce' => ['label' => 'Dead', 'cls' => 'bg-red-50 text-red-600 border-red-100'],
                        'complaint' => ['label' => 'Spam', 'cls' => 'bg-black text-white border-transparent'],
                        'blocked' => ['label' => 'Banned', 'cls' => 'bg-surface-900 text-white border-transparent'],
                        default => ['label' => 'Unknown', 'cls' => 'bg-surface-100 text-surface-600 border-surface-200'],
                    };
                    
                    $score = $email->email_score ?? 3;
                    $scoreColor = match(true) {
                        $score >= 4 => 'text-emerald-500',
                        $score == 3 => 'text-amber-500',
                        default => 'text-red-500',
                    };
                @endphp
                <div class="flex flex-col items-center gap-1">
                    <div class="inline-flex items-center px-2 py-0.5 rounded-sm text-[8px] font-black uppercase tracking-widest border {{ $status['cls'] }}">
                        {{ $status['label'] }}
                    </div>
                    <div class="flex items-center gap-0.5 mt-0.5">
                        @for($i=1; $i<=5; $i++)
                            <div class="w-1.75 h-1.75 rounded-full {{ $i <= $score ? str_replace('text', 'bg', $scoreColor) : 'bg-gray-200' }}"></div>
                        @endfor
                    </div>
                    @if($email->validation_reason)
                        <div class="text-[7px] text-gray-400 font-bold max-w-[80px] truncate" title="{{ $email->validation_reason }}">
                            {{ $email->validation_reason }}
                        </div>
                    @endif
                </div>
            </td>

            {{-- Subscription Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div class="flex items-center justify-center gap-2" :class="{
                        'text-emerald-500': row.subscription_status === 'subscribed',
                        'text-red-400': row.subscription_status === 'unsubscribed',
                        'text-amber-500': row.subscription_status === 'bounced'
                    }">
                        <span class="text-[10px] font-black uppercase tracking-tighter" x-text="row.subscription_status"></span>
                    </div>
                </template>
                <template x-if="editing">
                    <select x-model="row.subscription_status" class="bg-white border border-gray-100 text-[10px] font-black uppercase rounded-sm focus:ring-0 focus:outline-none p-1">
                        <option value="subscribed">Subscribed</option>
                        <option value="unsubscribed">Unsubscribed</option>
                        <option value="bounced">Bounced</option>
                    </select>
                </template>
            </td>

            {{-- WA Status Column --}}
            <td class="px-8 py-4 whitespace-nowrap text-center">
                <template x-if="!editing">
                    <div>
                        <template x-if="row.whatsapp_subscription_status === 'subscribed'">
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Subscribed</span>
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
                    <select x-model="row.whatsapp_subscription_status" class="bg-white border border-gray-100 text-[10px] font-black uppercase rounded-sm focus:ring-0 focus:outline-none p-1">
                        <option value="subscribed">Subscribed</option>
                        <option value="unsubscribed">Opt-out</option>
                    </select>
                </template>
            </td>

            {{-- Actions Column --}}
            <td class="px-8 py-4 text-right">
                <div class="flex justify-end gap-1">
                    <template x-if="!editing">
                        <div class="flex gap-1">
                            <button @click="editing = true" class="p-2 text-surface-400 hover:text-brand hover:bg-white border border-transparent hover:border-gray-100 rounded-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="deleteEmail(row.id)" class="p-2 text-surface-400 hover:text-red-600 hover:bg-white border border-transparent hover:border-gray-100 rounded-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                    <template x-if="editing">
                        <div class="flex gap-1">
                            <button @click="save()" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-sm" :disabled="saving">
                                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            <button @click="editing = false" class="p-2 text-red-500 hover:bg-red-50 rounded-sm" :disabled="saving">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </td>
        </tr>
    @endforeach
@endforeach
