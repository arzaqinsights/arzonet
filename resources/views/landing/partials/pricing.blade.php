{{-- PREMIUM INDUSTRIAL-MINIMALIST PRICING SUITE --}}
@php
    $plans = config('plans.plans', []);
    $addons = config('plans.addons', []);
@endphp

<section id="pricing" class="py-24 bg-white border-t border-surface-200"
         x-data="{
             contacts: 20000,
             emails: 100000,
             baseContacts: 20000,
             baseEmails: 100000,
             basePrice: 9999,
             
             get extraContacts() {
                 return Math.max(0, this.contacts - this.baseContacts);
             },
             get extraEmails() {
                 return Math.max(0, this.emails - this.baseEmails);
             },
             get finalPrice() {
                 // Base ₹9,999 + ₹500 per 10k additional contacts + ₹5000 per 50k additional emails
                 let contactCost = (this.extraContacts / 10000) * 500;
                 let emailCost = (this.extraEmails / 50000) * 5000;
                 return Math.round(this.basePrice + contactCost + emailCost);
             },
             get formattedFinalPrice() {
                 return '₹' + this.finalPrice.toLocaleString('en-IN');
             }
         }">
    <div class="container">
        
        {{-- Section Header --}}
        <div class="text-center mb-20">
            <span class="px-3 py-1 bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm">Subscription Infrastructure</span>
            <h2 class="text-4xl md:text-5xl uppercase text-black font-black mt-4 mb-4" style="font-family: 'Outfit', sans-serif;">
                Simple, High-Capacity <span class="text-brand">Plans.</span>
            </h2>
            <p class="text-lg text-gray-600 font-light max-w-2xl mx-auto">
                Select your operational capacity. Seamlessly upgrade as your audience and marketing volume grows.
            </p>
        </div>

        {{-- 4-Column Plans Bento Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 xl:gap-8 items-stretch mb-24">
            @foreach($plans as $key => $plan)
                @php
                    $isGrowth = $key === 'growth';
                    $isBusiness = $key === 'business';
                    $isEnterprise = $key === 'enterprise';
                @endphp
                <div class="relative bg-white border {{ $isGrowth ? 'border-brand shadow-xl' : 'border-surface-200 shadow-sm' }} rounded-sm flex flex-col justify-between p-6 transition-all duration-300 hover:shadow-md group">
                    
                    {{-- Most Popular Badge for Growth Plan --}}
                    @if($isGrowth)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-brand text-white text-[9px] font-black uppercase tracking-widest rounded-sm">
                            Most Popular
                        </div>
                    @endif

                    <div>
                        {{-- Plan Heading --}}
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-surface-900 tracking-wider" style="font-family: 'Outfit', sans-serif;">
                                {{ $plan['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium italic mt-1">{{ $plan['tagline'] }}</p>
                        </div>

                        {{-- Pricing Value --}}
                        @if($isBusiness)
                            <div class="flex items-baseline gap-1 pb-6 mb-6 border-b border-gray-100 relative z-10">
                                <span class="text-4xl font-black text-surface-900" style="font-family: 'Outfit', sans-serif;" x-text="formattedFinalPrice">
                                    {{ $plan['price'] }}
                                </span>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">/{{ $plan['period'] }}</span>
                            </div>
                        @else
                            <div class="flex items-baseline gap-1 pb-6 mb-6 border-b border-gray-100">
                                <span class="text-4xl font-black text-surface-900" style="font-family: 'Outfit', sans-serif;">
                                    {{ $plan['price'] }}
                                </span>
                                @if($plan['period'])
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">/{{ $plan['period'] }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Business Plan Slider Section (Dynamic Volume Adjustment) --}}
                        @if($isBusiness)
                            <div class="space-y-4 my-6 py-4 border-y border-gray-100 relative z-10 bg-surface-50/50 p-3 rounded-sm">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Adjust Custom Volumes</p>
                                
                                <!-- Contacts Slider -->
                                <div>
                                    <div class="flex justify-between text-[11px] font-bold text-surface-900 mb-1">
                                        <span class="font-medium text-gray-400">Contacts:</span>
                                        <span class="text-brand font-black" x-text="parseInt(contacts).toLocaleString('en-IN')"></span>
                                    </div>
                                    <input type="range" min="30000" max="500000" step="10000" x-model="contacts" 
                                           class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand">
                                    <div class="flex justify-between text-[8px] text-gray-400 mt-1 uppercase tracking-wider">
                                        <span>30k Base</span>
                                        <span>500k Max</span>
                                    </div>
                                </div>

                                <!-- Emails Slider -->
                                <div>
                                    <div class="flex justify-between text-[11px] font-bold text-surface-900 mb-1">
                                        <span class="font-medium text-gray-400">Emails/mo:</span>
                                        <span class="text-brand font-black" x-text="parseInt(emails).toLocaleString('en-IN')"></span>
                                    </div>
                                    <input type="range" min="140000" max="2500000" step="20000" x-model="emails" 
                                           class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand">
                                    <div class="flex justify-between text-[8px] text-gray-400 mt-1 uppercase tracking-wider">
                                        <span>140k Base</span>
                                        <span>2.5M Max</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Plan Quotas / Limits --}}
                        <div class="space-y-2 mb-8 bg-surface-50 p-4 rounded-sm border border-surface-200/50">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Plan Capacities</p>
                            @foreach($plan['limits'] as $limitKey => $limitVal)
                                <div class="flex items-center justify-between text-xs text-surface-900 font-bold">
                                    <span class="capitalize text-gray-400 font-medium">{{ $limitKey }}:</span>
                                    @if($isBusiness && $limitKey === 'contacts')
                                        <span class="text-brand font-black" x-text="parseInt(contacts).toLocaleString('en-IN') + ' Contacts'"></span>
                                    @elseif($isBusiness && $limitKey === 'emails')
                                        <span class="text-brand font-black" x-text="parseInt(emails).toLocaleString('en-IN') + ' Emails/mo'"></span>
                                    @else
                                        <span>{{ $limitVal }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Core Included Features list --}}
                        <div class="space-y-3 mb-8">
                            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-3">Included Capabilities</p>
                            @foreach($plan['features'] as $feature)
                                <div class="flex items-start gap-2.5 text-xs text-gray-600 font-light leading-snug">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 text-[14px]"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach

                            {{-- Show Excluded items to Starter plan to create upsell comparison --}}
                            @if(!empty($plan['not_included']))
                                <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mt-6 mb-3">Locked / Upgrades Required</p>
                                @foreach($plan['not_included'] as $exFeature)
                                    <div class="flex items-start gap-2.5 text-xs text-gray-400/80 font-light leading-snug line-through">
                                        <i class="fa-solid fa-circle-xmark text-gray-300 mt-0.5 text-[14px]"></i>
                                        <span>{{ $exFeature }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Dynamic Call to Action button --}}
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        @if($isEnterprise)
                            <a href="mailto:sales@arzonet.com?subject=Enterprise Inquiry" 
                               class="block w-full text-center px-4 py-3 bg-surface-900 hover:bg-black text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-sm">
                                Contact Sales Team
                            </a>
                        @elseif($isBusiness)
                            <a :href="'{{ auth()->check() ? route('admin.billing.plans') : route('register') }}?plan=business&contacts=' + contacts + '&emails=' + emails" 
                               class="block w-full text-center px-4 py-3 bg-surface-900 hover:bg-black text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-sm">
                                Deploy Custom Business Plan
                            </a>
                        @else
                            <a href="{{ auth()->check() ? route('admin.billing.plans') : route('register') }}" 
                               class="block w-full text-center px-4 py-3 {{ $isGrowth ? 'bg-brand hover:bg-[#e05638] text-white shadow-[0_4px_12px_rgba(255,107,74,0.2)]' : 'bg-white hover:bg-gray-50 border-2 border-surface-900 text-surface-900' }} text-[10px] font-black uppercase tracking-widest rounded-sm transition-all">
                                {{ $isGrowth ? 'Deploy Growth Plan' : 'Get Started Now' }}
                            </a>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>

        {{-- STRATEGIC SAAS ADD-ONS GRID --}}
        <div class="mt-32 pt-20 border-t border-surface-200 relative overflow-hidden bg-surface-50 p-8 rounded-sm">
            <div class="absolute top-0 right-0 w-64 h-64 bg-brand/5 rounded-full blur-[100px] pointer-events-none"></div>
            
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-12 relative z-10">
                <div>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-widest rounded-sm">Add-On Modules</span>
                    <h3 class="text-3xl uppercase text-black font-black mt-3 mb-2" style="font-family: 'Outfit', sans-serif;">
                        Custom Power <span class="text-brand">Add-Ons.</span>
                    </h3>
                    <p class="text-sm text-gray-500 font-light max-w-xl">
                        Optimize and customize your marketing capacity instantly. Pay only for the resources you actually consume.
                    </p>
                </div>
                <a href="{{ auth()->check() ? route('admin.billing.plans') : route('register') }}" 
                   class="inline-flex items-center justify-center px-5 py-3.5 bg-brand hover:bg-[#e05638] text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-md">
                    Configure Add-ons in billing &rarr;
                </a>
            </div>

            {{-- 3-Column Add-Ons Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                @foreach($addons as $addonKey => $addon)
                    @php
                        $iconMap = [
                            'extra_contacts' => 'fa-users-line text-brand',
                            'extra_emails' => 'fa-envelope-circle-check text-blue-500',
                            'dedicated_ip' => 'fa-server text-emerald-500',
                            'additional_whatsapp' => 'fa-whatsapp text-green-500 fa-brands',
                            'ai_content' => 'fa-wand-magic-sparkles text-purple-500',
                            'extra_team' => 'fa-user-plus text-indigo-500',
                            'premium_templates' => 'fa-layer-group text-amber-500',
                            'priority_support' => 'fa-headset text-pink-500',
                            'white_label' => 'fa-eye-slash text-stone-500',
                            'custom_domain' => 'fa-globe text-cyan-500',
                            'api_upgrade' => 'fa-terminal text-rose-500',
                            'managed_deliverability' => 'fa-shield-halved text-teal-500'
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
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</section>
