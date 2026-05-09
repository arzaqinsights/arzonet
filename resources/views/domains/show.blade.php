@extends('layouts.app')
@section('title', 'DNS Configuration')
@section('heading', 'Verify Domain: ' . $domain->domain)

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ 
    copy(text) {
        navigator.clipboard.writeText(text);
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied to clipboard!', type: 'success' } }));
    }
}">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.domains.index') }}" class="inline-flex items-center gap-2 text-xs font-black text-surface-400 uppercase tracking-widest hover:text-surface-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7"/></svg>
            Back to Registry
        </a>
        
        <div class="flex items-center gap-4">
            <span class="text-[10px] font-bold text-surface-400 uppercase">Status: </span>
            <span @class([
                'px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-widest border',
                'bg-green-100 text-green-700 border-green-200' => $domain->status === 'verified',
                'bg-amber-100 text-amber-700 border-amber-200' => $domain->status === 'pending',
            ])>
                {{ $domain->status }}
            </span>
        </div>
    </div>

    {{-- Setup Instructions --}}
    <div class="glass-card rounded-md border-l-4 border-indigo-500">
        <div class="p-8 flex gap-8">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-md flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="space-y-2">
                <h3 class="text-lg font-bold text-surface-900">Configure DNS Records</h3>
                <p class="text-sm text-surface-500 leading-relaxed max-w-3xl">To authenticate your domain, you must add the following CNAME records to your DNS provider (e.g., Cloudflare, GoDaddy, Namecheap). This allows SendGrid to sign your emails and verify domain ownership.</p>
            </div>
        </div>
    </div>

    {{-- DNS Records Table --}}
    <div class="glass-card overflow-hidden rounded-md">
        <table class="w-full">
            <thead class="bg-surface-50 border-b border-surface-100">
                <tr>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-surface-400 uppercase tracking-widest">Type</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-surface-400 uppercase tracking-widest">Host / Name</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-surface-400 uppercase tracking-widest">Value / Points To</th>
                    <th class="px-8 py-4 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($domain->dns_records as $type => $record)
                <tr class="hover:bg-surface-50/50 transition-colors">
                    <td class="px-8 py-6">
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black rounded uppercase border border-indigo-100">
                            {{ $record['type'] ?? 'CNAME' }}
                        </span>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-2">
                            <code class="text-xs font-mono text-indigo-600 bg-indigo-50/50 px-2 py-1 rounded">{{ $record['host'] }}</code>
                            <button @click="copy('{{ $record['host'] }}')" class="text-surface-400 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-2">
                            <code class="text-xs font-mono text-surface-600 break-all">{{ $record['data'] }}</code>
                            <button @click="copy('{{ $record['data'] }}')" class="text-surface-400 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Verification Actions --}}
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-8 bg-surface-900 rounded-md shadow-2xl overflow-hidden relative">
        <div class="absolute top-0 right-0 opacity-10 pointer-events-none">
            <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-3.5-9H7v2h1.5v4h2v-4H12v-2H8.5V9h2V7h-2v2H7v2h1.5z"/></svg>
        </div>
        
        <div class="relative z-10">
            <h4 class="text-lg font-bold text-white tracking-tight">Ready to verify?</h4>
            <p class="text-surface-400 text-sm mt-1">Once you've added the records, click the button below to validate with SendGrid.</p>
        </div>

        <div class="flex items-center gap-4 relative z-10">
            <div class="text-right hidden md:block">
                <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Propagation Warning</p>
                <p class="text-[11px] text-surface-400 italic">DNS changes can take up to 24-48 hours to propagate.</p>
            </div>
            
            <form action="{{ route('admin.domains.verify', $domain) }}" method="POST">
                @csrf
                <button type="submit" 
                        class="px-10 py-4 bg-white text-surface-900 rounded-md font-black text-sm uppercase tracking-widest hover:bg-primary-50 transition-all shadow-xl">
                    Verify DNS Records
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
