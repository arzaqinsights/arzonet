@foreach($emails as $email)
<tr class="group hover:bg-surface-50/50 transition-colors border-b border-surface-50 last:border-0">
    {{-- Email Column --}}
    <td class="!pl-8 !py-3">
        <div class="font-bold text-surface-900 text-xs leading-tight">{{ $email->email }}</div>
    </td>

    {{-- Name Column --}}
    <td class="!py-3">
        <div class="text-[11px] text-surface-600 font-medium">{{ $email->name ?? '—' }}</div>
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
        <td class="!py-3">
            <div class="text-[11px] text-surface-600 font-medium">
                {{ $email->meta[$field] ?? '—' }}
            </div>
        </td>
    @endforeach

    {{-- Status Column --}}
    <td class="!py-3">
        @php
            $cls = match($email->status) {
                'valid' => 'bg-emerald-100 text-emerald-700',
                'invalid' => 'bg-red-100 text-red-700',
                'duplicate' => 'bg-amber-100 text-amber-700',
                default => 'bg-surface-100 text-surface-600',
            };
        @endphp
        <div class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest {{ $cls }}">
            {{ $email->status }}
        </div>
    </td>

    {{-- Subscription Column --}}
    <td class="!py-3">
        @php
            $subCls = match($email->subscription_status) {
                'subscribed' => 'text-emerald-600',
                'unsubscribed' => 'text-red-600',
                'bounced' => 'text-amber-600',
                default => 'text-surface-400',
            };
        @endphp
        <div class="text-[10px] font-black uppercase tracking-tighter {{ $subCls }}">
            {{ $email->subscription_status ?? 'subscribed' }}
        </div>
    </td>

    {{-- Date Columns --}}
    <td class="!py-3">
        <div class="text-[10px] text-surface-400 font-bold">{{ $email->created_at?->format('M d, Y') ?? '—' }}</div>
    </td>
    <td class="!py-3">
        <div class="text-[10px] text-surface-400 font-bold">{{ $email->updated_at?->format('M d, Y') ?? '—' }}</div>
    </td>

    {{-- Actions Column --}}
    <td class="!pr-8 !py-3 text-right">
        <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="editContact({{ $email->id }})" class="p-1.5 text-surface-400 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit Contact">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button @click="deleteEmail({{ $email->id }})" class="p-1.5 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete Contact">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </td>
</tr>
@endforeach
