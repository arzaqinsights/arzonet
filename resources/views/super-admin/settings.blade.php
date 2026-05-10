@extends('layouts.super-admin')

@section('content')
<div class="p-6 md:p-10 max-w-4xl">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-black mb-2 font-['Outfit']">Global Pricing Settings</h1>
            <p class="text-surface-500 font-medium">Configure base rates and volume discounts for all users.</p>
        </div>
        <a href="{{ route('admin.super.dashboard') }}" class="text-xs font-black uppercase text-surface-400 hover:text-black transition-all flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-600 text-sm font-bold rounded-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.super.settings.update') }}" method="POST" class="space-y-8" x-data="{ 
        discounts: {{ json_encode($pricing['discounts'] ?? []) }} 
    }">
        @csrf
        
        <!-- Base Rates -->
        <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm">
            <h2 class="text-lg font-black text-black mb-6 font-['Outfit'] border-b border-surface-100 pb-4 italic">01. Base Rates</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Price per 1000 Contacts (₹)</label>
                    <input type="number" name="contacts_base_price" value="{{ $pricing['contacts_base_price'] }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3" required>
                    <p class="mt-2 text-[10px] text-surface-400 font-medium italic">Example: 200 means ₹200 for every 1000 contacts.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Price per 1000 Emails (₹)</label>
                    <input type="number" name="emails_base_price" value="{{ $pricing['emails_base_price'] }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3" required>
                    <p class="mt-2 text-[10px] text-surface-400 font-medium italic">Example: 100 means ₹100 for every 1000 emails sent.</p>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-surface-100">
                <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Applicable Tax (GST %)</label>
                <div class="relative w-32">
                    <input type="number" name="tax_percent" value="{{ $pricing['tax_percent'] }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3 pr-8" required>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-surface-400 font-bold">%</span>
                </div>
            </div>
        </div>

        <!-- Volume Discounts -->
        <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm">
            <h2 class="text-lg font-black text-black mb-6 font-['Outfit'] border-b border-surface-100 pb-4 italic">02. Volume Discounts</h2>
            
            <div class="space-y-4 mb-6">
                <template x-for="(discount, index) in discounts" :key="index">
                    <div class="flex items-center gap-4 bg-surface-50 p-4 rounded-sm border border-surface-100">
                        <div class="flex-1">
                            <label class="block text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Min. Units (Total Volume)</label>
                            <input type="number" :name="'discounts['+index+'][min]'" x-model="discount.min" class="w-full bg-white border-surface-200 rounded-sm text-xs font-bold p-2">
                        </div>
                        <div class="w-24">
                            <label class="block text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Discount %</label>
                            <input type="number" :name="'discounts['+index+'][percent]'" x-model="discount.percent" class="w-full bg-white border-surface-200 rounded-sm text-xs font-bold p-2">
                        </div>
                        <button type="button" @click="discounts.splice(index, 1)" class="mt-4 text-red-500 hover:text-red-700 transition-all">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </template>
            </div>

            <button type="button" @click="discounts.push({ min: 0, percent: 0 })" class="text-[10px] font-black uppercase text-brand hover:underline flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Add Discount Tier
            </button>
        </div>

        <div class="flex justify-end pt-6 border-t border-surface-100">
            <button type="submit" class="px-10 py-4 bg-brand text-white font-black text-sm rounded-sm hover:bg-brand/90 transition-all shadow-lg shadow-brand/20 uppercase tracking-widest">
                Save Pricing Rules
            </button>
        </div>
    </form>
</div>
@endsection
