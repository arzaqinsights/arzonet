@props(['label', 'model', 'options'])

<div x-data="{ open: false }" class="relative" @click.away="open = false">
    <div @click="open = !open"
        class="flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-sm border border-gray-200 hover:border-gray-300 transition-all cursor-pointer min-w-[100px]">
        <span class="text-[13px] text-surface-800 tracking-widest">{{ $label }}</span>
        
        <div class="flex items-center gap-1.5">
            <template x-if="{{ $model }}.length > 0">
                <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
            </template>
            <svg class="w-3 h-3 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <div x-show="open" style="display: none;"
        class="absolute z-[100] top-full mt-1 left-0 w-48 bg-white border border-gray-200 rounded-sm shadow-xl p-1 text-left">
        <div class="max-h-48 overflow-y-auto space-y-0.5 p-1 custom-scrollbar">
            <label class="flex items-center gap-2 text-[10px] font-bold text-gray-700 cursor-pointer hover:bg-gray-50 p-1.5 rounded-sm transition-colors w-full">
                <input type="checkbox" :checked="{{ $model }}.length === 0" 
                    @change="if($event.target.checked) { {{ $model }} = []; fetchEmails(); }" 
                    class="rounded-sm border-gray-300 text-brand focus:ring-brand w-3 h-3 cursor-pointer">
                All
            </label>
            @foreach($options as $val => $text)
            <label class="flex items-center gap-2 text-[10px] font-bold text-gray-700 cursor-pointer hover:bg-gray-50 p-1.5 rounded-sm transition-colors w-full">
                <input type="checkbox" x-model="{{ $model }}" value="{{ $val }}" 
                    @change="fetchEmails()" 
                    class="rounded-sm border-gray-300 text-brand focus:ring-brand w-3 h-3 cursor-pointer">
                {{ $text }}
            </label>
            @endforeach
        </div>
    </div>
</div>
