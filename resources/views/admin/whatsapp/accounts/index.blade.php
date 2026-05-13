@extends('layouts.app')

@section('title', 'WhatsApp Connectivity')

@section('header-actions')
    <button onclick="launchWhatsAppSignup()" id="connect-btn" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all flex items-center gap-2">
        <i class="fa-brands fa-whatsapp text-sm"></i>
        <span>Connect WhatsApp</span>
    </button>
@endsection

@section('content')
<div class="space-y-6" x-data="{ loading: false, error: null }">
    {{-- Onboarding Loading Overlay --}}
    <div x-show="loading" x-cloak class="fixed inset-0 bg-surface-900/80 z-[100] flex items-center justify-center backdrop-blur-sm">
        <div class="text-center space-y-6 animate-slide-up">
            <div class="relative w-20 h-20 mx-auto">
                <div class="absolute inset-0 border-4 border-white/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-t-brand rounded-full animate-spin"></div>
            </div>
            <div>
                <h3 class="text-white font-black uppercase tracking-widest text-xl">Finalizing Connection</h3>
                <p class="text-white/40 text-[10px] font-black uppercase tracking-widest mt-2">Exchanging credentials and subscribing webhooks</p>
            </div>
        </div>
    </div>

    {{-- Error Banner --}}
    <div x-show="error" x-cloak class="p-6 bg-red-50 border border-red-100 rounded-sm flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-red-900 uppercase tracking-widest">Authentication Error</p>
                <p class="text-sm font-bold text-red-600" x-text="error"></p>
            </div>
        </div>
        <button @click="error = null" class="text-red-400 hover:text-red-900 transition-colors">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Connected Accounts Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($accounts as $account)
        <div class="bg-white border border-color rounded-sm overflow-hidden group hover:border-brand transition-all duration-300">
            {{-- Card Header --}}
            <div class="p-6 border-b border-color bg-surface-50/50 flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-sm bg-brand text-white flex items-center justify-center">
                        <i class="fa-brands fa-whatsapp text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">{{ $account->display_name ?: 'Unnamed Number' }}</h3>
                        <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mt-0.5">{{ $account->phone_number }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-sm bg-emerald-50 text-emerald-600 border border-emerald-100 text-[8px] font-black uppercase tracking-widest">
                    <span class="w-1 h-1 bg-emerald-600 rounded-full animate-pulse"></span>
                    {{ $account->status }}
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Quality Rating</p>
                        <div class="flex items-center gap-1.5">
                            @php
                                $rating = $account->metadata['quality_rating'] ?? 'UNKNOWN';
                                $ratingColor = match($rating) {
                                    'GREEN' => 'text-emerald-500',
                                    'YELLOW' => 'text-amber-500',
                                    'RED' => 'text-red-500',
                                    default => 'text-surface-400'
                                };
                            @endphp
                            <span class="text-xs font-black uppercase tracking-tight {{ $ratingColor }}">{{ $rating }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Messaging Limit</p>
                        <span class="text-xs font-black text-surface-900 uppercase tracking-tight">
                            {{ str_replace('TIER_', '', $account->metadata['messaging_limit'] ?? '1K') }}
                        </span>
                    </div>
                </div>

                <div class="pt-4 border-t border-color flex items-center justify-between">
                    <div class="flex flex-col">
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-0.5">WABA ID</p>
                        <p class="text-[10px] font-bold text-surface-600">{{ $account->whatsapp_business_account_id }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form action="{{ route('admin.whatsapp.accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Disconnecting this number will stop all active campaigns. Proceed?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-surface-300 hover:text-red-500 hover:bg-red-50 rounded-sm transition-all" title="Disconnect Number">
                                <i class="fa-solid fa-power-off text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center border-2 border-dashed border-color rounded-sm bg-surface-50/50">
            <div class="w-16 h-16 rounded-full bg-white border border-color flex items-center justify-center mx-auto mb-6">
                <i class="fa-brands fa-whatsapp text-3xl text-surface-200"></i>
            </div>
            <h3 class="text-lg font-black text-surface-900 tracking-tight uppercase">Ready to Expand?</h3>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-2 max-w-sm mx-auto">Connect your WhatsApp Business API and start broadcasting templates to your contacts instantly.</p>
            <button onclick="launchWhatsAppSignup()" class="mt-8 bg-brand text-white text-[10px] font-black uppercase tracking-widest px-10 py-4 rounded-sm hover:bg-black transition-all shadow-xl shadow-brand/10">
                Begin Official Onboarding
            </button>
        </div>
        @endforelse
    </div>
</div>

{{-- Meta SDK Integration Logic --}}
<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '{{ config('services.whatsapp.app_id') }}',
            cookie     : true,
            xfbml      : true,
            version    : '{{ config('services.whatsapp.api_version') }}'
        });
    };

    function launchWhatsAppSignup() {
        console.log('Launching Meta Signup...');
        const btn = document.getElementById('connect-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch animate-spin"></i><span>Waiting for Meta...</span>';

        FB.login(function (response) {
            console.log('Meta FB.login response:', response);
            if (response.authResponse && response.authResponse.code) {
                const code = response.authResponse.code;
                console.log('Obtained Code:', code);
                finishOnboarding(code);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i><span>Connect WhatsApp</span>';
                console.error('Onboarding failed: No code returned from Meta.', response);
                alert('Onboarding failed: No code returned from Meta. Check console for details.');
            }
        }, {
            config_id: '{{ config('services.whatsapp.config_id') }}',
            response_type: 'code',
            override_default_response_type: true,
            scope: 'whatsapp_business_management,whatsapp_business_messaging',
        });
    }

    function finishOnboarding(code) {
        console.log('Sending code to backend...');
        
        // Safer way to access Alpine data
        const el = document.querySelector('[x-data]');
        let scope = null;
        if (el) {
            scope = el._x_dataStack ? el._x_dataStack[0] : (el.__x ? el.__x.$data : null);
        }

        if (scope) {
            scope.loading = true;
            scope.error = null;
        }

        fetch('{{ route('admin.whatsapp.accounts.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: code })
        })
        .then(async response => {
            const data = await response.json();
            console.log('Backend response:', data);
            if (!response.ok) throw new Error(data.message || 'Server error during onboarding');
            return data;
        })
        .then(data => {
            if (data.success) {
                alert('Success! WhatsApp connected.');
                window.location.reload();
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            scope.loading = false;
            scope.error = err.message;
            alert('Error: ' + err.message);
            const btn = document.getElementById('connect-btn');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-brands fa-whatsapp"></i><span>Connect WhatsApp</span>';
        });
    }
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
@endsection

