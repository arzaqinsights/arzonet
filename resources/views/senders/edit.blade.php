@extends('layouts.app')
@section('title', 'Edit Sender')
@section('heading', 'Modify Infrastructure')

@section('content')
<div class="max-w-3xl mx-auto animate-slide-up">
    <div class="glass-card rounded-md">
        <div class="p-8">
            <form action="{{ route('admin.senders.update', $sender) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Display Name</label>
                        <input type="text" name="from_name" value="{{ $sender->from_name }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Sender Address</label>
                        <input type="email" name="email" value="{{ $sender->email }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                        <p class="text-[10px] text-surface-400 font-bold uppercase tracking-tight">Identity: {{ strtoupper($sender->type) }} Mode</p>
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
                            <input type="number" name="emails_per_second" value="{{ $sender->emails_per_second }}" class="form-input rounded-md !bg-white" min="1" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Per Minute</label>
                            <input type="number" name="emails_per_minute" value="{{ $sender->emails_per_minute }}" class="form-input rounded-md !bg-white" min="1" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase tracking-wider">Daily Max Limit</label>
                            <input type="number" name="daily_limit" value="{{ $sender->daily_limit }}" class="form-input rounded-md !bg-white" min="1" required>
                        </div>
                    </div>
                </div>

                {{-- SMTP Settings (Only if type is SMTP) --}}
                @if($sender->type === 'smtp')
                <div class="space-y-6 pt-6 border-t border-surface-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-sm font-bold text-surface-700">SMTP Host</label>
                            <input type="text" name="smtp_host" value="{{ $sender->smtp_host }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Port</label>
                            <input type="number" name="smtp_port" value="{{ $sender->smtp_port }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">Username</label>
                            <input type="text" name="smtp_username" value="{{ $sender->smtp_username }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-surface-700">App Password</label>
                            <input type="password" name="smtp_password" value="{{ $sender->smtp_password }}" class="form-input rounded-md !bg-surface-50 border-surface-200" required>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-6 bg-indigo-50/30 rounded-md border border-indigo-100/50 flex items-start gap-4">
                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-indigo-900 uppercase tracking-tight">Enterprise Infrastructure</h4>
                        <p class="text-xs text-indigo-700 mt-1">This sender uses the platform's global high-speed credentials. API keys are managed centrally for better security and performance.</p>
                    </div>
                </div>
                @endif

                <div class="pt-8 flex items-center justify-between border-t border-surface-100">
                    <a href="{{ route('admin.senders.index') }}" class="text-sm font-bold text-surface-400 hover:text-surface-900 transition-colors">
                        &larr; Back to Senders
                    </a>
                    <button type="submit" class="btn btn-primary rounded-md px-12 py-3 shadow-xl shadow-primary-100">
                        Update Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
