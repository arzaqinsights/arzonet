@extends('layouts.app')

@section('title', 'WhatsApp Settings')

@section('content')
<div class="max-w-3xl space-y-6">
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-sm text-emerald-700 text-sm font-bold flex items-center gap-3">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Connected Numbers --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        <div class="border-b border-color px-6 py-4 bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Connected WhatsApp Numbers</h2>
        </div>
        @forelse($accounts as $account)
        <div class="px-6 py-5 border-b border-color last:border-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-sm bg-brand/10 text-brand flex items-center justify-center">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black text-surface-900">{{ $account->display_name ?: 'Unnamed Number' }}</p>
                        <p class="text-[10px] text-surface-400 font-bold">{{ $account->phone_number }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $account->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-surface-300' }}"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest {{ $account->status === 'active' ? 'text-emerald-600' : 'text-surface-400' }}">{{ $account->status }}</span>
                    </div>
                    <form action="{{ route('admin.whatsapp.accounts.destroy', $account) }}" method="POST"
                          onsubmit="return confirm('Disconnect this number? This will stop all campaigns.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 border border-red-100 rounded-sm hover:bg-red-50 text-red-500 transition-colors">
                            Disconnect
                        </button>
                    </form>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div class="bg-surface-50 rounded-sm p-3">
                    <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">WABA ID</p>
                    <p class="text-[10px] font-mono text-surface-600 mt-0.5">{{ $account->whatsapp_business_account_id }}</p>
                </div>
                <div class="bg-surface-50 rounded-sm p-3">
                    <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">Phone Number ID</p>
                    <p class="text-[10px] font-mono text-surface-600 mt-0.5">{{ $account->phone_number_id }}</p>
                </div>
                <div class="bg-surface-50 rounded-sm p-3">
                    <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">Quality Rating</p>
                    @php $rating = $account->metadata['quality_rating'] ?? 'UNKNOWN'; @endphp
                    <p class="text-[10px] font-black mt-0.5 {{ $rating === 'GREEN' ? 'text-emerald-600' : ($rating === 'RED' ? 'text-red-500' : 'text-surface-500') }}">
                        {{ $rating }}
                    </p>
                </div>
            </div>
        </div>
        @empty
        <div class="py-12 text-center">
            <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">No numbers connected.</p>
            <a href="{{ route('admin.whatsapp.accounts.index') }}" class="inline-block mt-3 text-brand text-[10px] font-black uppercase tracking-widest hover:underline">
                Go to Phone Numbers →
            </a>
        </div>
        @endforelse
    </div>

    {{-- Webhook Settings --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        <div class="border-b border-color px-6 py-4 bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Webhook Configuration</h2>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-2">Webhook URL (set this in your Meta App)</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ url('/api/whatsapp/webhook') }}"
                        class="flex-grow border border-color rounded-sm px-4 py-2.5 text-sm font-mono bg-surface-50 text-surface-700">
                    <button onclick="navigator.clipboard.writeText('{{ url('/api/whatsapp/webhook') }}')"
                        class="text-[9px] font-black uppercase tracking-widest px-4 py-2.5 border border-color rounded-sm hover:bg-surface-50 transition-colors">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <div>
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-2">Verify Token</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ config('services.whatsapp.webhook_verify_token') }}"
                        class="flex-grow border border-color rounded-sm px-4 py-2.5 text-sm font-mono bg-surface-50 text-surface-700">
                    <button onclick="navigator.clipboard.writeText('{{ config('services.whatsapp.webhook_verify_token') }}')"
                        class="text-[9px] font-black uppercase tracking-widest px-4 py-2.5 border border-color rounded-sm hover:bg-surface-50 transition-colors">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-sm p-4">
                <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-1">Setup Instructions</p>
                <ol class="text-[11px] text-amber-700 space-y-1 list-decimal list-inside">
                    <li>Go to Meta Developer Portal → Your App → WhatsApp → Configuration</li>
                    <li>Set the Callback URL to the Webhook URL above</li>
                    <li>Set Verify Token to the value above</li>
                    <li>Subscribe to: messages, messaging_postbacks, message_deliveries, message_reads</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Meta App Config --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        <div class="border-b border-color px-6 py-4 bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Meta App Configuration</h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            <div class="bg-surface-50 rounded-sm p-4">
                <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">App ID</p>
                <p class="text-sm font-mono text-surface-700 mt-1">{{ config('services.whatsapp.app_id') }}</p>
            </div>
            <div class="bg-surface-50 rounded-sm p-4">
                <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">API Version</p>
                <p class="text-sm font-mono text-surface-700 mt-1">{{ config('services.whatsapp.api_version') }}</p>
            </div>
            <div class="bg-surface-50 rounded-sm p-4">
                <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">Configuration ID</p>
                <p class="text-sm font-mono text-surface-700 mt-1">{{ config('services.whatsapp.config_id') }}</p>
            </div>
            <div class="bg-surface-50 rounded-sm p-4">
                <p class="text-[8px] font-black text-surface-400 uppercase tracking-widest">App Secret</p>
                <p class="text-sm font-mono text-surface-700 mt-1">••••••••••••••</p>
            </div>
        </div>
    </div>
</div>
@endsection
