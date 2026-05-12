@extends('layouts.app')

@section('content')
<div class="p-6 md:p-10 max-w-6xl mx-auto" x-data="{
    contacts: 5000,
    emails: 10000,
    baseContactPrice: {{ $pricing['contacts_base_price'] }},
    baseEmailPrice: {{ $pricing['emails_base_price'] }},
    taxPercent: {{ $pricing['tax_percent'] }},
    discounts: {{ json_encode($pricing['discounts']) }},
    
    get subtotal() {
        return ((this.contacts / 1000) * this.baseContactPrice) + ((this.emails / 1000) * this.baseEmailPrice);
    },
    
    get discountPercent() {
        let totalVolume = parseInt(this.contacts) + parseInt(this.emails);
        let pct = 0;
        this.discounts.forEach(d => {
            if (totalVolume >= d.min) pct = d.percent;
        });
        return pct;
    },
    
    get discountAmount() {
        return (this.subtotal * this.discountPercent) / 100;
    },
    
    get taxAmount() {
        return ((this.subtotal - this.discountAmount) * this.taxPercent) / 100;
    },
    
    get grandTotal() {
        return (this.subtotal - this.discountAmount) + this.taxAmount;
    }
}">
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-black text-black mb-3 font-['Outfit'] italic underline decoration-brand decoration-4 underline-offset-8">Choose Your Power Plan</h1>
        <p class="text-surface-500 font-medium text-lg">Scale your reach. Only pay for what you use.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-start">
        <!-- Configuration Area -->
        <div class="lg:col-span-3 space-y-12">
            
            <!-- Contacts Slider -->
            <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 px-4 py-1 bg-surface-100 text-[10px] font-black text-surface-400 uppercase">Step 01</div>
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-black font-['Outfit']">Target Contacts</h3>
                    <div class="px-4 py-2 bg-brand/10 text-brand rounded-sm font-black text-lg">
                        <span x-text="contacts.toLocaleString()"></span>
                    </div>
                </div>
                <input type="range" min="1000" max="500000" step="1000" x-model="contacts" class="w-full h-2 bg-surface-100 rounded-lg appearance-none cursor-pointer accent-brand">
                <div class="flex justify-between mt-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">
                    <span>1k Contacts</span>
                    <span>500k Contacts</span>
                </div>
            </div>

            <!-- Emails Slider -->
            <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 px-4 py-1 bg-surface-100 text-[10px] font-black text-surface-400 uppercase">Step 02</div>
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-black text-black font-['Outfit']">Monthly Email Volume</h3>
                    <div class="px-4 py-2 bg-black/10 text-black rounded-sm font-black text-lg">
                        <span x-text="emails.toLocaleString()"></span>
                    </div>
                </div>
                <input type="range" min="1000" max="1000000" step="5000" x-model="emails" class="w-full h-2 bg-surface-100 rounded-lg appearance-none cursor-pointer accent-black">
                <div class="flex justify-between mt-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">
                    <span>1k Emails</span>
                    <span>1M Emails</span>
                </div>
            </div>

            <!-- Comprehensive Features List -->
            <div class="bg-white border border-surface-200 rounded-sm shadow-sm overflow-hidden">
                <div class="bg-surface-50 border-b border-surface-200 px-8 py-5">
                    <h3 class="text-lg font-black text-black font-['Outfit'] flex items-center gap-2">
                        <i class="fa-solid fa-star text-brand"></i> All-In-One Platform Features
                    </h3>
                </div>
                <div class="p-8 space-y-6">
                    <!-- Feature 1 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center shrink-0 mt-1">
                            <i class="fa-solid fa-filter"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-black mb-1">Advanced Contact Filtering & Cleaning</h4>
                            <p class="text-[13px] text-surface-600 leading-relaxed">
                                Apni list ko fix aur filter karein — 1 din me <strong>upto 1 Lakh contacts</strong> filter karein. Automatically remove duplicates. Invalid, typos, aur suspicious emails alag se mark ho jayengi taaki bounce possibility zero ho.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center shrink-0 mt-1">
                            <i class="fa-solid fa-file-export"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-black mb-1">Smart Custom Exports</h4>
                            <p class="text-[13px] text-surface-600 leading-relaxed">
                                Har filter ke sath contacts ko export karein. City, sectors, ya kisi bhi custom field ki value ke hisab se list ko alag-alag hisson me export aur segment karein.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center shrink-0 mt-1">
                            <i class="fa-solid fa-paper-plane"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-black mb-1">Bulk, Transactional & Promotional Campaigns</h4>
                            <p class="text-[13px] text-surface-600 leading-relaxed">
                                Bulk mail, transactional alerts, ya promotional emails bhejein. Har campaign ki <strong>full analytics</strong> (open, click, bounce) real-time me track karein.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center shrink-0 mt-1">
                            <i class="fa-solid fa-user-tag"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-black mb-1">Deep Personalization</h4>
                            <p class="text-[13px] text-surface-600 leading-relaxed">
                                Har user ko bulk me personalize mail bhejein. Unke naam, company, ya kisi bhi custom data point ko email me dynamically inject karein.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-black/5 text-black flex items-center justify-center shrink-0 mt-1">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-black mb-1">Daily Use Ka One & Only Platform</h4>
                            <p class="text-[13px] text-surface-600 leading-relaxed">
                                Ek hi platform se apni poori email marketing, list hygiene, aur audience engagement ko daily manage karein.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Card -->
        <div class="lg:col-span-2">
            <div class="bg-black text-white p-10 rounded-sm shadow-2xl sticky top-24">
                <h3 class="text-2xl font-black mb-8 font-['Outfit'] border-b border-white/10 pb-6">Order Summary</h3>
                
                <div class="space-y-6 mb-10">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/60 font-medium">Platform License</span>
                        <span class="font-black italic">Free</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/60 font-medium">Capacity Add-ons</span>
                        <span class="font-black" x-text="'₹' + subtotal.toLocaleString()"></span>
                    </div>
                    <div class="flex items-center justify-between text-sm" x-show="discountAmount > 0">
                        <span class="text-brand font-bold italic">Volume Discount (<span x-text="discountPercent"></span>%)</span>
                        <span class="text-brand font-black" x-text="'- ₹' + discountAmount.toLocaleString()"></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-white/60 font-medium">GST (<span x-text="taxPercent"></span>%)</span>
                        <span class="font-black" x-text="'₹' + taxAmount.toLocaleString()"></span>
                    </div>
                </div>

                <div class="flex items-end justify-between border-t border-white/10 pt-8 mb-10">
                    <div>
                        <p class="text-[10px] font-black uppercase text-white/40 tracking-widest mb-1">Total Due</p>
                        <h2 class="text-5xl font-black font-['Outfit'] text-brand">₹<span x-text="grandTotal.toLocaleString()"></span></h2>
                    </div>
                </div>

                <form action="{{ route('admin.billing.purchase') }}" method="POST">
                    @csrf
                    <input type="hidden" name="contacts" :value="contacts">
                    <input type="hidden" name="emails" :value="emails">
                    <input type="hidden" name="amount" :value="grandTotal">
                    
                    <button type="submit" class="w-full py-5 bg-brand text-white font-black text-lg rounded-sm hover:scale-[1.02] active:scale-[0.98] transition-all shadow-[0_0_30px_rgba(255,107,0,0.3)] uppercase tracking-widest">
                        Proceed to Payment
                    </button>
                </form>

                <p class="mt-6 text-center text-[10px] text-white/30 font-medium uppercase tracking-widest">Secure Checkout via Cashfree</p>
            </div>
        </div>
    </div>
</div>
@endsection
