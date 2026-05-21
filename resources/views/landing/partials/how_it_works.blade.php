{{-- HOW IT WORKS --}}
<section class="py-24 bg-surface-50 border-y border-surface-200">
    <div class="container">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl uppercase text-black font-black mb-4 font-['Outfit']">Three steps to your <span class="text-brand">inbox.</span></h2>
            <p class="text-slate-500 font-light text-base max-w-xl mx-auto">Get up and running in minutes with our streamlined list uploading and delivery architecture.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-12 relative">
            <!-- Connecting Line (Desktop Only) -->
            <div class="hidden md:block absolute top-[4.5rem] left-0 w-full h-[2px] bg-slate-200 -z-10"></div>
            
            @foreach([
                ['01','Import Your List','Upload a CSV or Excel list. Our streaming generator handles 100k+ records chunk-by-chunk without server memory overload.'],
                ['02','Design & Dispatch','Select custom templates, match personalization tags, and shoot. The queue scheduler handles delivery throttling automatically.'],
                ['03','Monitor & Optimize','Watch bounces, opens, and link clicks roll in in real-time. Engagement scores are calculated automatically per contact.'],
            ] as $step)
            <div class="relative bg-white border border-slate-200 rounded p-8 text-center hover:-translate-y-2 transition-all duration-300 hover:border-brand hover:shadow-lg hover:shadow-brand/5 group">
                <div class="w-16 h-16 mx-auto bg-slate-950 text-white rounded-full flex items-center justify-center text-2xl font-black mb-6 shadow-md transition-all duration-300 group-hover:bg-brand group-hover:scale-105">
                    {{ $step[0] }}
                </div>
                <h3 class="text-xl font-black text-slate-900 mb-3 font-['Outfit']">{{ $step[1] }}</h3>
                <p class="text-slate-600 leading-relaxed font-light text-sm">{{ $step[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
