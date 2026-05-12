@extends('layouts.auth')
@section('title', 'Verify Email — Arzonet')
@section('heading', 'Verify your email')
@section('subheading', 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?')

@section('content')
<div class="mt-6">
    <div class="text-sm text-gray-600 mb-6">
        If you didn't receive the email, we will gladly send you another.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-4 rounded-md border border-green-200">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand hover:text-[#e05638] focus:outline-none">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none">
                Log Out
            </button>
        </form>
    </div>
</div>
@endsection
