@extends('layouts.landing')
@section('title', 'Contact Us — Arzonet')

@section('content')
<section class="py-24 container">
    <div class="max-w-3xl mx-auto">
    <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 mb-4" style="font-family:'Outfit',sans-serif;">Contact Us</h1>
        <p class="text-lg text-gray-500">We'd love to hear from you. Please reach out with any questions or support requests.</p>
    </div>
    
    <div class="bg-white border border-gray-200 rounded-xl p-8 mb-12 shadow-sm">
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h3 class="font-bold text-gray-900 mb-2">General Inquiries</h3>
                <p class="text-gray-500 mb-4">For general questions about Arzonet, our features, or pricing.</p>
                <div class="flex flex-col gap-2">
                    <a href="mailto:arzonetmail@gmail.com" class="font-semibold" style="color:var(--color-brand)">
                        <i class="fa-solid fa-envelope mr-2"></i>arzonetmail@gmail.com
                    </a>
                    <a href="tel:8090492602" class="font-semibold" style="color:var(--color-brand)">
                        <i class="fa-solid fa-phone mr-2"></i>+91 8090492602
                    </a>
                </div>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 mb-2">Technical Support</h3>
                <p class="text-gray-500 mb-4">Need help setting up your SMTP or fixing a deliverability issue?</p>
                <div class="flex flex-col gap-2">
                    <a href="mailto:arzonetmail@gmail.com" class="font-semibold" style="color:var(--color-brand)">
                        <i class="fa-solid fa-envelope mr-2"></i>arzonetmail@gmail.com
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-8 text-center border border-gray-200">
        <h3 class="font-bold text-gray-900 mb-2">Enterprise Sales</h3>
        <p class="text-gray-500 mb-4">Looking for a custom plan, dedicated IP warming, or AWS SES direct setup?</p>
        <a href="mailto:arzonetmail@gmail.com" class="btn btn-primary">Contact Sales Team</a>
    </div>
    </div>
</section>
@endsection
