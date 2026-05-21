{{-- PRICING SECTION — Simple 3+1 Plan Cards --}}
@php
    $plans = config('plans.plans', []);
    $custom = config('plans.custom', []);
    $rates = config('plans.rates', []);
@endphp

<section id="pricing" class="py-24 bg-white border-t border-surface-200">
    <div class="container">

        {{-- Section Header --}}
        <div class="text-center mb-20">
            <span class="px-3 py-1 bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm">Simple Pricing</span>
            <h2 class="text-4xl md:text-5xl uppercase text-black font-black mt-4 mb-4" style="font-family: 'Outfit', sans-serif;">
                Straightforward <span class="text-brand">Pricing.</span>
            </h2>
            <p class="text-lg text-gray-600 font-light max-w-2xl mx-auto">
                CRM, Email Marketing, and WhatsApp — all in a single plan. Choose the right option for your budget.
            </p>
        </div>

        {{-- 4-Column Plans Grid (3 fixed + 1 custom) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 xl:gap-8 items-stretch">

            {{-- Fixed Plans --}}
            @foreach($plans as $key => $plan)
                @php
                    $isPopular = !empty($plan['popular']);
                @endphp
                <div class="relative bg-white border {{ $isPopular ? 'border-brand shadow-xl' : 'border-surface-200 shadow-sm' }} rounded-sm flex flex-col justify-between p-6 transition-all duration-300 hover:shadow-md group">

                    {{-- Popular Badge --}}
                    @if($isPopular)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-brand text-white text-[9px] font-black uppercase tracking-widest rounded-sm">
                            Most Popular
                        </div>
                    @endif

                    <div>
                        {{-- Plan Name --}}
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-surface-900 tracking-wider uppercase" style="font-family: 'Outfit', sans-serif;">
                                {{ $plan['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium italic mt-1">{{ $plan['tagline'] }}</p>
                        </div>

                        {{-- Price --}}
                        <div class="flex items-baseline gap-1 pb-6 mb-6 border-b border-gray-100">
                            <span class="text-4xl font-black text-surface-900" style="font-family: 'Outfit', sans-serif;">
                                ₹{{ number_format($plan['price']) }}
                            </span>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">/{{ $plan['period'] }}</span>
                        </div>

                        {{-- Limits Summary --}}
                        <div class="space-y-2 mb-6 bg-surface-50 p-4 rounded-sm border border-surface-200/50">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">What's Included</p>
                            <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                <span class="text-gray-400 font-medium">Team Members:</span>
                                <span>{{ $plan['limits']['crm_users'] }} Users</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                <span class="text-gray-400 font-medium">Contacts:</span>
                                <span>{{ number_format($plan['limits']['crm_contacts']) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                <span class="text-gray-400 font-medium">Emails/month:</span>
                                <span>{{ number_format($plan['limits']['emails_per_month']) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                <span class="text-gray-400 font-medium">WhatsApp Numbers:</span>
                                <span>{{ $plan['limits']['whatsapp_numbers'] }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                <span class="text-gray-400 font-medium">WhatsApp Msgs/mo:</span>
                                <span>{{ number_format($plan['limits']['whatsapp_messages']) }}</span>
                            </div>
                        </div>

                        {{-- Features --}}
                        <div class="space-y-1.5 mb-6">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Features</p>
                            @foreach($plan['features'] as $feature)
                                <div class="flex items-start gap-2 text-xs text-gray-600 font-light leading-snug">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 text-[12px]"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Not Included --}}
                        @if(!empty($plan['not_included']))
                            <div class="pt-2 border-t border-slate-100 space-y-1.5">
                                <p class="text-[9px] font-black text-red-500 uppercase tracking-widest">Available in Higher Plans</p>
                                @foreach($plan['not_included'] as $exFeature)
                                    <div class="flex items-start gap-2 text-xs text-gray-400/80 font-light leading-snug line-through">
                                        <i class="fa-solid fa-circle-xmark text-gray-300 mt-0.5 text-[12px]"></i>
                                        <span>{{ $exFeature }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- CTA Button --}}
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="{{ route('pricing') }}?select={{ $key }}"
                           class="block w-full text-center px-4 py-3 {{ $isPopular ? 'bg-brand hover:bg-[#e05638] text-white shadow-[0_4px_12px_rgba(255,107,74,0.2)]' : 'bg-white hover:bg-gray-50 border-2 border-surface-900 text-surface-900' }} text-[10px] font-black uppercase tracking-widest rounded-sm transition-all">
                            Get {{ $plan['name'] }}
                        </a>
                    </div>
                </div>
            @endforeach

            {{-- Custom Plan Card --}}
            <div class="relative bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700 rounded-sm flex flex-col justify-between p-6 transition-all duration-300 hover:shadow-xl group text-white">
                <div>
                    <div class="mb-6">
                        <h3 class="text-lg font-black tracking-wider uppercase" style="font-family: 'Outfit', sans-serif;">
                            {{ $custom['name'] ?? 'Custom' }}
                        </h3>
                        <p class="text-xs text-slate-400 font-medium italic mt-1">{{ $custom['tagline'] ?? 'Build your own plan' }}</p>
                    </div>

                    <div class="flex items-baseline gap-1 pb-6 mb-6 border-b border-slate-700">
                        <span class="text-4xl font-black text-brand" style="font-family: 'Outfit', sans-serif;">
                            You Decide
                        </span>
                    </div>

                    <div class="space-y-2 mb-6 bg-slate-800/50 p-4 rounded-sm border border-slate-700/50">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Per Unit Rates</p>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-400 font-medium">CRM Users:</span>
                            <span class="text-brand">₹{{ $rates['crm_per_user'] ?? 600 }}/user/mo</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-400 font-medium">Contacts:</span>
                            <span class="text-brand">₹{{ $rates['crm_per_1k_contacts'] ?? 10 }}/1K/mo</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-400 font-medium">Emails:</span>
                            <span class="text-brand">₹{{ $rates['email_per_1k'] ?? 100 }}/1K/mo</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-400 font-medium">WhatsApp No.:</span>
                            <span class="text-brand">₹{{ $rates['whatsapp_per_number'] ?? 500 }}/no/mo</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-400 font-medium">WhatsApp Msgs:</span>
                            <span class="text-brand">₹0 (Billed by Meta)</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 mb-6">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2">Features</p>
                        @foreach(($custom['features'] ?? []) as $feature)
                            <div class="flex items-start gap-2 text-xs text-slate-300 font-light leading-snug">
                                <i class="fa-solid fa-circle-check text-brand mt-0.5 text-[12px]"></i>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-700">
                    <a href="{{ route('pricing') }}?select=custom"
                       class="block w-full text-center px-4 py-3 bg-brand hover:bg-[#e05638] text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-[0_4px_12px_rgba(255,107,74,0.2)]">
                        Build Your Plan →
                    </a>
                </div>
            </div>

        </div>

        {{-- ADD-ONS --}}
        @php $addons = config('plans.addons', []); @endphp
        @if(!empty($addons))
        <div class="mt-32 pt-20 border-t border-surface-200 relative overflow-hidden bg-surface-50 p-8 rounded-sm">
            <div class="absolute top-0 right-0 w-64 h-64 bg-brand/5 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-12 relative z-10">
                <div>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-widest rounded-sm">Add-Ons</span>
                    <h3 class="text-3xl uppercase text-black font-black mt-3 mb-2" style="font-family: 'Outfit', sans-serif;">
                        Add Extras As <span class="text-brand">Needed.</span>
                    </h3>
                    <p class="text-sm text-gray-500 font-light max-w-xl">
                        Need extra capacity or features on top of your plan? Add them separately.
                    </p>
                </div>
                <a href="{{ route('pricing') }}?select=custom"
                   class="inline-flex items-center justify-center px-5 py-3.5 bg-brand hover:bg-[#e05638] text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-md">
                    Configure in Custom Plan &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                @foreach($addons as $addonKey => $addon)
                    @php
                        $iconMap = [
                            'extra_contacts'         => 'fa-users-line text-brand',
                            'extra_emails'           => 'fa-envelope-circle-check text-blue-500',
                            'extra_whatsapp_number'  => 'fa-whatsapp text-green-500 fa-brands',
                            'extra_team'             => 'fa-user-plus text-indigo-500',
                            'dedicated_ip'           => 'fa-server text-emerald-500',
                            'white_label'            => 'fa-eye-slash text-stone-500',
                        ];
                        $iconClass = $iconMap[$addonKey] ?? 'fa-plus text-brand';
                    @endphp
                    <div class="bg-white border border-surface-200/60 p-5 rounded-sm hover:-translate-y-1 transition-transform duration-300 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-sm bg-surface-50 flex items-center justify-center flex-shrink-0 border border-gray-100 shadow-sm">
                            <i class="fa-solid {{ $iconClass }} text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-surface-900 uppercase tracking-wide" style="font-family: 'Outfit', sans-serif;">
                                {{ $addon['name'] }}
                            </h4>
                            <p class="text-xs text-gray-500 leading-relaxed font-light mt-1">
                                {{ $addon['desc'] }}
                            </p>
                            <p class="text-xs font-bold text-brand mt-1">{{ $addon['price_label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</section>
