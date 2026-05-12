@extends('layouts.auth')
@section('title', 'Register — Arzonet')
@section('heading', 'Create your account')
@section('subheading')
    Already have an account?
    <a href="{{ route('login') }}" class="font-medium text-brand hover:text-[#e05638]">
        Sign in here
    </a>
@endsection

@section('content')
<div class="mt-6" x-data="{
    step: 1,
    nextStep() { if(this.step < 3) this.step++ },
    prevStep() { if(this.step > 1) this.step-- }
}">
    <form action="{{ route('submit.register') }}" method="POST" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-md text-sm border border-red-200">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Progress Bar -->
        <div class="relative mb-8">
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                <div :style="'width: ' + ((step / 3) * 100) + '%'" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-brand transition-all duration-300"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 font-medium">
                <span :class="step >= 1 ? 'text-brand' : ''">Account</span>
                <span :class="step >= 2 ? 'text-brand' : ''">Business</span>
                <span :class="step >= 3 ? 'text-brand' : ''">Address</span>
            </div>
        </div>

        <!-- Step 1: Account Info -->
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0" style="display: none;">
            <div class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <div class="mt-1">
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" required value="{{ old('email', $email ?? '') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <div class="mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="button" @click="nextStep()" class="inline-flex justify-center py-3 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none transition-colors">
                    Next Step <i class="fa-solid fa-arrow-right ml-2 mt-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Business Info -->
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0" style="display: none;">
            <div class="space-y-5">
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <div class="mt-1">
                        <input id="phone_number" name="phone_number" type="tel" required value="{{ old('phone_number') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                    <div class="mt-1">
                        <input id="company_name" name="company_name" type="text" required value="{{ old('company_name') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div>
                    <label for="gstin" class="block text-sm font-medium text-gray-700">GSTIN (Optional)</label>
                    <div class="mt-1">
                        <input id="gstin" name="gstin" type="text" value="{{ old('gstin') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-between">
                <button type="button" @click="prevStep()" class="inline-flex justify-center py-3 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                    Back
                </button>
                <button type="button" @click="nextStep()" class="inline-flex justify-center py-3 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none transition-colors">
                    Next Step <i class="fa-solid fa-arrow-right ml-2 mt-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Address Info -->
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0" style="display: none;">
            <div class="space-y-5">
                <div>
                    <label for="address_street" class="block text-sm font-medium text-gray-700">Street Address</label>
                    <div class="mt-1">
                        <input id="address_street" name="address_street" type="text" required value="{{ old('address_street') }}"
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="address_city" class="block text-sm font-medium text-gray-700">City</label>
                        <div class="mt-1">
                            <input id="address_city" name="address_city" type="text" required value="{{ old('address_city') }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="address_state" class="block text-sm font-medium text-gray-700">State</label>
                        <div class="mt-1">
                            <input id="address_state" name="address_state" type="text" required value="{{ old('address_state') }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="address_zip" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                        <div class="mt-1">
                            <input id="address_zip" name="address_zip" type="text" required value="{{ old('address_zip') }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="address_country" class="block text-sm font-medium text-gray-700">Country</label>
                        <div class="mt-1">
                            <input id="address_country" name="address_country" type="text" required value="{{ old('address_country', 'India') }}"
                                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-between">
                <button type="button" @click="prevStep()" class="inline-flex justify-center py-3 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                    Back
                </button>
                <button type="submit" class="inline-flex justify-center py-3 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand hover:bg-[#e05638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition-colors">
                    Create Account
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
