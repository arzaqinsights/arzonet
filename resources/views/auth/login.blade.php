@extends('layouts.auth')
@section('title', 'Sign In — Arzonet')
@section('heading', 'Sign in to your account')
@section('subheading')
    Or
    <a href="{{ route('register') }}" class="font-medium text-brand hover:text-[#e05638]">
        start your 14-day free trial
    </a>
@endsection

@section('content')
<div class="mt-6">
    <form action="{{ route('submit.login') }}" method="POST" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-md text-sm border border-red-200">
            {{ $errors->first() }}
        </div>
        @endif

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email ?? '') }}"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
            </div>
        </div>

        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <div class="mt-1 relative">
                <input id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm pr-10">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-brand transition-colors" @click="showPassword = !showPassword">
                    <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox"
                    class="h-4 w-4 text-brand focus:ring-brand border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-900">
                    Remember me
                </label>
            </div>

            <div class="text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-brand hover:text-[#e05638]">
                    Forgot your password?
                </a>
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand hover:bg-[#e05638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition-colors">
                Sign in
            </button>
        </div>
    </form>
</div>
@endsection
