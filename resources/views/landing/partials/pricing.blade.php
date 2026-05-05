{{-- PRICING --}}
<section id="pricing" class="py-24 max-w-[1440px] mx-auto px-6 md:px-12">
    <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl uppercase text-black font-bold mb-4">Simple, transparent <span class="text-brand">pricing.</span></h2>
        <p class="text-lg text-gray-600 font-light">Start free. Scale as you grow. No hidden fees.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-8 items-center max-w-6xl mx-auto">
        {{-- Free --}}
        <div class="border rounded-md p-8 bg-white">
            <h3 class="text-xl font-bold text-black mb-2 uppercase">Free</h3>
            <div class="flex items-baseline gap-1 mb-6"><span class="text-5xl font-black text-black">₹0</span><span class="text-gray-500 font-medium">/mo</span></div>
            <p class="text-gray-600 mb-8 font-light h-12">Perfect for side projects and small lists.</p>
            <ul class="space-y-4 mb-8">
                @foreach(['10,000 emails/month','1 SMTP sender','Basic analytics','CSV import (10k rows)','Community support'] as $f)
                <li class="flex items-start gap-3 text-gray-600 font-light">
                    <i class="fa-solid fa-check text-brand mt-1"></i>
                    <span>{{ $f }}</span>
                </li>
                @endforeach
            </ul>
            <a href="{{ route('admin.dashboard') }}" class="block w-full text-center border-2 border-surface-200 hover:border-black text-black px-6 py-4 rounded-md font-bold transition-colors">Get Started Free</a>
        </div>
        {{-- Pro --}}
        <div class="border-2 border-brand rounded-md p-8 bg-white relative shadow-xl transform md:-translate-y-4">
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1.5 rounded-full text-xs font-bold text-white bg-brand uppercase tracking-wider">Most Popular</div>
            <h3 class="text-xl font-bold text-black mb-2 uppercase">Pro</h3>
            <div class="flex items-baseline gap-1 mb-6"><span class="text-5xl font-black text-brand">₹2,499</span><span class="text-gray-500 font-medium">/mo</span></div>
            <p class="text-gray-600 mb-8 font-light h-12">Everything you need to scale your email marketing.</p>
            <ul class="space-y-4 mb-8">
                @foreach(['500,000 emails/month','Unlimited SMTP senders','Advanced analytics + tracking','CSV import (unlimited)','Bounce & complaint webhooks','Priority email support'] as $f)
                <li class="flex items-start gap-3 text-gray-600 font-light">
                    <i class="fa-solid fa-check text-brand mt-1"></i>
                    <span>{{ $f }}</span>
                </li>
                @endforeach
            </ul>
            <a href="{{ route('admin.dashboard') }}" class="block w-full text-center bg-brand hover:bg-[#e05638] text-white px-6 py-4 rounded-md font-bold transition-colors shadow-[0_8px_20px_rgb(255,107,74,0.3)]">Start Pro Trial</a>
        </div>
        {{-- Enterprise --}}
        <div class="border rounded-md p-8 bg-white">
            <h3 class="text-xl font-bold text-black mb-2 uppercase">Enterprise</h3>
            <div class="flex items-baseline gap-1 mb-6"><span class="text-5xl font-black text-black">Custom</span></div>
            <p class="text-gray-600 mb-8 font-light h-12">Dedicated support and infrastructure for high volume.</p>
            <ul class="space-y-4 mb-8">
                @foreach(['Unlimited emails','Dedicated IP warming','SLA & uptime guarantee','Custom integrations','AWS SES direct setup','Dedicated account manager'] as $f)
                <li class="flex items-start gap-3 text-gray-600 font-light">
                    <i class="fa-solid fa-check text-brand mt-1"></i>
                    <span>{{ $f }}</span>
                </li>
                @endforeach
            </ul>
            <a href="mailto:hello@arzonet.com" class="block w-full text-center border-2 border-surface-200 hover:border-black text-black px-6 py-4 rounded-md font-bold transition-colors">Contact Sales</a>
        </div>
    </div>
</section>
