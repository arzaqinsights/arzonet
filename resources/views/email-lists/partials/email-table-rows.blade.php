@foreach($emails as $email)
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
<tr x-data="{ 
        editing: false, 
        saving: false,
        row: {
            id: {{ $email->id }},
            email: '{{ $email->email }}',
            whatsapp_number: '{{ $email->whatsapp_number ?? '' }}',
            name: '{{ $email->name ?? '' }}',
            segment_name: '{{ $email->segment_name ?? '' }}',
            tags: '{{ $email->tags ?? '' }}',
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
            });
        }
    }" 
    class="group hover:bg-surface-50/50 transition-all duration-200 border-b border-color last:border-0"
    :class="editing ? 'bg-surface-50/80' : (selectedIds.includes({{ $email->id }}) ? 'bg-brand/5' : '')">
    
    {{-- Checkbox Column --}}
    <td class="px-8 py-4 whitespace-nowrap sticky left-0 bg-white z-9">
        <input type="checkbox" :value="{{ $email->id }}" x-model="selectedIds" class="w-4 h-4 rounded-sm border-gray-200 text-brand focus:ring-brand focus:ring-offset-0 cursor-pointer">
    </td>

    {{-- Email Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <template x-if="!editing">
            <div class="font-bold text-surface-900 text-sm flex items-center gap-2">
                <span x-text="row.email"></span>
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
            <div class="text-xs text-surface-600 font-bold flex items-center gap-1.5">
                <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                <span x-text="row.whatsapp_number || '—'"></span>
            </div>
        </template>
        <template x-if="editing">
            <input type="text" x-model="row.whatsapp_number" placeholder="WhatsApp" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
        </template>
    </td>

    {{-- WA Status Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <template x-if="row.whatsapp_subscription_status === 'subscribed'">
            <div class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Subscribed</span>
            </div>
        </template>
        <template x-if="row.whatsapp_subscription_status === 'unsubscribed'">
            <div class="flex items-center gap-1.5">
                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">Opt-out</span>
            </div>
        </template>
        <template x-if="!row.whatsapp_subscription_status">
            <span class="text-[10px] font-black text-surface-300 uppercase tracking-widest">—</span>
        </template>
    </td>

    {{-- Name Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <template x-if="!editing">
            <div class="text-xs text-surface-600 font-bold" x-text="row.name || '—'"></div>
        </template>
        <template x-if="editing">
            <input type="text" x-model="row.name" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
        </template>
    </td>

    {{-- Dynamic CRM Columns --}}
    @foreach($displayedFields as $field)
        <td class="px-8 py-4 whitespace-nowrap">
            <template x-if="!editing">
                <div class="text-xs text-surface-500 font-bold" x-text="row.meta['{{ $field }}'] || '—'"></div>
            </template>
            <template x-if="editing">
                <input type="text" x-model="row.meta['{{ $field }}']" class="w-full px-2 py-1 bg-white border border-gray-100 rounded-sm text-xs font-bold focus:ring-0 focus:outline-none">
            </template>
        </td>
    @endforeach

    {{-- Segment Column --}}
    <td class="px-8 py-4 whitespace-nowrap text-center">
        <template x-if="!editing">
            <div class="text-[10px] font-bold text-surface-600 uppercase tracking-widest" x-text="row.segment_name || '—'"></div>
        </template>
        <template x-if="editing">
            <input type="text" x-model="row.segment_name" class="w-20 px-2 py-1 bg-white border border-gray-100 rounded-sm text-[10px] font-bold focus:ring-0 focus:outline-none">
        </template>
    </td>

    {{-- Tag Column --}}
    <td class="px-8 py-4 whitespace-nowrap text-center">
        <template x-if="!editing">
            <div class="inline-flex px-2 py-0.5 rounded-sm bg-brand/5 text-brand text-[9px] font-black uppercase tracking-widest border border-brand/10" x-text="row.tags || '—'"></div>
        </template>
        <template x-if="editing">
            <input type="text" x-model="row.tags" class="w-20 px-2 py-1 bg-white border border-gray-100 rounded-sm text-[9px] font-bold uppercase focus:ring-0 focus:outline-none">
        </template>
    </td>

    {{-- Health/Status Column --}}
    <td class="px-8 py-4 whitespace-nowrap text-center">
        @php
            $status = match($email->email_status ?? $email->status) {
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
