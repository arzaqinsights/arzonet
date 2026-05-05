@extends('layouts.landing')
@section('title', 'Privacy Policy — Arzonet')

@section('content')
<section class="py-24 max-w-3xl mx-auto px-6">
    <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 mb-4" style="font-family:'Outfit',sans-serif;">Privacy Policy</h1>
        <p class="text-gray-500">Last updated: {{ date('F j, Y') }}</p>
    </div>
    
    <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed space-y-6">
        <h2 class="text-xl font-bold text-gray-900">1. Information We Collect</h2>
        <p>We collect information that you provide directly to us when you register for an account, subscribe to our newsletter, or use our bulk email platform. This may include your name, email address, billing information, and the contact lists you upload to our platform.</p>

        <h2 class="text-xl font-bold text-gray-900">2. How We Use Your Information</h2>
        <p>We use the information we collect to operate, maintain, and improve our services. Specifically, your uploaded email lists are used solely for the purpose of sending your campaigns and tracking analytics on your behalf. We will never sell, rent, or share your contact lists with third parties.</p>

        <h2 class="text-xl font-bold text-gray-900">3. Data Security</h2>
        <p>We implement appropriate technical and organizational measures to protect your personal data and your recipients' data against unauthorized access, alteration, disclosure, or destruction. However, no internet transmission is ever fully secure.</p>

        <h2 class="text-xl font-bold text-gray-900">4. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at <a href="mailto:privacy@arzonet.com" style="color:var(--color-brand)">privacy@arzonet.com</a>.</p>
    </div>
</section>
@endsection
