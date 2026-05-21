@extends('layouts.app')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto">
    <!-- State A: Checkout / Order Confirmation Screen -->
    @if($checkout)
        <div class="mb-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-6 border-b border-surface-100">
            <div>
                <a href="{{ route('pricing') }}" class="inline-flex items-center gap-2 text-xs font-bold text-surface-500 hover:text-brand transition-colors mb-2">
                    <i class="fa-solid fa-arrow-left"></i> Back to Pricing
                </a>
                <h1 class="text-3xl font-black text-black font-['Outfit'] tracking-tight">Confirm Your Plan</h1>
                <p class="text-surface-500 font-medium text-sm mt-1">Please check your plan details before proceeding to payment.</p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-8 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold rounded flex items-center gap-3">
                <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            <!-- Left Config Details Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Selected Plan -->
                <div class="bg-white border border-surface-200 rounded p-6 shadow-sm">
                    <span class="text-[10px] font-black uppercase text-surface-400 tracking-wider">Selected Plan</span>
                    <h3 class="text-xl font-black text-black font-['Outfit'] mt-1">{{ strtoupper($planName) }} PLAN</h3>
                    <p class="text-xs text-surface-500 mt-0.5">CRM + Email Marketing + WhatsApp — all included.</p>
                </div>

                <!-- Plan Limits -->
                <div class="bg-white border border-surface-200 rounded p-6 shadow-sm space-y-4">
                    <span class="text-[10px] font-black uppercase text-surface-400 tracking-wider">Your Plan Limits</span>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-surface-100 bg-surface-50/50 p-4 rounded flex items-start gap-3">
                            <i class="fa-solid fa-users text-brand mt-1"></i>
                            <div>
                                <h4 class="text-sm font-black text-black">Contact CRM</h4>
                                <p class="text-[11px] text-surface-500 font-medium mt-1">Team Members: <span class="font-bold text-slate-800">{{ $limits['crm_users'] ?? 0 }}</span></p>
                                <p class="text-[11px] text-surface-500 font-medium">Contacts: <span class="font-bold text-slate-800">{{ number_format($limits['crm_contacts'] ?? 0) }}</span></p>
                            </div>
                        </div>

                        <div class="border border-surface-100 bg-surface-50/50 p-4 rounded flex items-start gap-3">
                            <i class="fa-solid fa-envelope text-brand mt-1"></i>
                            <div>
                                <h4 class="text-sm font-black text-black">Email Marketing</h4>
                                <p class="text-[11px] text-surface-500 font-medium mt-1">Emails/Month: <span class="font-bold text-slate-800">{{ number_format($limits['emails_per_month'] ?? 0) }}</span></p>
                            </div>
                        </div>

                        <div class="border border-surface-100 bg-surface-50/50 p-4 rounded flex items-start gap-3">
                            <i class="fa-brands fa-whatsapp text-brand mt-1"></i>
                            <div>
                                <h4 class="text-sm font-black text-black">WhatsApp Marketing</h4>
                                <p class="text-[11px] text-surface-500 font-medium mt-1">Numbers: <span class="font-bold text-slate-800">{{ $limits['whatsapp_numbers'] ?? 0 }}</span></p>
                                <p class="text-[11px] text-surface-500 font-medium">Messages/Month: <span class="font-bold text-slate-800">{{ number_format($limits['whatsapp_messages'] ?? 0) }}</span> <span class="text-surface-400 font-normal">(Billed by Meta directly)</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right checkout card -->
            <div class="lg:col-span-1">
                <div class="bg-black text-white p-8 rounded shadow-2xl border border-neutral-900">
                    <h3 class="text-xl font-black mb-6 font-['Outfit'] border-b border-white/10 pb-4 tracking-tight">Order Receipt</h3>
                    
                    <div class="space-y-4 mb-8 text-xs font-semibold">
                        <div class="flex items-center justify-between text-white/60">
                            <span>{{ ucfirst($planKey) }} Plan Price</span>
                            <span class="text-white">₹{{ number_format($details['base_price']) }}</span>
                        </div>

                        @if($details['extra_price'] > 0)
                            <div class="flex items-center justify-between text-white/60">
                                <span>Extra Capacity</span>
                                <span class="text-white">₹{{ number_format($details['extra_price']) }}</span>
                            </div>
                        @endif

                        <div class="border-t border-white/10 pt-4 flex items-center justify-between text-white/60">
                            <span>Subtotal</span>
                            <span class="text-white">₹{{ number_format($details['subtotal']) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-white/60">
                            <span>GST ({{ $pricing['tax_percent'] ?? 18 }}%)</span>
                            <span class="text-white">₹{{ number_format($details['tax_amount'], 2) }}</span>
                        </div>
                    </div>

                    <div class="border-t border-white/10 pt-6 mb-8 flex items-end justify-between">
                        <div>
                            <p class="text-[9px] font-black uppercase text-white/40 tracking-widest mb-1">Grand Total</p>
                            <h2 class="text-4xl font-black font-['Outfit'] text-brand">₹{{ number_format($details['grand_total']) }}</h2>
                        </div>
                    </div>

                    <form action="{{ route('admin.billing.purchase') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $planKey }}">
                        <input type="hidden" name="crm_users" value="{{ $limits['crm_users'] ?? 0 }}">
                        <input type="hidden" name="crm_contacts" value="{{ $limits['crm_contacts'] ?? 0 }}">
                        <input type="hidden" name="emails_per_month" value="{{ $limits['emails_per_month'] ?? 0 }}">
                        <input type="hidden" name="whatsapp_numbers" value="{{ $limits['whatsapp_numbers'] ?? 0 }}">
                        <input type="hidden" name="whatsapp_messages" value="{{ $limits['whatsapp_messages'] ?? 0 }}">

                        <button type="submit" class="w-full py-4 bg-brand text-white font-black text-sm rounded hover:bg-brand/90 transition-all uppercase tracking-widest active:scale-[0.98]">
                            Proceed to Payment
                        </button>
                    </form>

                    <p class="mt-4 text-[9px] text-white/30 text-center uppercase tracking-widest flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-lock"></i> Encrypted Checkout via Cashfree
                    </p>
                </div>
            </div>
        </div>

    <!-- State B: Active Subscription Details Dashboard -->
    @else
        <div class="mb-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pb-6 border-b border-surface-100">
            <div>
                <h1 class="text-3xl font-black text-black font-['Outfit'] tracking-tight">Active Subscription</h1>
                <p class="text-surface-500 font-medium text-sm mt-1">Review your current activated features, limits, and usage progress.</p>
            </div>
            
            <a href="{{ route('pricing') }}" class="inline-flex items-center gap-2 bg-brand hover:bg-[#e05638] text-white px-6 py-3 rounded font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:-translate-y-0.5 shadow-lg shadow-brand/10">
                Upgrade / Change Plan <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        @if(session('success'))
            <div class="mb-8 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(!$subscription)
            <div class="border border-dashed border-surface-200 rounded p-12 text-center bg-white">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-surface-400">
                    <i class="fa-solid fa-box-open text-xl"></i>
                </div>
                <h3 class="text-lg font-black text-black font-['Outfit']">No Active Subscription Found</h3>
                <p class="text-sm text-surface-500 mt-1 max-w-md mx-auto">Please choose one of our flexible plans to activate email campaigns, CRM features, and WhatsApp channels.</p>
                <a href="{{ route('pricing') }}" class="inline-block mt-6 px-6 py-3 bg-slate-900 text-white font-bold text-xs uppercase tracking-widest hover:bg-black rounded transition-all">
                    Choose a plan
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
                <!-- Current Plan Card (1 Column) -->
                <div class="lg:col-span-1 bg-black text-white p-8 rounded shadow-2xl relative overflow-hidden border border-neutral-900">
                    <!-- Glow background design -->
                    <div class="absolute -right-16 -top-16 w-36 h-36 bg-brand/10 rounded-full blur-3xl"></div>
                    
                    <span class="text-[9px] font-black uppercase text-white/40 tracking-widest">Active Plan</span>
                    <h3 class="text-2xl font-black text-white mt-1 font-['Outfit'] uppercase tracking-tight">{{ $subscription->plan_name }}</h3>
                    
                    <div class="mt-6 flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">{{ strtoupper($subscription->status) }}</span>
                    </div>

                    <div class="border-t border-white/10 mt-6 pt-6 space-y-3.5 text-xs text-white/70">
                        <div class="flex justify-between">
                            <span>Billing Cycle</span>
                            <span class="font-bold text-white">Monthly</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Starts At</span>
                            <span class="font-bold text-white">{{ $subscription->starts_at ? $subscription->starts_at->format('M d, Y') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Renews/Expires At</span>
                            <span class="font-bold text-white">{{ $subscription->ends_at ? $subscription->ends_at->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Upgrade Callout -->
                    <div class="mt-8 pt-6 border-t border-white/10">
                        <p class="text-[10px] text-white/50 leading-relaxed">
                            Need more volume or additional modules? Update your subscription limits instantly in the live builder.
                        </p>
                        <a href="{{ route('pricing') }}" class="block mt-4 w-full text-center py-3 bg-brand hover:bg-[#e05638] text-white text-[11px] font-black uppercase tracking-widest rounded transition-all">
                            Modify Limits / Modules
                        </a>
                    </div>
                </div>

                <!-- Usage Analytics Progress (2 Columns) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Configured Modules -->
                    <div class="bg-white border border-surface-200 rounded p-6 shadow-sm">
                        <h4 class="text-xs font-black uppercase text-surface-400 tracking-widest mb-4">Enabled Capabilities</h4>
                        
                        <div class="flex flex-wrap gap-2.5">
                            @php
                                $activeModules = (array) $subscription->selected_modules;
                            @endphp
                            
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold {{ in_array('crm', $activeModules) ? 'bg-brand/10 text-brand' : 'bg-surface-50 text-surface-300 line-through' }}">
                                <i class="fa-solid fa-users text-[10px]"></i> Contact CRM & Team
                            </span>
                            
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold {{ in_array('email', $activeModules) ? 'bg-brand/10 text-brand' : 'bg-surface-50 text-surface-300 line-through' }}">
                                <i class="fa-solid fa-envelope text-[10px]"></i> Email Campaigns
                            </span>
                            
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold {{ in_array('whatsapp', $activeModules) ? 'bg-brand/10 text-brand' : 'bg-surface-50 text-surface-300 line-through' }}">
                                <i class="fa-solid fa-whatsapp text-[10px]"></i> WhatsApp Business
                            </span>
                        </div>
                    </div>

                    <!-- Usage meters -->
                    <div class="bg-white border border-surface-200 rounded p-6 shadow-sm space-y-6">
                        <h4 class="text-xs font-black uppercase text-surface-400 tracking-widest mb-2">Usage Quota Allocation</h4>

                        <!-- CRM Contacts Usage (only if CRM module is selected) -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-surface-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-address-book text-surface-400"></i> CRM Contacts Limit
                                </span>
                                @if(in_array('crm', $activeModules))
                                    <span>{{ number_format($contactsCount) }} / {{ number_format($subscription->contacts_limit) }}</span>
                                @else
                                    <span class="text-surface-400">Module Disabled</span>
                                @endif
                            </div>
                            
                            @if(in_array('crm', $activeModules))
                                @php
                                    $contactsPct = $subscription->contacts_limit > 0 ? min(100, ($contactsCount / $subscription->contacts_limit) * 100) : 0;
                                @endphp
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: {{ $contactsPct }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1.5 text-[9px] font-bold text-surface-400 uppercase">
                                    <span>{{ round($contactsPct) }}% consumed</span>
                                    <span>{{ number_format(max(0, $subscription->contacts_limit - $contactsCount)) }} remaining</span>
                                </div>
                            @else
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden opacity-50">
                                    <div class="h-full bg-surface-300 rounded-full" style="width: 0%"></div>
                                </div>
                            @endif
                        </div>

                        <!-- Monthly Email Usage (only if Email module is selected) -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-surface-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-paper-plane text-surface-400"></i> Monthly Email Sent Quota
                                </span>
                                @if(in_array('email', $activeModules))
                                    <span>{{ number_format($emailsCount) }} / {{ number_format($subscription->emails_limit) }}</span>
                                @else
                                    <span class="text-surface-400">Module Disabled</span>
                                @endif
                            </div>
                            
                            @if(in_array('email', $activeModules))
                                @php
                                    $emailsPct = $subscription->emails_limit > 0 ? min(100, ($emailsCount / $subscription->emails_limit) * 100) : 0;
                                @endphp
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: {{ $emailsPct }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1.5 text-[9px] font-bold text-surface-400 uppercase">
                                    <span>{{ round($emailsPct) }}% consumed</span>
                                    <span>{{ number_format(max(0, $subscription->emails_limit - $emailsCount)) }} remaining</span>
                                </div>
                            @else
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden opacity-50">
                                    <div class="h-full bg-surface-300 rounded-full" style="width: 0%"></div>
                                </div>
                            @endif
                        </div>

                        <!-- WhatsApp Line capacity (only if WhatsApp module is selected) -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-surface-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-surface-400"></i> Connected WhatsApp Lines
                                </span>
                                @if(in_array('whatsapp', $activeModules))
                                    <span>{{ $whatsappCount }} / {{ $subscription->whatsapp_limit ?? 1 }}</span>
                                @else
                                    <span class="text-surface-400">Module Disabled</span>
                                @endif
                            </div>
                            
                            @if(in_array('whatsapp', $activeModules))
                                @php
                                    $waLimit = $subscription->whatsapp_limit ?? 1;
                                    $waPct = $waLimit > 0 ? min(100, ($whatsappCount / $waLimit) * 100) : 0;
                                @endphp
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: {{ $waPct }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1.5 text-[9px] font-bold text-surface-400 uppercase">
                                    <span>{{ round($waPct) }}% consumed</span>
                                    <span>{{ max(0, $waLimit - $whatsappCount) }} channels available</span>
                                </div>
                            @else
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden opacity-50">
                                    <div class="h-full bg-surface-300 rounded-full" style="width: 0%"></div>
                                </div>
                            @endif
                        </div>

                        <!-- Team Seats capacity (only if CRM module is selected) -->
                        <div>
                            <div class="flex items-center justify-between text-xs font-bold text-surface-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-users-gear text-surface-400"></i> Team Seat Limits
                                </span>
                                @if(in_array('crm', $activeModules))
                                    <span>{{ $teamCount }} / {{ $subscription->team_limit ?? 3 }}</span>
                                @else
                                    <span class="text-surface-400">Module Disabled</span>
                                @endif
                            </div>
                            
                            @if(in_array('crm', $activeModules))
                                @php
                                    $teamLimitVal = $subscription->team_limit ?? 3;
                                    $teamPct = $teamLimitVal > 0 ? min(100, ($teamCount / $teamLimitVal) * 100) : 0;
                                @endphp
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: {{ $teamPct }}%"></div>
                                </div>
                                <div class="flex justify-between mt-1.5 text-[9px] font-bold text-surface-400 uppercase">
                                    <span>{{ round($teamPct) }}% consumed</span>
                                    <span>{{ max(0, $teamLimitVal - $teamCount) }} seats available</span>
                                </div>
                            @else
                                <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden opacity-50">
                                    <div class="h-full bg-surface-300 rounded-full" style="width: 0%"></div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
