<table class="data-table">
    <thead>
        <tr>
            <th class="!pl-6">Recipient</th>
            <th>Status</th>
            <th>Message ID</th>
            <th class="text-right !pr-6">Sent At</th>
        </tr>
    </thead>
    <tbody id="logs-body">
        @forelse($logs as $log)
        <tr>
            <td class="!pl-6">
                <span class="font-bold text-surface-900">{{ $log->email_address }}</span>
            </td>
            <td>
                @php 
                    $lcls = match($log->status) { 
                        'delivered' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                        'sent' => 'bg-primary-50 text-primary-600 border-primary-100', 
                        'bounced', 'failed', 'dropped', 'invalid' => 'bg-rose-50 text-rose-600 border-rose-100', 
                        'blocked' => 'bg-orange-50 text-orange-600 border-orange-100',
                        'spamreport', 'complaint' => 'bg-amber-50 text-amber-600 border-amber-100',
                        'unsubscribed' => 'bg-purple-50 text-purple-600 border-purple-100',
                        'deferred' => 'bg-amber-50 text-amber-600 border-amber-100',
                        'pending' => 'bg-surface-50 text-surface-600 border-surface-100',
                        default => 'bg-surface-50 text-surface-400 border-surface-100' 
                    }; 
                @endphp
                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border {{ $lcls }}">
                    {{ $log->status }}
                </span>
                @if($log->error_message && $log->status !== 'pending')
                    <p class="text-[9px] text-rose-500 font-medium truncate max-w-[150px] mt-1" title="{{ $log->error_message }}">{{ $log->error_message }}</p>
                @endif
            </td>
            <td class="font-mono text-[10px] text-surface-400">{{ $log->message_id ?? 'N/A' }}</td>
            <td class="text-right !pr-6 text-surface-500 font-medium text-xs">
                {{ $log->sent_at ? $log->sent_at->format('H:i:s') : '—' }}
                @if(isset($log->open_count) && $log->open_count > 0)
                <div class="text-[9px] text-sky-500 font-bold uppercase mt-1">Opened ({{ $log->open_count }})</div>
                @endif
                @if(isset($log->click_count) && $log->click_count > 0)
                <div class="text-[9px] text-indigo-500 font-bold uppercase mt-1">Clicked ({{ $log->click_count }})</div>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center py-12 text-surface-400 italic">No logs found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="p-4 border-t border-surface-100 bg-surface-50/50 logs-pagination">
    {{ $logs->links() }}
</div>
