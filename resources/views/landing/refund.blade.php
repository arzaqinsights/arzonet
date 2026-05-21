@extends('layouts.landing')
@section('title', 'Refund Policy — Arzonet')

@section('content')
<section class="py-24 container">
    <div class="max-w-3xl mx-auto">
    <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 mb-4" style="font-family:'Outfit',sans-serif;">Refund Policy</h1>
        <p class="text-gray-500">Last updated: {{ date('F j, Y') }}</p>
    </div>
    
    <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed space-y-6">
        <h2 class="text-xl font-bold text-gray-900">1. General Refund Terms</h2>
        <p>Arzonet offers a free tier so you can evaluate our platform before committing to a paid plan. Because of this, we generally do not offer refunds for paid subscriptions unless specifically required by law.</p>

        <h2 class="text-xl font-bold text-gray-900">2. Subscription Cancellations</h2>
        <p>You can cancel your subscription at any time. When you cancel, you will continue to have access to the paid features until the end of your current billing cycle. We do not provide prorated refunds for mid-cycle cancellations.</p>

        <h2 class="text-xl font-bold text-gray-900">3. Exceptions</h2>
        <p>Refunds may be issued on a case-by-case basis at our sole discretion if there has been a major technical failure on our end that prevented you from using the service for an extended period of time.</p>

        <h2 class="text-xl font-bold text-gray-900">4. Account Terminations</h2>
        <p>If your account is suspended or terminated due to a violation of our Anti-Spam Policy or Acceptable Use Policy, you will not be entitled to any refund for unused time or credits.</p>
    </div>
    </div>
</section>
@endsection
