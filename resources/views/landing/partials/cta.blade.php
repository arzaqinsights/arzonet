{{-- CTA --}}
<section class="py-32 container text-center">
    <div class="max-w-4xl mx-auto bg-slate-950 text-white rounded p-12 md:p-20 relative overflow-hidden shadow-2xl border border-slate-900">
        <!-- Decoration Overlays -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-brand rounded-full blur-[100px] opacity-40 pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-brand rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
        
        <h2 class="relative z-10 text-4xl md:text-5xl uppercase text-white font-black mb-6 font-['Outfit']">Ready to send at scale?</h2>
        <p class="relative z-10 text-base text-slate-400 mb-10 font-light max-w-2xl mx-auto">Join thousands of teams sending millions of emails reliably every month with Arzonet's enterprise infrastructure.</p>
        <a href="{{ auth()->check() ? route('admin.dashboard') : route('register') }}" class="relative z-10 inline-flex items-center gap-2 bg-brand hover:bg-[#e05638] text-white px-10 py-5 rounded font-bold text-sm uppercase tracking-wider transition-all duration-300 hover:-translate-y-1 shadow-[0_8px_30px_rgb(255,107,74,0.3)]">
            Start Free — No Card Needed <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>
