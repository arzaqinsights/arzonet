@extends('layouts.app')
@section('title', 'DNS Configuration')
@section('heading', 'Verify Domain: ' . $domain->domain)

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ 
    copied: null,
    copy(text, id) {
        navigator.clipboard.writeText(text);
        this.copied = id;
        setTimeout(() => { if(this.copied === id) this.copied = null }, 2000);
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
                @php
                    $fullHost = $record['host'];
                    $shortHost = str_replace('.' . $domain->domain, '', $fullHost);
                @endphp
                <tr class="hover:bg-surface-50/50 transition-colors">
                    <td class="px-8 py-6">
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black rounded uppercase border border-indigo-100">
                            {{ $record['type'] ?? 'CNAME' }}
                        </span>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col">
                                <code class="text-xs font-mono text-indigo-600 bg-indigo-50/50 px-2 py-1 rounded w-fit">{{ $shortHost }}</code>
                                <span class="text-[9px] text-surface-400 mt-1">.{{ $domain->domain }} (omitted for copy)</span>
                            </div>
                            <button @click="copy('{{ $shortHost }}', 'host-{{ $loop->index }}')" class="relative flex items-center gap-2 p-2 rounded-md hover:bg-indigo-50 text-surface-400 hover:text-indigo-600 transition-all group">
                                <svg x-show="copied !== 'host-{{ $loop->index }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="copied === 'host-{{ $loop->index }}'" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-show="copied === 'host-{{ $loop->index }}'" class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-surface-900 text-white text-[10px] rounded pointer-events-none whitespace-nowrap">Copied!</span>
                            </button>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-3">
                            <code class="text-xs font-mono text-surface-600 break-all">{{ $record['data'] }}</code>
                            <button @click="copy('{{ $record['data'] }}', 'val-{{ $loop->index }}')" class="relative flex items-center gap-2 p-2 rounded-md hover:bg-indigo-50 text-surface-400 hover:text-indigo-600 transition-all group">
                                <svg x-show="copied !== 'val-{{ $loop->index }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="copied === 'val-{{ $loop->index }}'" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-show="copied === 'val-{{ $loop->index }}'" class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-surface-900 text-white text-[10px] rounded pointer-events-none whitespace-nowrap">Copied!</span>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- DMARC Recommendation --}}
    <div @class([
        'glass-card rounded-md border-l-4 overflow-hidden',
        'border-green-500' => $dmarcRecord,
        'border-amber-500' => !$dmarcRecord,
    ])>
        <div class="p-8">
            <div class="flex items-start gap-6">
                <div @class([
                    'w-10 h-10 rounded-md flex items-center justify-center shrink-0',
                    'bg-green-50 text-green-600' => $dmarcRecord,
                    'bg-amber-50 text-amber-600' => !$dmarcRecord,
                ])>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="flex-1 space-y-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h4 class="text-base font-bold text-surface-900">DMARC Authentication</h4>
                            @if($dmarcRecord)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest rounded">Active</span>
                            @else
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-black uppercase tracking-widest rounded">Recommended</span>
                            @endif
                        </div>
                        <p class="text-sm text-surface-500 mt-1">DMARC protects your domain from spoofing and is mandatory for high-volume senders like Gmail and Yahoo.</p>
                    </div>

                    @if($dmarcRecord)
                        <div class="bg-surface-50 p-4 rounded-md border border-surface-100">
                            <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Current Record</p>
                            <code class="text-xs font-mono text-surface-600 break-all">{{ $dmarcRecord }}</code>
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-sm text-surface-600">Add this <span class="font-bold">TXT</span> record to your DNS to enable DMARC protection:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-surface-50 p-4 rounded-md border border-surface-100">
                                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Host / Name</p>
                                    <div class="flex items-center justify-between">
                                        <code class="text-xs font-mono text-indigo-600">_dmarc</code>
                                        <button @click="copy('_dmarc', 'dmarc-host')" class="relative p-1 rounded hover:bg-indigo-50 text-surface-400 hover:text-indigo-600 transition-all">
                                            <svg x-show="copied !== 'dmarc-host'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                            <svg x-show="copied === 'dmarc-host'" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="bg-surface-50 p-4 rounded-md border border-surface-100">
                                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Value</p>
                                    <div class="flex items-center justify-between">
                                        <code class="text-xs font-mono text-indigo-600">v=DMARC1; p=none;</code>
                                        <button @click="copy('v=DMARC1; p=none;', 'dmarc-val')" class="relative p-1 rounded hover:bg-indigo-50 text-surface-400 hover:text-indigo-600 transition-all">
                                            <svg x-show="copied !== 'dmarc-val'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                            <svg x-show="copied === 'dmarc-host'" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
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
