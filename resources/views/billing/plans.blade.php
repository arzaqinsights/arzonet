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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-surface-50 p-6 rounded-sm border border-surface-100 flex items-start gap-4">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-brand border border-surface-200 shrink-0">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-black mb-1">Instant Activation</h4>
                        <p class="text-[11px] text-surface-500 font-medium">Limits are updated immediately after payment via Cashfree.</p>
                    </div>
                </div>
                <div class="bg-surface-50 p-6 rounded-sm border border-surface-100 flex items-start gap-4">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-black border border-surface-200 shrink-0">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-black mb-1">Tax Compliant Invoices</h4>
                        <p class="text-[11px] text-surface-500 font-medium">Download GST-ready PDF invoices for your business records.</p>
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
