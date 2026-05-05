@extends('layouts.landing')
@section('title', 'Terms & Conditions — Arzonet')

@section('content')
<section class="py-24 max-w-3xl mx-auto px-6">
    <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 mb-4" style="font-family:'Outfit',sans-serif;">Terms & Conditions</h1>
        <p class="text-gray-500">Last updated: {{ date('F j, Y') }}</p>
    </div>
    
    <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed space-y-6">
        <h2 class="text-xl font-bold text-gray-900">1. Acceptance of Terms</h2>
        <p>By accessing or using the Arzonet platform, you agree to be bound by these Terms and Conditions. If you disagree with any part of the terms, you may not access the service.</p>

        <h2 class="text-xl font-bold text-gray-900">2. Acceptable Use Policy (Anti-Spam)</h2>
        <p>Arzonet is strictly for sending solicited emails. You agree NOT to use the platform for sending unsolicited bulk email (spam). You must have explicit consent from all recipients on your contact lists. We reserve the right to immediately suspend or terminate accounts that violate our Anti-Spam policy or experience abnormally high bounce or complaint rates.</p>

        <h2 class="text-xl font-bold text-gray-900">3. Account Responsibilities</h2>
        <p>You are responsible for safeguarding the password that you use to access the service and for any activities or actions under your password. You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.</p>

        <h2 class="text-xl font-bold text-gray-900">4. Service Availability</h2>
        <p>While we strive for 99.9% uptime, we do not guarantee that the service will be uninterrupted, secure, or error-free. We may experience hardware, software, or other problems, resulting in interruptions, delays, or errors.</p>
    </div>
</section>
@endsection
