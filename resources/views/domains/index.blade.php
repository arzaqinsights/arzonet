@extends('layouts.app')
@section('title', 'Domain Authentication')
@section('heading', 'Authenticated Domains')

@section('content')
<div class="space-y-8 animate-slide-up">

    {{-- Add Domain Card --}}
    <div class="glass-card rounded-md">
        <div class="p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h3 class="text-xl font-bold text-surface-900 tracking-tight">Authenticate New Domain</h3>
                    <p class="text-sm text-surface-500 mt-1">Verify your business domain to unlock high-speed bulk sending without individual email verification.</p>
                </div>
            </div>

            <form action="{{ route('admin.domains.store') }}" method="POST" class="max-w-2xl">
                @csrf
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Business Domain Name</label>
                        <div class="flex gap-4">
                            <input type="text" name="domain" 
                                   class="form-input rounded-md !bg-surface-50 border-surface-200 py-3 flex-1" 
                                   placeholder="e.g. mybrand.com" required>
                            <button type="submit" class="btn btn-primary rounded-md px-8 shadow-xl shadow-primary-200 text-sm font-black uppercase tracking-widest whitespace-nowrap">
                                Add Domain
                            </button>
                        </div>
                        <p class="text-[10px] text-surface-400 mt-2 font-medium">
                            <span class="text-red-500 font-bold uppercase mr-1">Note:</span> 
                            Public providers like Gmail, Yahoo, or Outlook are not supported for domain authentication.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Domains Registry --}}
    <div class="glass-card overflow-hidden rounded-md">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-[10px] uppercase tracking-[0.2em]">Domain Infrastructure</h4>
            <span class="text-[10px] font-bold text-surface-400">{{ $domains->count() }} Authenticated Domains</span>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th class="!pl-8">Domain</th>
                    <th>Status</th>
                    <th>Verified At</th>
                    <th>Linked Senders</th>
                    <th class="text-right !pr-8">Manage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($domains as $domain)
                <tr class="group">
                    <td class="!pl-8">
                        <div class="flex items-center gap-4 py-2">
                            <div @class([
                                'w-10 h-10 rounded-md flex items-center justify-center font-black text-sm border shadow-sm',
                                'bg-indigo-50 text-indigo-600 border-indigo-100' => $domain->status === 'verified',
                                'bg-surface-50 text-surface-400 border-surface-100' => $domain->status === 'pending',
                            ])>
                                {{ strtoupper(substr($domain->domain, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-surface-900">{{ $domain->domain }}</p>
                                <p class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">SendGrid Auth ID: {{ $domain->sendgrid_domain_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $statusCls = match($domain->status) {
                                'verified' => 'bg-green-100 text-green-700 border-green-200',
                                'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                default => 'bg-surface-100 text-surface-500 border-surface-200',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border {{ $statusCls }}">
                            <span class="w-1 h-1 rounded-full bg-current"></span>
                            {{ $domain->status }}
                        </span>
                    </td>
                    <td>
                        <span class="text-[11px] text-surface-500 font-medium">
                            {{ $domain->verified_at ? $domain->verified_at->format('M d, Y H:i') : '---' }}
                        </span>
                    </td>
                    <td>
                        <span class="text-[11px] font-bold text-surface-600">
                            {{ $domain->senders_count ?? $domain->senders()->count() }} Active
                        </span>
                    </td>
                    <td class="!pr-8">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <a href="{{ route('admin.domains.show', $domain) }}" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors" title="DNS Records & Verification">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-5.618 2.04c-.161.433-.255.902-.255 1.392 0 4.14 2.808 7.625 6.643 8.647l.216.057.216-.057c3.835-1.022 6.643-4.507 6.643-8.647 0-.49-.094-.959-.255-1.392z"/></svg>
                            </a>

                            <form action="{{ route('admin.domains.destroy', $domain) }}" method="POST" onsubmit="return confirm('Remove domain authentication? All linked senders will be disabled.')">
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
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            </div>
                            <p class="text-sm italic">No authenticated domains. Add your first domain to get started.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
