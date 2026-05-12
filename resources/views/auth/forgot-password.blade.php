@extends('layouts.auth')
@section('title', 'Forgot Password — Arzonet')
@section('heading', 'Reset your password')
@section('subheading')
    Or
    <a href="{{ route('login') }}" class="font-medium text-brand hover:text-[#e05638]">
        return to sign in
    </a>
@endsection

@section('content')
<div class="mt-6">
    <div class="text-sm text-gray-600 mb-6">
        Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-4 rounded-md border border-green-200">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" class="space-y-6">
        @csrf

        @if($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-md text-sm border border-red-200">
            {{ $errors->first() }}
        </div>
        @endif

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand hover:bg-[#e05638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition-colors">
                Email Password Reset Link
            </button>
        </div>
    </form>
</div>
@endsection
