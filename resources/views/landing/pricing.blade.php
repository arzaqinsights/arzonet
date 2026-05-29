@extends('layouts.landing')
@section('title', 'Pricing Plans — Arzonet')
@section('meta_description', 'Simple pricing for Email, WhatsApp & CRM. 3 ready-made plans + Custom plan builder. Pay only for what you use.')

@section('content')
<!-- Pricing Hero -->
<section class="relative py-20 overflow-hidden bg-slate-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-brand/20 via-slate-950 to-slate-950"></div>
    <div class="relative container text-center z-10">
        <span class="px-3 py-1 bg-brand/20 text-brand text-xs font-black uppercase tracking-widest rounded-full">Simple Pricing</span>
        <h1 class="text-4xl md:text-6xl uppercase font-black mt-4 mb-6 leading-tight tracking-tight font-['Outfit']">
            Straightforward <span class="text-brand">Pricing</span>
        </h1>
        <p class="text-lg text-slate-400 font-light max-w-3xl mx-auto mb-10 leading-relaxed">
            CRM, Email, and WhatsApp — all in one plan. Choose from our fixed plans or build your own custom plan.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="#plans" class="inline-flex items-center gap-2 bg-brand hover:bg-[#e05638] text-white px-8 py-4 rounded-sm font-bold text-sm uppercase tracking-wider transition-all duration-300 hover:-translate-y-0.5 shadow-[0_8px_30px_rgb(255,107,74,0.25)]">
                View Plans <i class="fa-solid fa-arrow-down"></i>
            </a>
            <a href="#configurator" class="inline-flex items-center gap-2 border border-white/20 hover:bg-white/5 text-white px-8 py-4 rounded-sm font-bold text-sm uppercase tracking-wider transition-all duration-300">
                Build Custom Plan <i class="fa-solid fa-wand-magic-sparkles"></i>
            </a>
        </div>
    </div>
</section>

