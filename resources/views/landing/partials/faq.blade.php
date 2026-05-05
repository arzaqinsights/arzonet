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
