@foreach($emails as $email)
<tr class="group hover:bg-surface-50/50 transition-all duration-200 border-b border-color last:border-0">
    {{-- Email Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <div class="font-bold text-surface-900 text-sm">{{ $email->email }}</div>
    </td>

    {{-- Name Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <div class="text-xs text-surface-600 font-bold">{{ $email->name ?? '—' }}</div>
    </td>

    {{-- Dynamic CRM Columns --}}
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
        <td class="px-8 py-4 whitespace-nowrap">
            <div class="text-xs text-surface-500 font-bold">
                {{ $email->meta[$field] ?? '—' }}
            </div>
        </td>
    @endforeach

    {{-- Status Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        @php
            $status = match($email->status) {
                'valid' => ['label' => 'Clean', 'cls' => 'bg-emerald-50 text-emerald-600 border-emerald-100'],
                'invalid' => ['label' => 'Broken', 'cls' => 'bg-red-50 text-red-600 border-red-100'],
                'duplicate' => ['label' => 'Cloned', 'cls' => 'bg-amber-50 text-amber-600 border-amber-100'],
                default => ['label' => 'Unknown', 'cls' => 'bg-surface-100 text-surface-600 border-surface-200'],
            };
        @endphp
        <div class="inline-flex items-center px-2 py-0.5 rounded-sm text-[8px] font-black uppercase tracking-widest border {{ $status['cls'] }}">
            {{ $status['label'] }}
        </div>
    </td>

    {{-- Subscription Column --}}
    <td class="px-8 py-4 whitespace-nowrap">
        @php
            $subStatus = match($email->subscription_status) {
                'subscribed' => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'cls' => 'text-emerald-500'],
                'unsubscribed' => ['icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'cls' => 'text-red-400'],
                'bounced' => ['icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'cls' => 'text-amber-500'],
                default => ['icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'cls' => 'text-surface-300'],
            };
        @endphp
        <div class="flex items-center gap-2 {{ $subStatus['cls'] }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $subStatus['icon'] }}"/></svg>
            <span class="text-[10px] font-black uppercase tracking-tighter">{{ $email->subscription_status ?? 'subscribed' }}</span>
        </div>
    </td>

    {{-- Date Columns --}}
    <td class="px-8 py-4 whitespace-nowrap">
        <div class="text-[10px] text-surface-400 font-bold uppercase tracking-tighter">{{ $email->created_at?->format('d M, Y') ?? '—' }}</div>
    </td>

    {{-- Actions Column --}}
    <td class="px-8 py-4 text-right">
        <div class="flex justify-end gap-1 transition-all duration-200">
            <button @click="editContact({{ $email->id }})" class="p-2 text-surface-400 hover:text-brand hover:bg-white border border-transparent hover:border-gray-100 rounded-sm transition-all" title="Modify Record">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button @click="deleteEmail({{ $email->id }})" class="p-2 text-surface-400 hover:text-red-600 hover:bg-white border border-transparent hover:border-gray-100 rounded-sm transition-all" title="Purge Record">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </td>
</tr>
@endforeach
