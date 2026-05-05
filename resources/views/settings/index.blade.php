@extends('layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
<div class="max-w-3xl mx-auto animate-fade-in">
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf @method('PUT')

        <div class="space-y-6">
            {{-- SES Configuration --}}
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    AWS SES Configuration
                </h3>
                <p class="text-sm text-surface-400 mb-4">Configure your Amazon SES credentials. These are stored in your .env file.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">SES Region</label>
                        <input type="text" name="ses_region" class="form-input" value="{{ $settings['ses_region'] }}" placeholder="us-east-1">
                    </div>
                    <div>
                        <label class="form-label">From Email</label>
                        <input type="email" name="ses_from_email" class="form-input" value="{{ $settings['ses_from_email'] }}" placeholder="hello@example.com">
                    </div>
                </div>

                <div class="mt-4 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                    <p class="text-xs text-emerald-400">✔️ SES integration is connected and active. You can manage verified sender emails from the Sender Emails tab.</p>
                </div>
            </div>

            {{-- Sending Limits --}}
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Sending Limits
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Daily Limit</label>
                        <input type="number" name="daily_limit" class="form-input" value="{{ $settings['daily_limit'] }}" min="100" required>
                    </div>
                    <div>
                        <label class="form-label">Weekly Limit</label>
                        <input type="number" name="weekly_limit" class="form-input" value="{{ $settings['weekly_limit'] }}" min="100" required>
                    </div>
                    <div>
                        <label class="form-label">Monthly Limit</label>
                        <input type="number" name="monthly_limit" class="form-input" value="{{ $settings['monthly_limit'] }}" min="100" required>
                    </div>
                </div>
            </div>

            {{-- Performance --}}
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    Performance Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Emails Per Minute</label>
                        <input type="number" name="emails_per_minute" class="form-input" value="{{ $settings['emails_per_minute'] }}" min="1" max="1000" required>
                        <p class="text-xs text-surface-500 mt-1">Rate limiting for outgoing emails</p>
                    </div>
                    <div>
                        <label class="form-label">Batch Size</label>
                        <input type="number" name="batch_size" class="form-input" value="{{ $settings['batch_size'] }}" min="10" max="500" required>
                        <p class="text-xs text-surface-500 mt-1">Emails per queue job</p>
                    </div>
                </div>
            </div>

            {{-- Cost --}}
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Cost Configuration
                </h3>
                <div>
                    <label class="form-label">Cost Per Email (USD)</label>
                    <input type="number" name="cost_per_email" class="form-input max-w-xs" value="{{ $settings['cost_per_email'] }}" min="0" step="0.0001" required>
                    <p class="text-xs text-surface-500 mt-1">AWS SES pricing: ~$0.0001 per email</p>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Settings
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
