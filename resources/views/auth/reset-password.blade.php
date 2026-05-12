@extends('layouts.auth')
@section('title', 'Reset Password — Arzonet')
@section('heading', 'Create new password')
@section('subheading', 'Please enter your new password below.')

@section('content')
<div class="mt-6">
    <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        @if($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-md text-sm border border-red-200">
            {{ $errors->first() }}
        </div>
        @endif

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $request->email) }}" readonly
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm text-gray-500 sm:text-sm">
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
            <div class="mt-1">
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <div class="mt-1">
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand focus:border-brand sm:text-sm">
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand hover:bg-[#e05638] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand transition-colors">
                Reset Password
            </button>
        </div>
    </form>
</div>
@endsection
