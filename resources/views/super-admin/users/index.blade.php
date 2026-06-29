@extends('layouts.super-admin')

@section('content')
<div class="p-6 md:p-10" x-data="{ showSuspendModal: false, suspendUserId: null }">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-black mb-2 font-['Outfit']">User Management</h1>
            <p class="text-surface-500 font-medium">Monitor all platform users and their plan compliance.</p>
        </div>
        <a href="{{ route('admin.super.dashboard') }}" class="text-xs font-black uppercase text-surface-400 hover:text-black transition-all flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold rounded flex items-center gap-3">
            <i class="fa-solid fa-circle-xmark text-rose-500"></i>
            {{ session('error') }}
        </div>
    @endif

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
                                <span class="text-sm font-bold text-black">
                                    {{ $user->name }} 
                                    @if($user->isSuperAdmin()) 
                                        <span class="text-[10px] bg-brand/10 text-brand px-1.5 rounded-sm ml-1 font-bold">ADMIN</span> 
                                    @endif
                                    @if($user->is_suspended)
                                        <span class="text-[10px] bg-rose-100 text-rose-700 px-1.5 rounded-sm ml-1 font-bold">SUSPENDED</span>
                                    @endif
                                </span>
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
                    <td class="px-6 py-6 text-right flex items-center justify-end gap-3 h-20">
                        @if(!$user->isSuperAdmin())
                            @if($user->is_suspended)
                                <form action="{{ route('admin.super.users.unsuspend', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-black uppercase text-emerald-600 hover:text-emerald-800 transition-all cursor-pointer">Unsuspend</button>
                                </form>
                            @else
                                <button @click="suspendUserId = {{ $user->id }}; showSuspendModal = true" 
                                        class="text-[10px] font-black uppercase text-rose-600 hover:text-rose-800 transition-all cursor-pointer">
                                    Suspend
                                </button>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-surface-50 border-t border-surface-200">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Suspend Modal -->
    <div x-show="showSuspendModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showSuspendModal = false">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-sm text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 relative z-10">
                <form action="{{ route('admin.super.users.suspend') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" :value="suspendUserId">
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-black text-slate-900 uppercase font-['Outfit'] mb-2">Suspend User Account</h3>
                        <p class="text-xs text-slate-500 mb-4">Please type a suspension message. This message will be shown to the user on their dashboard blocking their access.</p>
                        
                        <div>
                            <label for="suspension_reason" class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1.5">Suspension Message</label>
                            <textarea id="suspension_reason" name="suspension_reason" required rows="4" 
                                      class="w-full text-sm border-slate-300 rounded focus:border-brand focus:ring-brand p-2 border" 
                                      placeholder="Your account has been suspended due to policy violations. Please contact support."></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-sm border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700 focus:outline-none sm:ml-3 sm:w-auto cursor-pointer">
                            Suspend Account
                        </button>
                        <button type="button" @click="showSuspendModal = false" class="mt-3 w-full inline-flex justify-center rounded-sm border border-slate-300 shadow-sm px-4 py-2 bg-white text-xs font-black uppercase tracking-wider text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:w-auto cursor-pointer">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