<!-- Fixed Plans Section -->
<section id="plans" class="py-24 bg-white border-b border-slate-200">
    <div class="container">
        <div class="text-center mb-16">
            <span class="px-3 py-1 bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm">Ready-Made Plans</span>
            <h2 class="text-3xl md:text-4xl uppercase text-slate-900 font-black font-['Outfit'] mt-4">Choose a Plan & Get Started</h2>
            <p class="text-sm text-slate-500 font-medium mt-2">All plans include CRM, Email Marketing, and WhatsApp Business.</p>
        </div>

        @php
            $plans = config('plans.plans', []);
            $custom = config('plans.custom', []);
            $rates = config('plans.rates', []);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 xl:gap-8 items-stretch">
            {{-- Fixed Plans --}}
            @foreach($plans as $key => $plan)
                @php $isPopular = !empty($plan['popular']); @endphp
                <div class="relative bg-white border {{ $isPopular ? 'border-brand shadow-xl' : 'border-surface-200 shadow-sm' }} rounded-sm flex flex-col justify-between p-6 transition-all duration-300 hover:shadow-md group">

                    @if($isPopular)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-brand text-white text-[9px] font-black uppercase tracking-widest rounded-sm">
                            Most Popular
                        </div>
                    @endif

                    <div>
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-surface-900 tracking-wider uppercase" style="font-family: 'Outfit', sans-serif;">
                                {{ $plan['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium italic mt-1">{{ $plan['tagline'] }}</p>
                        </div>

                        <div class="flex items-baseline gap-1 pb-6 mb-6 border-b border-gray-100">
                            <span class="text-4xl font-black text-surface-900" style="font-family: 'Outfit', sans-serif;">
                                ₹{{ number_format($plan['price']) }}
                            </span>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">/{{ $plan['period'] }}</span>
                        </div>

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

                        <div class="space-y-1.5 mb-6">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Features</p>
                            @foreach($plan['features'] as $feature)
                                <div class="flex items-start gap-2 text-xs text-gray-600 font-light leading-snug">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 text-[12px]"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>

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

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <a href="{{ route('admin.billing.plans') }}?plan={{ $key }}"
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
                            <span class="text-brand">₹{{ $rates['crm_per_user'] ?? 100 }}/user/mo</span>
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
                    <a href="#configurator"
                       class="block w-full text-center px-4 py-3 bg-brand hover:bg-[#e05638] text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-[0_4px_12px_rgba(255,107,74,0.2)]">
                        Build Your Plan →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Plan Builder / Configurator -->
<section id="configurator" class="py-24 bg-slate-50 border-b border-slate-200">
    <div class="container"
         x-data="{
            rates: {{ json_encode($rates) }},
            sliders: {{ json_encode($custom['sliders'] ?? []) }},
            
            // Current limits (if active subscription exists)
            current_crm_users: {{ $subscription ? ($subscription->team_limit ?? 0) : 0 }},
            current_crm_contacts: {{ $subscription ? ($subscription->contacts_limit ?? 0) : 0 }},
            current_emails_per_month: {{ $subscription ? ($subscription->emails_limit ?? 0) : 0 }},
            current_whatsapp_numbers: {{ $subscription ? ($subscription->whatsapp_limit ?? 0) : 0 }},
            current_whatsapp_messages: 0,
            
            // Slider values
            crm_users: {{ $subscription ? ($subscription->team_limit ?: 1) : ($custom['sliders']['crm_users']['default'] ?? 5) }},
            crm_contacts: {{ $subscription ? ($subscription->contacts_limit ?: 1000) : ($custom['sliders']['crm_contacts']['default'] ?? 10000) }},
            emails_per_month: {{ $subscription ? ($subscription->emails_limit ?: 5000) : ($custom['sliders']['emails_per_month']['default'] ?? 25000) }},
            whatsapp_numbers: {{ $subscription ? ($subscription->whatsapp_limit ?: 1) : ($custom['sliders']['whatsapp_numbers']['default'] ?? 2) }},
            whatsapp_messages: {{ $custom['sliders']['whatsapp_messages']['default'] ?? 5000 }},

            include_email: {{ ($subscription && ($subscription->emails_limit ?? 0) == 0) ? 'false' : 'true' }},
            include_whatsapp: {{ ($subscription && ($subscription->whatsapp_limit ?? 0) == 0) ? 'false' : 'true' }},

            taxPercent: {{ $pricing['tax_percent'] ?? 18 }},

            get crmUsersCost() {
                let diff = Math.max(0, this.crm_users - this.current_crm_users);
                return diff * this.rates.crm_per_user;
            },
            get crmContactsCost() {
                let diff = Math.max(0, this.crm_contacts - this.current_crm_contacts);
                return (diff / 1000) * this.rates.crm_per_1k_contacts;
            },
            get emailsCost() {
                if (!this.include_email) return 0;
                let diff = Math.max(0, this.emails_per_month - this.current_emails_per_month);
                return (diff / 1000) * this.rates.email_per_1k;
            },
            get whatsappNumbersCost() {
                if (!this.include_whatsapp) return 0;
                let diff = Math.max(0, this.whatsapp_numbers - this.current_whatsapp_numbers);
                return diff * this.rates.whatsapp_per_number;
            },
            get whatsappMessagesCost() {
                if (!this.include_whatsapp) return 0;
                let diff = Math.max(0, this.whatsapp_messages - this.current_whatsapp_messages);
                return diff * this.rates.whatsapp_per_message;
            },

            get subtotal() {
                return Math.round(this.crmUsersCost + this.crmContactsCost + this.emailsCost + this.whatsappNumbersCost + this.whatsappMessagesCost);
            },
            get taxAmount() { return Math.round((this.subtotal * this.taxPercent) / 100); },
            get grandTotal() { return this.subtotal + this.taxAmount; },

            proceedToCheckout() {
                const params = new URLSearchParams();
                params.set('plan', 'custom');
                params.set('crm_users', this.crm_users);
                params.set('crm_contacts', this.crm_contacts);
                params.set('emails_per_month', this.include_email ? this.emails_per_month : 0);
                params.set('whatsapp_numbers', this.include_whatsapp ? this.whatsapp_numbers : 0);
                params.set('whatsapp_messages', this.include_whatsapp ? this.whatsapp_messages : 0);
                window.location.href = '{{ route('admin.billing.plans') }}?' + params.toString();
            }
         }">
        <div class="text-center mb-16">
            <span class="px-3 py-1 bg-brand/10 text-brand text-xs font-black uppercase tracking-widest rounded-full">Custom Plan Builder</span>
            <h2 class="text-3xl md:text-4xl uppercase text-slate-900 font-black font-['Outfit'] mt-4">Build Your Custom Plan</h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Drag the sliders to set quantities according to your needs. The price updates in real-time.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
            <!-- Left: Sliders Panel -->
            <div class="lg:col-span-2 space-y-6">

                {{-- CRM Sliders --}}
                <div class="border border-slate-200 rounded-sm bg-white p-6 shadow-sm space-y-6">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-sm bg-indigo-50 text-indigo-500 flex items-center justify-center text-sm border border-indigo-100/50">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="text-sm font-black text-slate-900 font-['Outfit'] uppercase tracking-wider">CRM & Team</span>
                    </div>

                    {{-- Team Members Slider --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-500">Team Members</span>
                            <div class="flex gap-2">
                                <template x-if="current_crm_users > 0">
                                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded animate-pulse" x-text="'Current limit: ' + current_crm_users"></span>
                                </template>
                                <span class="text-xs font-black text-brand bg-brand/5 px-2.5 py-1 rounded" x-text="crm_users + ' Users'"></span>
                            </div>
                        </div>
                        <input type="range" :min="current_crm_users || 1" max="100" step="1" x-model.number="crm_users" class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-brand">
                        <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                            <span x-text="(current_crm_users || 1) + ' User'"></span>
                            <span class="text-brand" x-text="crmUsersCost > 0 ? '+₹' + crmUsersCost.toLocaleString('en-IN') + '/mo' : '₹0'"></span>
                            <span>100 Users</span>
                        </div>
                    </div>

                    {{-- Contacts Slider --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-500">CRM Contacts</span>
                            <div class="flex gap-2">
                                <template x-if="current_crm_contacts > 0">
                                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded animate-pulse" x-text="'Current limit: ' + current_crm_contacts.toLocaleString('en-IN')"></span>
                                </template>
                                <span class="text-xs font-black text-brand bg-brand/5 px-2.5 py-1 rounded" x-text="crm_contacts.toLocaleString('en-IN') + ' Contacts'"></span>
                            </div>
                        </div>
                        <input type="range" :min="current_crm_contacts || 1000" max="500000" step="1000" x-model.number="crm_contacts" class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-brand">
                        <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                            <span x-text="(current_crm_contacts || 1000).toLocaleString('en-IN')"></span>
                            <span class="text-brand" x-text="crmContactsCost > 0 ? '+₹' + crmContactsCost.toLocaleString('en-IN') + '/mo' : '₹0'"></span>
                            <span>5,00,000</span>
                        </div>
                    </div>
                </div>

                {{-- Email Slider --}}
                <div class="border border-slate-200 rounded-sm bg-white p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-sm bg-blue-50 text-blue-500 flex items-center justify-center text-sm border border-blue-100/50">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <span class="text-sm font-black text-slate-900 font-['Outfit'] uppercase tracking-wider">Email Marketing</span>
                        </div>
                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="include_email" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                        </label>
                    </div>

                    <div :class="!include_email && 'opacity-40 pointer-events-none transition-all duration-300'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-500">Emails Per Month</span>
                            <div class="flex gap-2">
                                <template x-if="current_emails_per_month > 0">
                                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded animate-pulse" x-text="'Current limit: ' + current_emails_per_month.toLocaleString('en-IN')"></span>
                                </template>
                                <span class="text-xs font-black text-brand bg-brand/5 px-2.5 py-1 rounded" x-text="emails_per_month.toLocaleString('en-IN') + ' Emails/mo'"></span>
                            </div>
                        </div>
                        <input type="range" :min="current_emails_per_month || 5000" max="1000000" step="5000" x-model.number="emails_per_month" class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-brand" :disabled="!include_email">
                        <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                            <span x-text="(current_emails_per_month || 5000).toLocaleString('en-IN')"></span>
                            <span class="text-brand" x-text="emailsCost > 0 ? '+₹' + emailsCost.toLocaleString('en-IN') + '/mo' : '₹0'"></span>
                            <span>10,00,000</span>
                        </div>
                    </div>
                </div>

                {{-- WhatsApp Sliders --}}
                <div class="border border-slate-200 rounded-sm bg-white p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-sm bg-emerald-50 text-emerald-500 flex items-center justify-center text-sm border border-emerald-100/50">
                                <i class="fa-brands fa-whatsapp"></i>
                            </div>
                            <span class="text-sm font-black text-slate-900 font-['Outfit'] uppercase tracking-wider">WhatsApp Marketing</span>
                        </div>
                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="include_whatsapp" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                        </label>
                    </div>

                    <div :class="!include_whatsapp && 'opacity-40 pointer-events-none transition-all duration-300'" class="space-y-6">
                        {{-- WhatsApp Numbers --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-500">WhatsApp Numbers</span>
                                <div class="flex gap-2">
                                    <template x-if="current_whatsapp_numbers > 0">
                                        <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded animate-pulse" x-text="'Current limit: ' + current_whatsapp_numbers"></span>
                                    </template>
                                    <span class="text-xs font-black text-brand bg-brand/5 px-2.5 py-1 rounded" x-text="whatsapp_numbers + ' Numbers'"></span>
                                </div>
                            </div>
                            <input type="range" :min="current_whatsapp_numbers || 1" max="50" step="1" x-model.number="whatsapp_numbers" class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-brand" :disabled="!include_whatsapp">
                            <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                                <span x-text="current_whatsapp_numbers || 1"></span>
                                <span class="text-brand" x-text="whatsappNumbersCost > 0 ? '+₹' + whatsappNumbersCost.toLocaleString('en-IN') + '/mo' : '₹0'"></span>
                                <span>50</span>
                            </div>
                        </div>

                        {{-- WhatsApp Messages --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-500">WhatsApp Messages / Month</span>
                                <span class="text-xs font-black text-brand bg-brand/5 px-2.5 py-1 rounded" x-text="whatsapp_messages.toLocaleString('en-IN') + ' Messages/mo'"></span>
                            </div>
                            <input type="range" min="1000" max="500000" step="1000" x-model.number="whatsapp_messages" class="w-full h-1.5 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-brand" :disabled="!include_whatsapp">
                            <div class="flex justify-between mt-2 text-[9px] font-bold text-slate-400 uppercase">
                                <span>1,000</span>
                                <span class="text-brand">Billed by Meta</span>
                                <span>5,00,000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Price Summary Panel -->
            <div class="lg:col-span-1">
                <div class="bg-slate-900 text-white rounded-sm p-8 shadow-2xl sticky top-8">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Custom Plan Summary</h3>

                    {{-- Line Items --}}
                    <div class="space-y-3 mb-8 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">CRM Users (<span x-text="crm_users"></span>)</span>
                            <span class="font-bold" x-text="(current_crm_users > 0 ? 'Extra: ' : '') + '₹' + crmUsersCost.toLocaleString('en-IN')"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">CRM Contacts (<span x-text="crm_contacts.toLocaleString('en-IN')"></span>)</span>
                            <span class="font-bold" x-text="(current_crm_contacts > 0 ? 'Extra: ' : '') + '₹' + crmContactsCost.toLocaleString('en-IN')"></span>
                        </div>
                        <div class="flex justify-between" x-show="include_email">
                            <span class="text-slate-400">Emails (<span x-text="emails_per_month.toLocaleString('en-IN')"></span>/mo)</span>
                            <span class="font-bold" x-text="(current_emails_per_month > 0 ? 'Extra: ' : '') + '₹' + emailsCost.toLocaleString('en-IN')"></span>
                        </div>
                        <div class="flex justify-between" x-show="include_whatsapp">
                            <span class="text-slate-400">WhatsApp No. (<span x-text="whatsapp_numbers"></span>)</span>
                            <span class="font-bold" x-text="(current_whatsapp_numbers > 0 ? 'Extra: ' : '') + '₹' + whatsappNumbersCost.toLocaleString('en-IN')"></span>
                        </div>
                        <div class="flex justify-between" x-show="include_whatsapp">
                            <span class="text-slate-400">WhatsApp Msgs (<span x-text="whatsapp_messages.toLocaleString('en-IN')"></span>/mo)</span>
                            <span class="font-bold" x-text="whatsappMessagesCost > 0 ? '₹' + whatsappMessagesCost.toLocaleString('en-IN') : '₹0 (Meta Direct)'"></span>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="border-t border-slate-700 pt-4 space-y-2 mb-8 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal</span>
                            <span class="text-white font-bold" x-text="'₹' + subtotal.toLocaleString('en-IN')"></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>GST (<span x-text="taxPercent"></span>%)</span>
                            <span class="text-white font-bold" x-text="'₹' + taxAmount.toLocaleString('en-IN')"></span>
                        </div>
                    </div>

                    <div class="border-t border-slate-700 pt-6 mb-8">
                        <p class="text-[9px] font-black uppercase text-slate-500 tracking-widest mb-1">Grand Total / Month</p>
                        <h2 class="text-4xl font-black text-brand font-['Outfit']" x-text="'₹' + grandTotal.toLocaleString('en-IN')"></h2>
                    </div>

                    <button @click="proceedToCheckout()"
                            class="w-full py-4 bg-brand hover:bg-[#e05638] text-white text-[11px] font-black uppercase tracking-widest rounded-sm transition-all shadow-[0_8px_30px_rgb(255,107,74,0.25)] cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-bolt"></i> Proceed to Checkout
                    </button>

                    <p class="text-[9px] text-slate-500 text-center mt-4 uppercase tracking-wider">All Business plan features included</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature Comparison Table -->
<section class="py-24 bg-white">
    <div class="container">
        <div class="text-center mb-16">
            <span class="px-3 py-1 bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm">Feature Comparison</span>
            <h2 class="text-3xl md:text-4xl uppercase text-slate-900 font-black font-['Outfit'] mt-4">Compare All Features</h2>
            <p class="text-sm text-slate-500 font-medium mt-2">See what is included in each plan at a glance.</p>
        </div>

        @php $comparison = config('plans.comparison', []); @endphp

        <div class="overflow-x-auto border border-slate-200 rounded-sm shadow-sm">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-900 text-white text-xs font-black uppercase tracking-wider">
                        <th class="p-4 w-1/3">Feature</th>
                        <th class="p-4 text-center">Starter<br><span class="text-brand font-normal text-[10px]">₹{{ number_format(config('plans.plans.starter.price')) }}/mo</span></th>
                        <th class="p-4 text-center bg-brand/10">Growth<br><span class="text-brand font-normal text-[10px]">₹{{ number_format(config('plans.plans.growth.price')) }}/mo</span></th>
                        <th class="p-4 text-center">Business<br><span class="text-brand font-normal text-[10px]">₹{{ number_format(config('plans.plans.business.price')) }}/mo</span></th>
                        <th class="p-4 text-center">Custom<br><span class="text-brand font-normal text-[10px]">You Decide</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs">
                    @foreach($comparison as $sectionName => $features)
                        {{-- Section Header --}}
                        <tr class="bg-slate-50 font-black uppercase text-[10px] tracking-widest text-slate-400">
                            <td colspan="5" class="p-3">{{ $sectionName }}</td>
                        </tr>
                        @foreach($features as $featureName => $values)
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-4 font-bold text-slate-900">{{ $featureName }}</td>
                                @foreach($values as $idx => $val)
                                    <td class="p-4 text-center {{ $idx === 1 ? 'bg-brand/5' : '' }}">
                                        @if($val === true)
                                            <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                        @elseif($val === false)
                                            <span class="text-slate-300">&mdash;</span>
                                        @else
                                            <span class="{{ $val === 'Custom' ? 'text-brand font-bold' : 'font-medium text-slate-700' }}">{{ $val }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- CTA below table --}}
        <div class="text-center mt-12">
            <a href="#configurator" class="inline-flex items-center gap-2 bg-brand hover:bg-[#e05638] text-white px-8 py-4 rounded-sm font-bold text-sm uppercase tracking-wider transition-all duration-300">
                Build Custom Plan <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endsection
