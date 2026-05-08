@extends('layouts.app')
@section('title', 'Sender Management')
@section('heading', 'Sender Emails')

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ mode: 'bulk' }">

    {{-- Add Sender Card --}}
    <div class="glass-card rounded-md">
        <div class="p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h3 class="text-xl font-bold text-surface-900 tracking-tight">Register New Sender</h3>
                    <p class="text-sm text-surface-500 mt-1">Choose your mailing scale. Bulk mode uses high-speed enterprise infrastructure.</p>
                </div>
                
                <div class="inline-flex p-1 bg-surface-100 rounded-md">
                    <button @click="mode = 'bulk'" 
                            type="button"
                            :class="mode === 'bulk' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500 hover:text-surface-900'"
                            class="px-8 py-2 rounded-md text-sm font-black transition-all duration-200 cursor-pointer uppercase tracking-widest">
                        Bulk Mode
                    </button>
                    <button @click="mode = 'normal'" 
                            type="button"
                            :class="mode === 'normal' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500 hover:text-surface-900'"
                            class="px-8 py-2 rounded-md text-sm font-black transition-all duration-200 cursor-pointer uppercase tracking-widest">
                        Normal Mode
                    </button>
                </div>
            </div>

            <form action="{{ route('admin.senders.store') }}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="mode" x-bind:value="mode">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Sender Display Name</label>
                        <input type="text" name="from_name" class="form-input rounded-md !bg-surface-50 border-surface-200 py-3" placeholder="e.g. Arzonet Support" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Authorized Email Address</label>
                        <input type="email" name="email" class="form-input rounded-md !bg-surface-50 border-surface-200 py-3" placeholder="e.g. hello@arzonet.com" required>
                    </div>
                </div>

                {{-- Bulk Mode Intelligence --}}
                <div x-show="mode === 'bulk'" class="p-8 bg-indigo-50/30 rounded-md border border-indigo-100/50 flex items-start gap-6 animate-fade-in" x-cloak>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-md flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="space-y-2">
                        <h4 class="text-sm font-black text-indigo-900 uppercase tracking-widest">Enterprise Acceleration Active</h4>
                        <p class="text-xs text-indigo-700 leading-relaxed max-w-2xl">Bulk mode automatically routes your emails through our high-performance {{ strtoupper(config('emailplatform.bulk_provider')) }} infrastructure. This mode is optimized for large lists and high deliverability. <strong>No additional configuration required.</strong></p>
                    </div>
                </div>

                {{-- Normal Mode / SMTP Fields --}}
                <div x-show="mode === 'normal'" class="space-y-8 pt-8 border-t border-surface-100 animate-fade-in" x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-input rounded-md !bg-surface-50 border-surface-200" placeholder="smtp.gmail.com" :required="mode === 'normal'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Port</label>
                            <input type="number" name="smtp_port" class="form-input rounded-md !bg-surface-50 border-surface-200" placeholder="587" :required="mode === 'normal'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Username</label>
                            <input type="text" name="smtp_username" class="form-input rounded-md !bg-surface-50 border-surface-200" placeholder="your-email@gmail.com" :required="mode === 'normal'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest">App Password</label>
                            <input type="password" name="smtp_password" class="form-input rounded-md !bg-surface-50 border-surface-200" placeholder="••••••••••••" :required="mode === 'normal'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Security Type</label>
                            <select name="smtp_encryption" class="form-select rounded-md !bg-surface-50 border-surface-200">
                                <option value="tls">TLS (Recommended)</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary rounded-md px-12 py-4 shadow-xl shadow-primary-200 text-sm font-black uppercase tracking-widest">
                        Initialize Sender
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Senders Registry --}}
    <div class="glass-card overflow-hidden rounded-md">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-[10px] uppercase tracking-[0.2em]">Infrastructure Registry</h4>
            <span class="text-[10px] font-bold text-surface-400">{{ $senders->count() }} Managed Nodes</span>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th class="!pl-8">Node Identity</th>
                    <th>Scale</th>
                    <th>Protocol</th>
                    <th>Status</th>
                    <th class="text-right !pr-8">Manage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($senders as $sender)
                <tr class="group">
                    <td class="!pl-8">
                        <div class="flex items-center gap-4 py-2">
                            <div @class([
                                'w-10 h-10 rounded-md flex items-center justify-center font-black text-sm border shadow-sm',
                                'bg-indigo-50 text-indigo-600 border-indigo-100' => in_array($sender->type, ['ses', 'sendgrid']),
                                'bg-surface-50 text-surface-600 border-surface-100' => $sender->type === 'smtp',
                            ])>
                                {{ strtoupper(substr($sender->from_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-surface-900 leading-tight">{{ $sender->from_name }}</p>
                                <p class="text-[11px] font-medium text-surface-400 mt-1">{{ $sender->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php $isBulk = in_array($sender->type, ['ses', 'sendgrid']); @endphp
                        <span @class([
                            'text-[9px] font-black px-2 py-0.5 rounded-md border uppercase tracking-widest',
                            'bg-indigo-50 text-indigo-700 border-indigo-100' => $isBulk,
                            'bg-surface-50 text-surface-600 border-surface-200' => !$isBulk,
                        ])>
                            {{ $isBulk ? 'Bulk' : 'Normal' }}
                        </span>
                    </td>
                    <td>
                        <span class="text-[10px] font-bold text-surface-500 uppercase">{{ $sender->type }}</span>
                    </td>
                    <td>
                        @php
                            $statusCls = match($sender->status) {
                                'verified' => 'bg-green-100 text-green-700 border-green-200',
                                'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                default => 'bg-surface-100 text-surface-500 border-surface-200',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border {{ $statusCls }}">
                            <span class="w-1 h-1 rounded-full bg-current"></span>
                            {{ $sender->status }}
                        </span>
                    </td>
                    <td class="!pr-8">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            @if(in_array($sender->type, ['ses', 'sendgrid']) && $sender->status !== 'verified')
                            <a href="{{ route('admin.senders.verify', $sender) }}" class="p-2 text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Check Verification">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </a>
                            @endif

                            <form action="{{ route('admin.senders.destroy', $sender) }}" method="POST" onsubmit="return confirm('Decommission this node?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-surface-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-32 opacity-50">
                        <p class="text-sm italic">No infrastructure nodes registered.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
