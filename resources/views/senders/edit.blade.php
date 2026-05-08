@extends('layouts.app')
@section('title', 'Edit Sender')
@section('heading', 'Modify Infrastructure')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 animate-slide-up" x-data="{ type: '{{ $sender->type }}' }">
    
    <div class="glass-card rounded-md">
        <div class="p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-surface-900 tracking-tight">Edit Sender: {{ $sender->email }}</h3>
                    <p class="text-sm text-surface-500 mt-1">Update your delivery limits and authentication credentials.</p>
                </div>
                <div class="px-4 py-1.5 rounded-md bg-surface-100 text-surface-600 text-xs font-black uppercase tracking-widest">
                    {{ $sender->type }}
                </div>
            </div>

            <form action="{{ route('admin.senders.update', $sender) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Display Name</label>
                        <input type="text" name="from_name" value="{{ $sender->from_name }}" class="form-input !bg-surface-50 border-surface-200 rounded-md" required>
                    </div>
                    <div class="space-y-2 opacity-60">
                        <label class="text-sm font-bold text-surface-700">Sender Address (Immutable)</label>
                        <input type="email" value="{{ $sender->email }}" class="form-input !bg-surface-100 border-surface-200 rounded-md cursor-not-allowed" disabled>
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
                            <input type="number" name="emails_per_second" class="form-input !bg-white rounded-md" value="{{ $sender->emails_per_second }}" min="1" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Per Minute</label>
                            <input type="number" name="emails_per_minute" class="form-input !bg-white rounded-md" value="{{ $sender->emails_per_minute }}" min="1" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Daily Max Limit</label>
                            <input type="number" name="daily_limit" class="form-input !bg-white rounded-md" value="{{ $sender->daily_limit }}" min="1" required>
                        </div>
                    </div>
                </div>

                {{-- SMTP Fields --}}
                <template x-if="type === 'smtp'">
                    <div class="space-y-6 pt-6 border-t border-surface-100">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-bold text-surface-700">SMTP Host</label>
                                <input type="text" name="smtp_host" value="{{ $sender->smtp_host }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Port</label>
                                <input type="number" name="smtp_port" value="{{ $sender->smtp_port }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Username</label>
                                <input type="text" name="smtp_username" value="{{ $sender->smtp_username }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">App Password</label>
                                <input type="password" name="smtp_password" value="{{ $sender->smtp_password }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Security</label>
                                <select name="smtp_encryption" class="form-select !bg-surface-50 border-surface-200 rounded-md">
                                    <option value="tls" {{ $sender->smtp_encryption === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $sender->smtp_encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="none" {{ $sender->smtp_encryption === 'none' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- AWS SES Fields --}}
                <template x-if="type === 'ses'">
                    <div class="space-y-6 pt-6 border-t border-surface-100">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Access Key ID</label>
                                <input type="text" name="ses_key" value="{{ $sender->ses_key }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Secret Access Key</label>
                                <input type="password" name="ses_secret" value="{{ $sender->ses_secret }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-surface-700">Region</label>
                                <input type="text" name="ses_region" value="{{ $sender->ses_region }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                            </div>
                        </div>
                    </div>
                </template>

                {{-- SendGrid Fields --}}
                <template x-if="type === 'sendgrid'">
                    <div class="space-y-2 pt-6 border-t border-surface-100">
                        <label class="text-sm font-bold text-surface-700">SendGrid API Key</label>
                        <input type="password" name="sendgrid_api_key" value="{{ $sender->sendgrid_api_key }}" class="form-input !bg-surface-50 border-surface-200 rounded-md">
                    </div>
                </template>

                <div class="pt-8 flex items-center justify-between">
                    <a href="{{ route('admin.senders.index') }}" class="text-sm font-bold text-surface-500 hover:text-surface-900 transition-colors">
                        &larr; Back to Senders
                    </a>
                    <button type="submit" class="btn btn-primary px-12 rounded-md">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
