@extends('layouts.super-admin')

@section('content')
<div class="p-6 md:p-10">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-black mb-2 font-['Outfit']">User Management</h1>
            <p class="text-surface-500 font-medium">Monitor all platform users and their plan compliance.</p>
        </div>
        <a href="{{ route('admin.super.dashboard') }}" class="text-xs font-black uppercase text-surface-400 hover:text-black transition-all flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="bg-white border border-surface-200 rounded-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-surface-50 border-b border-surface-200">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">User Details</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Plan</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Contacts Usage</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Emails Usage</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($users as $user)
                @php
                    $sub = $user->subscription;
                    $contactLimit = $sub->contacts_limit ?? 0;
                    $emailLimit = $sub->emails_limit ?? 0;
                    
                    $contactProgress = $contactLimit > 0 ? ($user->emails_count / $contactLimit) * 100 : 0;
                    $emailProgress = $emailLimit > 0 ? ($user->sent_emails_count / $emailLimit) * 100 : 0;
                @endphp
                <tr class="hover:bg-surface-50/30 transition-colors">
                    <td class="px-6 py-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-surface-100 flex items-center justify-center text-xs font-black text-surface-500 uppercase">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-black">{{ $user->name }} @if($user->isSuperAdmin()) <span class="text-[10px] bg-brand/10 text-brand px-1.5 rounded-sm ml-1">ADMIN</span> @endif</span>
                                <span class="text-xs text-surface-400">{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-black">{{ $sub->plan_name ?? 'No Plan' }}</span>
                            <span class="text-[10px] text-surface-400 uppercase font-black">{{ $sub->status ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="w-48">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black text-black">{{ number_format($user->emails_count) }} / {{ number_format($contactLimit) }}</span>
                                <span class="text-[10px] font-black {{ $contactProgress > 90 ? 'text-red-600' : 'text-surface-400' }}">{{ round($contactProgress) }}%</span>
                            </div>
                            <div class="w-full h-1 bg-surface-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $contactProgress > 90 ? 'bg-red-500' : 'bg-brand' }} transition-all duration-500" style="width: {{ min(100, $contactProgress) }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6">
                        <div class="w-48">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black text-black">{{ number_format($user->sent_emails_count) }} / {{ number_format($emailLimit) }}</span>
                                <span class="text-[10px] font-black {{ $emailProgress > 90 ? 'text-red-600' : 'text-surface-400' }}">{{ round($emailProgress) }}%</span>
                            </div>
                            <div class="w-full h-1 bg-surface-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $emailProgress > 90 ? 'bg-red-500' : 'bg-black' }} transition-all duration-500" style="width: {{ min(100, $emailProgress) }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <button class="text-[10px] font-black uppercase text-surface-400 hover:text-brand transition-all">Details</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-surface-50 border-t border-surface-200">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
