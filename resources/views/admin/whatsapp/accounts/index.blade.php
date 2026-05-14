@extends('layouts.app')

@section('title', 'WhatsApp Phone Numbers')

@section('header-actions')
    <button onclick="launchWhatsAppSignup()" id="connect-btn" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all flex items-center gap-2">
        <i class="fa-solid fa-plus text-sm"></i>
        <span>Add Number</span>
    </button>
@endsection

@section('content')
<div class="space-y-4" x-data="{ loading: false, error: null }">
    {{-- Onboarding Loading Overlay --}}
    <div x-show="loading" x-cloak class="fixed inset-0 bg-surface-900/80 z-[100] flex items-center justify-center backdrop-blur-sm">
        <div class="text-center space-y-6">
            <div class="relative w-16 h-16 mx-auto">
                <div class="absolute inset-0 border-4 border-white/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-t-brand rounded-full animate-spin"></div>
            </div>
            <div>
                <h3 class="text-white font-black uppercase tracking-widest">Connecting Number...</h3>
                <p class="text-white/40 text-[10px] font-black uppercase tracking-widest mt-2">Please wait while we finalize the connection</p>
            </div>
        </div>
    </div>

    {{-- Error / Success Banners --}}
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-sm flex items-center gap-3 text-emerald-700">
        <i class="fa-solid fa-circle-check"></i>
        <span class="text-sm font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <div x-show="error" x-cloak class="p-4 bg-red-50 border border-red-100 rounded-sm flex items-center justify-between">
        <div class="flex items-center gap-3 text-red-700">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span class="text-sm font-bold" x-text="error"></span>
        </div>
        <button @click="error = null"><i class="fa-solid fa-xmark text-red-400"></i></button>
    </div>

    {{-- Stats Summary --}}
    @if($accounts->count() > 0)
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Total Numbers</p>
            <p class="text-3xl font-black text-surface-900 mt-1">{{ $accounts->count() }}</p>
        </div>
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Active</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ $accounts->where('status', 'active')->count() }}</p>
        </div>
        <div class="bg-white border border-color rounded-sm p-5">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Inactive</p>
            <p class="text-3xl font-black text-surface-400 mt-1">{{ $accounts->where('status', '!=', 'active')->count() }}</p>
        </div>
    </div>
    @endif

    {{-- Accounts Table --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        @if($accounts->count() > 0)
        <div class="border-b border-color px-6 py-4 flex items-center justify-between bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Connected Numbers</h2>
            <span class="text-[9px] text-surface-400 font-black uppercase tracking-widest">{{ $accounts->count() }} Account(s)</span>
        </div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-color bg-surface-50/50">
                    <th class="text-left px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">Business / Number</th>
                    <th class="text-left px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">WABA ID</th>
                    <th class="text-left px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">Quality</th>
                    <th class="text-left px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">Msg Limit</th>
                    <th class="text-left px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">Status</th>
                    <th class="text-right px-6 py-3 text-[9px] font-black text-surface-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-color/50">
                @foreach($accounts as $account)
                <tr class="hover:bg-surface-50/50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-sm bg-brand/10 text-brand flex items-center justify-center flex-shrink-0">
                                <i class="fa-brands fa-whatsapp text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-black text-surface-900">{{ $account->display_name ?: 'Unnamed Number' }}</p>
                                <p class="text-[10px] font-bold text-surface-400 mt-0.5">{{ $account->phone_number ?: 'Number not fetched' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-[10px] font-mono text-surface-600">{{ $account->whatsapp_business_account_id }}</p>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $rating = $account->metadata['quality_rating'] ?? 'UNKNOWN';
                            $ratingClass = match($rating) { 'GREEN' => 'text-emerald-600 bg-emerald-50', 'YELLOW' => 'text-amber-600 bg-amber-50', 'RED' => 'text-red-600 bg-red-50', default => 'text-surface-400 bg-surface-100' };
                        @endphp
                        <span class="px-2 py-0.5 rounded-sm text-[9px] font-black uppercase tracking-widest {{ $ratingClass }}">{{ $rating }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-black text-surface-600 uppercase">
                            {{ str_replace('TIER_', '', $account->metadata['messaging_limit'] ?? 'UNKNOWN') }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full {{ $account->status === 'active' ? 'bg-emerald-500' : 'bg-surface-300' }}"></span>
                            <span class="text-[9px] font-black uppercase tracking-widest {{ $account->status === 'active' ? 'text-emerald-600' : 'text-surface-400' }}">{{ $account->status }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('admin.whatsapp.accounts.register', $account) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                   class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 border border-brand/20 bg-brand/5 rounded-sm hover:bg-brand/10 transition-colors text-brand">
                                    <i class="fa-solid fa-bolt mr-1"></i> Activate on Cloud API
                                </button>
                            </form>
                            <a href="{{ route('admin.whatsapp.templates.sync', $account) }}" 
                               onclick="return confirm('Sync templates from Meta for this number?')"
                               class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 border border-color rounded-sm hover:bg-surface-50 transition-colors text-surface-600">
                                <i class="fa-solid fa-rotate mr-1"></i> Sync Templates
                            </a>
                            <form action="{{ route('admin.whatsapp.accounts.destroy', $account) }}" method="POST" 
                                  onsubmit="return confirm('Disconnect this number?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[9px] font-black uppercase tracking-widest px-3 py-1.5 border border-red-100 rounded-sm hover:bg-red-50 transition-colors text-red-500">
                                    <i class="fa-solid fa-power-off mr-1"></i> Disconnect
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="py-24 text-center">
            <div class="w-16 h-16 rounded-full bg-surface-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-brands fa-whatsapp text-3xl text-surface-300"></i>
            </div>
            <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">No Numbers Connected</h3>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-2">Connect your WhatsApp Business API number to get started.</p>
            <button onclick="launchWhatsAppSignup()" class="mt-6 bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all">
                Begin Official Onboarding
            </button>
        </div>
        @endif
    </div>
</div>

{{-- Meta SDK --}}
<script>
    window.fbAsyncInit = function() {
        FB.init({ appId: '{{ config('services.whatsapp.app_id') }}', cookie: true, xfbml: true, version: '{{ config('services.whatsapp.api_version') }}' });
    };

    let wabaIdFromMeta = null, phoneNumberIdFromMeta = null;

    window.addEventListener('message', function(event) {
        if (event.origin !== "https://www.facebook.com") return;
        try {
            const data = JSON.parse(event.data);
            if (data.type === 'WA_EMBEDDED_SIGNUP' && data.event === 'FINISH' && data.data) {
                wabaIdFromMeta = data.data.waba_id || null;
                phoneNumberIdFromMeta = data.data.phone_number_id || null;
            }
        } catch (e) {}
    });

    function launchWhatsAppSignup() {
        wabaIdFromMeta = null; phoneNumberIdFromMeta = null;
        const btn = document.getElementById('connect-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch animate-spin"></i><span>Waiting for Meta...</span>';

        FB.login(function (response) {
            if (response.authResponse && response.authResponse.code) {
                finishOnboarding(response.authResponse.code, wabaIdFromMeta, phoneNumberIdFromMeta);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-plus"></i><span>Add Number</span>';
            }
        }, { config_id: '{{ config('services.whatsapp.config_id') }}', response_type: 'code', override_default_response_type: true });
    }

    function finishOnboarding(code, wabaId, phoneNumberId) {
        const el = document.querySelector('[x-data]');
        const scope = el ? (el._x_dataStack ? el._x_dataStack[0] : null) : null;
        if (scope) { scope.loading = true; scope.error = null; }

        fetch('{{ route('admin.whatsapp.accounts.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ code, waba_id: wabaId, phone_number_id: phoneNumberId })
        })
        .then(async r => { const d = await r.json(); if (!r.ok) throw new Error(d.message); return d; })
        .then(() => window.location.reload())
        .catch(err => {
            if (scope) { scope.loading = false; scope.error = err.message; }
            document.getElementById('connect-btn').disabled = false;
            document.getElementById('connect-btn').innerHTML = '<i class="fa-solid fa-plus"></i><span>Add Number</span>';
        });
    }
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
@endsection
