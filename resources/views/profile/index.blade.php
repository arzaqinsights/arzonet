@extends('layouts.app')

@section('content')
<div class="p-6 md:p-10 max-w-6xl mx-auto">
    <div class="mb-12">
        <h1 class="text-3xl font-black text-black mb-2 font-['Outfit'] italic underline decoration-brand decoration-4 underline-offset-8">My Account & Plan</h1>
        <p class="text-surface-500 font-medium">Manage your personal details and track your platform usage.</p>
    </div>

    @if(session('success'))
        <div class="mb-8 p-4 bg-green-50 border border-green-100 text-green-600 text-sm font-bold rounded-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <!-- Usage Overview (Right Column on Desktop) -->
        <div class="lg:col-span-1 space-y-6 order-1 lg:order-2">
            <div class="bg-black text-white p-8 rounded-sm shadow-xl relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-brand rounded-full opacity-20 blur-2xl"></div>
                <h3 class="text-lg font-black mb-6 font-['Outfit'] border-b border-white/10 pb-4 tracking-widest uppercase">Plan Usage</h3>
                
                <!-- Contacts -->
                <div class="mb-8">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Uploaded Contacts</span>
                        <span class="text-sm font-black text-brand">{{ number_format($contactUsage->total) }} / {{ number_format($contactUsage->limit) }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full {{ $contactUsage->percent > 90 ? 'bg-red-500' : 'bg-brand' }} transition-all duration-1000" style="width: {{ min(100, $contactUsage->percent) }}%"></div>
                    </div>
                </div>

                <!-- Emails -->
                <div class="mb-10">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Monthly Emails</span>
                        <span class="text-sm font-black text-white">{{ number_format($emailUsage->total) }} / {{ number_format($emailUsage->limit) }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full {{ $emailUsage->percent > 90 ? 'bg-red-500' : 'bg-white' }} transition-all duration-1000" style="width: {{ min(100, $emailUsage->percent) }}%"></div>
                    </div>
                </div>

                <a href="{{ route('admin.billing.plans') }}" class="block w-full py-3 bg-brand text-white text-center text-xs font-black rounded-sm uppercase tracking-widest hover:bg-brand/90 transition-all shadow-lg shadow-brand/20 mb-3">
                    Upgrade Plan
                </a>
                <a href="{{ route('admin.billing.invoices.index') }}" class="block w-full py-3 bg-white/5 text-white/60 text-center text-[10px] font-black rounded-sm uppercase tracking-widest hover:bg-white/10 transition-all border border-white/10">
                    Billing History
                </a>
            </div>

            <div class="bg-white p-6 border border-surface-200 rounded-sm">
                <h4 class="text-xs font-black text-black uppercase tracking-widest mb-4">Current Subscription</h4>
                <div class="space-y-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-surface-400 font-medium">Plan Name</span>
                        <span class="font-bold text-black">{{ auth()->user()->subscription->plan_name ?? 'Free Plan' }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-surface-400 font-medium">Status</span>
                        <span class="font-bold text-green-600 uppercase">{{ auth()->user()->subscription->status ?? 'Active' }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-surface-400 font-medium">Renewal Date</span>
                        <span class="font-bold text-black">{{ (auth()->user()->subscription && auth()->user()->subscription->ends_at) ? auth()->user()->subscription->ends_at->format('M d, Y') : 'Lifetime' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form (Left Column on Desktop) -->
        <div class="lg:col-span-2 order-2 lg:order-1">
            <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')

                <!-- Personal Info -->
                <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm">
                    <h2 class="text-lg font-black text-black mb-6 font-['Outfit'] border-b border-surface-100 pb-4 italic">01. Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Full Name</label>
                            <input type="text" name="name" value="{{ $user->name }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Email Address</label>
                            <input type="email" name="email" value="{{ $user->email }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ $user->phone_number }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                    </div>
                </div>

                <!-- Company Info -->
                <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm">
                    <h2 class="text-lg font-black text-black mb-6 font-['Outfit'] border-b border-surface-100 pb-4 italic">02. Company Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Company Name</label>
                            <input type="text" name="company_name" value="{{ $user->company_name }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">GSTIN (Optional)</label>
                            <input type="text" name="gstin" value="{{ $user->gstin }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Street Address</label>
                            <input type="text" name="address_street" value="{{ $user->address_street }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">City</label>
                            <input type="text" name="address_city" value="{{ $user->address_city }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Zip Code</label>
                            <input type="text" name="address_zip" value="{{ $user->address_zip }}" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="bg-white p-8 border border-surface-200 rounded-sm shadow-sm">
                    <h2 class="text-lg font-black text-black mb-6 font-['Outfit'] border-b border-surface-100 pb-4 italic">03. Security</h2>
                    <p class="text-[10px] text-surface-400 mb-6 font-medium italic">Leave blank if you don't want to change your password.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">New Password</label>
                            <input type="password" name="password" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="w-full bg-surface-50 border-surface-200 rounded-sm focus:ring-brand focus:border-brand text-sm font-bold p-3">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-12 py-4 bg-black text-white font-black text-sm rounded-sm hover:bg-surface-800 transition-all shadow-lg shadow-black/10 uppercase tracking-widest">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
