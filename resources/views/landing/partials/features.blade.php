{{-- FEATURES --}}
<section id="features" class="py-24 container">
    <div class="mb-16">
        <h2 class="text-4xl md:text-5xl uppercase text-black font-bold mb-4">Everything you need to <span class="text-brand">send at scale.</span></h2>
        <p class="text-lg text-gray-600 max-w-2xl font-light">Enterprise-grade tools built into a simple, beautiful interface. Stop worrying about deliverability and start focusing on your message.</p>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach([
            ['fa-solid fa-file-csv','Bulk CSV Import','Upload 100k+ contacts via CSV with streaming processing. No timeouts, no memory crashes — ever.'],
            ['fa-solid fa-server','Multi-SMTP Routing','Round-robin across unlimited SMTP providers and AWS SES. Maximize throughput automatically.'],
            ['fa-solid fa-chart-line','Real-time Analytics','Track opens, clicks, bounces, and unsubscribes per campaign with per-recipient attribution.'],
            ['fa-solid fa-shield-halved','Bounce Protection','Hard bounces auto-blacklisted. Soft bounces retried intelligently. Your sender score stays clean.'],
            ['fa-solid fa-bullseye','Smart Segmentation','Filter by engagement score, status, signup source, and custom metadata fields.'],
            ['fa-solid fa-link','Link Tracking','Every link rewritten with unique tokens. See exactly who clicked what and when.'],
        ] as $f)
        <div class="border relative rounded-md overflow-hidden p-8 group bg-white">
            <i class="{{ $f[0] }} absolute top-4 right-4 text-7xl opacity-5"></i>
            <div class="w-14 h-14 rounded-md bg-surface-100 text-brand flex items-center justify-center text-2xl mb-6">
                <i class="{{ $f[0] }}"></i>
            </div>
            <h3 class="text-xl font-bold text-black mb-3">{{ $f[1] }}</h3>
            <p class="text-gray-600 leading-relaxed font-light">{{ $f[2] }}</p>
        </div>
        @endforeach
    </div>
</section>
