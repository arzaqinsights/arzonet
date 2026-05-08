@extends('layouts.app')
@section('title', 'Sender Management')
@section('heading', 'Sender Emails')

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ type: 'ses' }">

    {{-- Add Sender Card --}}
    <div class="glass-card">
        <div class="p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h3 class="text-xl font-bold text-surface-900 tracking-tight">Add New Sender</h3>
                    <p class="text-sm text-surface-500 mt-1">Configure your outgoing email identity and credentials.</p>
                </div>
                
                <div class="inline-flex p-1 bg-surface-100 rounded-md">
                    <button @click="type = 'ses'" 
                            type="button"
                            :class="type === 'ses' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500 hover:text-surface-900'"
                            class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200 cursor-pointer">
                        AWS SES
                    </button>
                    <button @click="type = 'smtp'" 
                            type="button"
                            :class="type === 'smtp' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500 hover:text-surface-900'"
                            class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200 cursor-pointer">
                        SMTP / Gmail
                    </button>
                    <button @click="type = 'sendgrid'" 
                            type="button"
                            :class="type === 'sendgrid' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500 hover:text-surface-900'"
                            class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200 cursor-pointer">
                        SendGrid
                    </button>
                </div>
            </div>

            <form action="{{ route('admin.senders.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="type" x-bind:value="type">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Display Name</label>
                        <input type="text" name="from_name" class="form-input !bg-surface-50 border-surface-200" placeholder="e.g. Marketing Team" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Sender Address</label>
                        <input type="email" name="email" class="form-input !bg-surface-50 border-surface-200" placeholder="e.g. hello@example.com" required>
                    </div>
                </div>

                {{-- Throughput Limits --}}
                <div class="p-6 bg-primary-50/30 rounded-md border border-primary-100/50">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-md bg-primary-100 flex items-center justify-center text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h4 class="font-bold text-surface-900 text-sm">Throughput & Rate Limiting</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Per Second</label>
                            <input type="number" name="emails_per_second" class="form-input !bg-white" value="1" min="1" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Per Minute</label>
                            <input type="number" name="emails_per_minute" class="form-input !bg-white" value="30" min="1" required>
                            <p class="text-[10px] text-surface-400">Set 30-40 for standard SMTP accounts.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Daily Max Limit</label>
                            <input type="number" name="daily_limit" class="form-input !bg-white" value="1000" min="1" required>
                        </div>
                    </div>
                </div>

                {{-- SMTP Fields --}}
                <div x-show="type === 'smtp'" class="space-y-6 pt-6 border-t border-surface-100" x-transition x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-bold text-surface-700">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-input !bg-surface-50 border-surface-200" placeholder="smtp.gmail.com" :required="type === 'smtp'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Port</label>
                            <input type="number" name="smtp_port" class="form-input !bg-surface-50 border-surface-200" placeholder="587" :required="type === 'smtp'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Username</label>
                            <input type="text" name="smtp_username" class="form-input !bg-surface-50 border-surface-200" placeholder="your-email@gmail.com" :required="type === 'smtp'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">App Password</label>
                            <input type="password" name="smtp_password" class="form-input !bg-surface-50 border-surface-200" placeholder="••••••••••••" :required="type === 'smtp'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Security</label>
                            <select name="smtp_encryption" class="form-select !bg-surface-50 border-surface-200">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- AWS SES Fields --}}
                <div x-show="type === 'ses'" class="space-y-6 pt-6 border-t border-surface-100" x-transition x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Access Key ID</label>
                            <input type="text" name="ses_key" class="form-input !bg-surface-50 border-surface-200" placeholder="AKIA..." :required="type === 'ses'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Secret Access Key</label>
                            <input type="password" name="ses_secret" class="form-input !bg-surface-50 border-surface-200" placeholder="••••••••" :required="type === 'ses'">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Region</label>
                            <input type="text" name="ses_region" class="form-input !bg-surface-50 border-surface-200" value="us-east-1" :required="type === 'ses'">
                        </div>
                    </div>
                </div>

                {{-- SendGrid Fields --}}
                <div x-show="type === 'sendgrid'" class="space-y-6 pt-6 border-t border-surface-100" x-transition x-cloak>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">SendGrid API Key</label>
                        <input type="password" name="sendgrid_api_key" class="form-input !bg-surface-50 border-surface-200" placeholder="SG.xxxx" :required="type === 'sendgrid'">
                        <p class="text-[10px] text-surface-400">Provide an API key with 'Mail Send' permissions.</p>
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="btn btn-primary px-8">
                        Deploy Infrastructure
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Senders List --}}
    <div class="glass-card overflow-hidden">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-xs uppercase tracking-[0.2em]">Active Infrastructures</h4>
            <span class="text-[10px] font-bold text-surface-400">{{ $senders->count() }} Configured</span>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Identity</th>
                    <th>Protocol</th>
                    <th>Verification</th>
                    <th class="text-right">Manage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($senders as $sender)
                <tr class="group">
                    <td>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-md bg-primary-50 flex items-center justify-center text-primary-600 font-extrabold text-sm shadow-sm">
                                {{ strtoupper(substr($sender->from_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-surface-900 leading-tight">{{ $sender->from_name }}</p>
                                <p class="text-[11px] font-medium text-surface-400 mt-1">{{ $sender->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-[10px] font-black px-2.5 py-1 rounded-md bg-surface-100 text-surface-600 border border-surface-200 uppercase tracking-tighter">
                            {{ $sender->type }}
                        </span>
                    </td>
                    <td>
                        @php
                            $cls = match($sender->status) {
                                'verified' => 'badge-success',
                                'pending' => 'badge-warning',
                                'failed' => 'badge-danger',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <span class="badge {{ $cls }}">
                            <span class="w-1.5 h-1.5 rounded-md bg-current mr-2"></span>
                            {{ ucfirst($sender->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            @if($sender->type === 'ses' && $sender->status !== 'verified')
                            <form action="{{ route('admin.senders.retry', $sender) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Check Status / Retry">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </form>
                            @endif

                            <a href="{{ route('admin.senders.edit', $sender) }}" class="p-2 text-surface-600 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit Settings">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            <form action="{{ route('admin.senders.test', $sender) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-amber-600 hover:bg-amber-50 rounded-md transition-colors" title="Test Connection">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </button>
                            </form>
                            <form action="{{ route('admin.senders.destroy', $sender) }}" method="POST" onsubmit="return confirm('Remove this sender?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-20">
                        <div class="max-w-xs mx-auto">
                            <div class="w-16 h-16 bg-surface-50 rounded-md flex items-center justify-center mx-auto mb-4 text-surface-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <h5 class="text-surface-900 font-bold">No Senders Yet</h5>
                            <p class="text-xs text-surface-500 mt-1">Configure your first sender to start mailing.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($senders->hasPages())
        <div class="px-8 py-6 border-t border-surface-100 bg-surface-50/30">
            {{ $senders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
