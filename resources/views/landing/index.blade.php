@extends('layouts.landing')
@section('title', 'Arzonet — Enterprise Bulk Email Platform')

@section('content')
@include('landing.partials.hero')
@include('landing.partials.features')
{{-- HOW IT WORKS --}}
<section class="py-24 bg-surface-50 border-y border-surface-200">
    <div class="max-w-[1440px] mx-auto px-6 md:px-12">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl uppercase text-black font-bold mb-4">Three steps to your <span class="text-brand">inbox.</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-12 relative">
            <!-- Connecting Line (Desktop Only) -->
            <div class="hidden md:block absolute top-[4.5rem] left-0 w-full h-[2px] bg-gray-200 -z-10"></div>
            
            @foreach([
                ['01','Import Your List','Upload a CSV or paste emails. Our streaming engine handles 100k rows without breaking a sweat.'],
                ['02','Design & Send','Pick a template, map your columns, hit send. The queue handles rate limiting and retries automatically.'],
                ['03','Track Everything','Watch opens, clicks, bounces roll in live. Engagement scores update per contact automatically.'],
            ] as $step)
            <div class="relative bg-white border rounded-md p-8 text-center hover:-translate-y-2 transition-transform duration-300">
                <div class="w-20 h-20 mx-auto bg-surface-800 text-white rounded-md flex items-center justify-center text-3xl font-black mb-6 shadow-lg shadow-black/10">
                    {{ $step[0] }}
                </div>
                <h3 class="text-xl font-bold text-black mb-3">{{ $step[1] }}</h3>
                <p class="text-gray-600 leading-relaxed font-light">{{ $step[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PRICING --}}
@include('landing.partials.pricing')

{{-- FAQ --}}
<section id="faq" class="py-24 bg-surface-50 border-y border-surface-200">
    <div class="max-w-3xl mx-auto px-6 md:px-12">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl uppercase text-black font-bold mb-4">Common <span class="text-brand">questions.</span></h2>
        </div>
        <div x-data="{ open: null }" class="space-y-4">
            @foreach([
                ['Can I import a 100,000 row CSV without timeout?','Yes. Arzonet uses PHP Generators for streaming — rows are processed chunk-by-chunk in background jobs. Your browser never hangs.'],
                ['How does bounce handling work?','Hard bounces (permanent failures) are automatically blacklisted. Soft bounces are retried with exponential backoff. AWS SES webhooks keep your list clean in real time.'],
                ['Can I use multiple SMTP servers?','Absolutely. Add unlimited senders. The bulk mailer round-robins across all verified senders automatically to maximize throughput.'],
                ['What happens if I hit Gmail\'s rate limits?','The sending queue includes per-sender rate limiting. You can set emails-per-minute per sender. Excess jobs are delayed, not dropped.'],
                ['Is open tracking reliable?','Open tracking via pixel is available. We flag Gmail proxy opens (GoogleImageProxy UA) and Apple Mail Privacy opens to keep your analytics honest.'],
            ] as [$q,$a])
            <div class="border rounded-md bg-white overflow-hidden transition-all duration-300" x-data="{ id: '{{ $loop->index }}' }" :class="open === id ? 'border-brand shadow-md' : 'border-surface-200 hover:border-gray-300'">
                <button @click="open === id ? open = null : open = id" class="w-full text-left px-6 py-5 flex justify-between items-center gap-4 cursor-pointer focus:outline-none">
                    <span class="font-bold text-black text-lg" :class="open === id ? 'text-brand' : ''">{{ $q }}</span>
                    <div class="w-8 h-8 rounded-full bg-surface-100 flex items-center justify-center flex-shrink-0 transition-transform duration-300" :class="open === id ? 'rotate-180 bg-brand/10 text-brand' : 'text-gray-500'">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </button>
                <div x-show="open === id" x-collapse class="px-6 pb-6 text-base text-gray-600 leading-relaxed font-light">
                    {{ $a }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-32 max-w-[1440px] mx-auto px-6 md:px-12 text-center">
    <div class="max-w-4xl mx-auto bg-surface-900 rounded-md p-12 md:p-20 relative overflow-hidden shadow-2xl">
        <!-- Decoration Overlays -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-brand rounded-full blur-[100px] opacity-40 pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-brand rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
        
        <h2 class="relative z-10 text-4xl md:text-5xl uppercase text-white font-bold mb-6">Ready to send at scale?</h2>
        <p class="relative z-10 text-xl text-gray-400 mb-10 font-light max-w-2xl mx-auto">Join thousands of teams sending millions of emails reliably every month with Arzonet's enterprise infrastructure.</p>
        <a href="{{ auth()->check() ? route('admin.dashboard') : route('register') }}" class="relative z-10 inline-flex items-center gap-2 bg-brand hover:bg-[#e05638] text-white px-10 py-5 rounded-md font-bold text-lg transition-all hover:-translate-y-1 shadow-[0_8px_30px_rgb(255,107,74,0.3)]">
            Start Free — No Card Needed <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

@endsection
