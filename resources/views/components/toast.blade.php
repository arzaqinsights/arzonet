@props(['type' => 'success', 'message' => ''])

@php
    $isSuccess = $type === 'success';
@endphp

<div x-data="{ show: false }" 
     x-init="setTimeout(() => show = true, 100); setTimeout(() => show = false, 5000)" 
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-4 opacity-0 scale-95"
     x-transition:enter-end="translate-y-0 opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-y-0 opacity-100 scale-100"
     x-transition:leave-end="translate-y-4 opacity-0 scale-95"
     class="fixed bottom-8 right-8 z-[200] flex items-center gap-4 bg-surface-900 text-white p-4 rounded-sm shadow-2xl border border-white/5 min-w-[320px] max-w-[420px]"
     x-cloak>
    
    {{-- Status Icon --}}
    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $isSuccess ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">
        @if($isSuccess)
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 pr-4">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] {{ $isSuccess ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $isSuccess ? 'Action Success' : 'System Error' }}
        </p>
        <p class="text-sm font-medium text-white/90 mt-0.5 leading-snug">
            {{ $message }}
        </p>
    </div>

    {{-- Close Button --}}
    <button @click="show = false" class="shrink-0 p-1 hover:text-white text-white/30 transition-colors cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    {{-- Progress Bar --}}
    <div class="absolute bottom-0 left-0 h-0.5 {{ $isSuccess ? 'bg-emerald-500' : 'bg-red-500' }} transition-all duration-[5000ms]"
         :style="show ? 'width: 0%' : 'width: 100%'"
         x-init="setTimeout(() => $el.style.width = '0%', 100); $el.style.width = '100%'">
    </div>
</div>
