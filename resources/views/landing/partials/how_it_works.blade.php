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
