@extends('layouts.app')
@section('title', 'Signup Forms')
@section('heading', 'Signup Forms')

@section('header-actions')
    <a href="{{ route('admin.signup-forms.create') }}"
        class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
        New Form
    </a>
@endsection

@section('content')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formsIndex', () => ({
            copyToClipboard(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied to clipboard!', type: 'success' } }));
                    }).catch(() => {
                        this.fallbackCopy(text);
                    });
                } else {
                    this.fallbackCopy(text);
                }
            },
            fallbackCopy(text) {
                const el = document.createElement('textarea');
                el.value = text;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                const selected = document.getSelection().rangeCount > 0 ? document.getSelection().getRangeAt(0) : false;
                el.select();
                try {
                    document.execCommand('copy');
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copied to clipboard!', type: 'success' } }));
                } catch (err) {
                    console.error('Fallback copy failed', err);
                }
                document.body.removeChild(el);
                if (selected) {
                    document.getSelection().removeAllRanges();
                    document.getSelection().addRange(selected);
                }
            },
            modalOpen: false,
            selectedForm: null,
            widgetType: 'popup',
            widgetTrigger: 'exit-intent',
            widgetDelay: 3000,
            widgetScroll: 50,
            
            openWidgetModal(form) {
                this.selectedForm = form;
                this.widgetType = 'popup';
                this.widgetTrigger = 'exit-intent';
                this.widgetDelay = 3000;
                this.widgetScroll = 50;
                this.modalOpen = true;
            },
            getGeneratedCode() {
                if (!this.selectedForm) return '';
                const url = '{{ url('/forms') }}/' + this.selectedForm.token + '/widget.js';
                
                if (this.widgetType === 'inline') {
                    return `<!-- 1. Form Placeholder -->\n<div id="arzonet-form-${this.selectedForm.token}"></div>\n\n<!-- 2. Form Script -->\n<script src="${url}" data-type="inline" async defer><\/script>`;
                }
                
                let attrs = `data-type="${this.widgetType}" data-trigger="${this.widgetTrigger}"`;
                if (this.widgetTrigger === 'delay') {
                    attrs += ` data-delay="${this.widgetDelay}"`;
                } else if (this.widgetTrigger === 'scroll') {
                    attrs += ` data-scroll="${this.widgetScroll}"`;
                }
                
                return `<script src="${url}" ${attrs} async defer><\/script>`;
            }
        }));
    });
</script>

<div x-data="formsIndex()">
    <div class="space-y-6 animate-slide-up">

    @if($forms->isEmpty())
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-indigo-50 flex items-center justify-center">
                <i class="fa-solid fa-rectangle-list text-indigo-500 text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Signup Forms Yet</h3>
            <p class="text-surface-500 text-sm mb-6">Design dynamic signup forms to grow your email lists.</p>
            <a href="{{ route('admin.signup-forms.create') }}" class="btn btn-primary">Create Form</a>
        </div>
    @else
        <div class="flex -m-6 flex-col">
            @foreach($forms as $form)
                @php
                    $embedCode = '<iframe src="' . route('public.forms.show', $form->token) . '" width="100%" height="450px" frameborder="0"></iframe>';
                    $convRate = $form->views_count > 0 ? round(($form->completed_submissions_count / $form->views_count) * 100, 1) : 0;
                @endphp
                <div class="py-6 pr-4 pl-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5 transition-all border-b border-brand/20 bg-surface-0">
                    
                    {{-- Left side: Name & Status --}}
                    <div class="flex items-center gap-3.5 min-w-[240px]">
                        <span class="w-3.5 h-3.5 rounded-full shrink-0 border border-black/10 shadow-sm" style="background-color: {{ $form->theme_color }}"></span>
                        <div>
                            <h3 class="font-black text-sm text-surface-900 leading-tight">{{ $form->name }}</h3>
                            <div class="flex items-center gap-2 mt-1.5">
                                @if(!empty($form->steps))
                                    <span class="text-[9px] font-black uppercase text-indigo-700 bg-indigo-50 border border-indigo-150 px-1.5 py-0.5 rounded-sm">Multi-Step</span>
                                @else
                                    <span class="text-[9px] font-black uppercase text-gray-600 bg-gray-100 border border-gray-200 px-1.5 py-0.5 rounded-sm">Single-Page</span>
                                @endif
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">{{ count($form->custom_fields ?? []) }} fields</span>
                            </div>
                        </div>
                    </div>

                    {{-- Middle-Left: Quick Integration Links --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <button @click="copyToClipboard(@js(route('public.forms.show', $form->token)))" 
                            class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 text-[10px] font-black uppercase tracking-wider text-gray-700 transition-all flex items-center gap-1.5 rounded-sm cursor-pointer">
                            <i class="fa-solid fa-link text-gray-400"></i>
                            Copy Link
                        </button>
                        <a href="{{ route('public.forms.show', $form->token) }}" target="_blank" 
                            class="px-3 py-1.5 bg-brand/5 hover:bg-brand text-brand hover:text-white border border-brand/20 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 rounded-sm">
                            <i class="fa-solid fa-up-right-from-square text-[9px]"></i>
                            Open
                        </a>
                        <button @click="openWidgetModal({ token: '{{ $form->token }}' })" 
                            class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 text-[10px] font-black uppercase tracking-wider text-gray-700 transition-all flex items-center gap-1.5 rounded-sm cursor-pointer">
                            <i class="fa-solid fa-code text-gray-400"></i>
                            Get Embed Widget
                        </button>
                    </div>

                    {{-- Middle-Right: Live Analytics --}}
                    <div class="flex items-center gap-6 lg:border-l lg:border-r lg:border-gray-100 lg:px-6 shrink-0">
                        <div class="text-center">
                            <span class="block text-[8px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Views</span>
                            <span class="font-extrabold text-sm text-surface-900">{{ number_format($form->views_count) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-[8px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Completions</span>
                            <span class="font-extrabold text-sm text-surface-900">{{ number_format($form->completed_submissions_count) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Conversion</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $convRate >= 30 ? 'bg-emerald-50 text-emerald-700 border border-emerald-250/20' : ($convRate >= 10 ? 'bg-amber-50 text-amber-700 border border-amber-250/20' : 'bg-gray-100 text-gray-700') }}">
                                {{ $convRate }}%
                            </span>
                        </div>
                    </div>

                    {{-- Right side: Form CRUD & Detail actions --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.signup-forms.analytics', $form) }}" 
                            class="px-3 py-1.5 hover:bg-brand/5 text-brand text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 transition-colors rounded-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Analytics
                        </a>
                        <a href="{{ route('admin.signup-forms.edit', $form) }}" 
                            class="px-3 py-1.5 hover:bg-gray-150 text-gray-700 text-[10px] font-black uppercase tracking-wider transition-colors rounded-sm">
                            Edit
                        </a>
                        <form action="{{ route('admin.signup-forms.destroy', $form) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this signup form?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 hover:bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-wider transition-colors rounded-sm cursor-pointer">
                                Delete
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>
    @endif
    </div>

    {{-- Widget Code Generator Modal --}}
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80" x-cloak>
        <div class="glass-card w-full max-w-lg p-7 rounded-sm shadow-2xl relative bg-white border border-gray-150 animate-slide-up max-h-[92vh] overflow-y-auto" @click.away="modalOpen = false">
            <button @click="modalOpen = false" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 text-xl font-black transition-colors cursor-pointer">&times;</button>
            
            <h3 class="text-xs font-black text-surface-900 uppercase tracking-widest mb-4">Website Integration Widget</h3>
            
            <div class="space-y-5 text-xs">
                <p class="text-gray-500 leading-relaxed text-[11px]">Customize and copy the script tag below to integrate the form directly into your external website or CMS (WordPress, Webflow, Shopify, etc.).</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Widget Type</label>
                        <select x-model="widgetType" class="w-full bg-gray-50 border border-gray-200 hover:border-gray-300 focus:border-brand focus:ring-1 focus:ring-brand/10 px-3 py-2 text-xs rounded-sm outline-none transition-all">
                            <option value="popup">Popup Modal</option>
                            <option value="slide-in">Slide-In Drawer (Bottom-Right)</option>
                            <option value="inline">Inline Embedded</option>
                        </select>
                    </div>
                    
                    <div x-show="widgetType !== 'inline'">
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Trigger Mode</label>
                        <select x-model="widgetTrigger" class="w-full bg-gray-50 border border-gray-200 hover:border-gray-300 focus:border-brand focus:ring-1 focus:ring-brand/10 px-3 py-2 text-xs rounded-sm outline-none transition-all">
                            <option value="exit-intent">On Exit Intent (Mouse Out)</option>
                            <option value="delay">Time Delay (Seconds)</option>
                            <option value="scroll">Scroll Percentage</option>
                            <option value="immediate">Immediately on Load</option>
                        </select>
                    </div>
                </div>
 
                {{-- Conditional Delay Input --}}
                <div x-show="widgetType !== 'inline' && widgetTrigger === 'delay'" class="animate-slide-up">
                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Delay (milliseconds)</label>
                    <input type="number" x-model="widgetDelay" step="500" min="500" class="w-full bg-gray-50 border border-gray-200 hover:border-gray-300 focus:border-brand focus:ring-1 focus:ring-brand/10 px-3 py-2 text-xs rounded-sm outline-none transition-all">
                </div>
 
                {{-- Conditional Scroll Input --}}
                <div x-show="widgetType !== 'inline' && widgetTrigger === 'scroll'" class="animate-slide-up">
                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Scroll Percentage (1-100%)</label>
                    <input type="number" x-model="widgetScroll" min="1" max="100" class="w-full bg-gray-50 border border-gray-200 hover:border-gray-300 focus:border-brand focus:ring-1 focus:ring-brand/10 px-3 py-2 text-xs rounded-sm outline-none transition-all">
                </div>

                {{-- Helper Instructions & Guide --}}
                <div class="p-3.5 bg-gray-50 rounded-sm border border-gray-200/60 text-[10px] space-y-3">
                    <div>
                        <span class="font-bold text-gray-800 block mb-1 uppercase tracking-wider text-[9px]"><i class="fa-solid fa-circle-info text-brand mr-1"></i> Where to place this code?</span>
                        <p class="text-gray-500 leading-relaxed" x-show="widgetType === 'inline'">
                            <strong>Inline Embedded:</strong> Paste the generated <code>&lt;div&gt;</code> placeholder code exactly where you want the signup form to physically render on your page (e.g. inside a blog sidebar, header, or article). Place the <code>&lt;script&gt;</code> tag right below it or in the footer of the page template.
                        </p>
                        <p class="text-gray-500 leading-relaxed" x-show="widgetType !== 'inline'">
                            <strong>Global Overlay:</strong> Paste the generated <code>&lt;script&gt;</code> tag anywhere inside the <code>&lt;body&gt;</code> section of your HTML (ideally right before the closing <code>&lt;/body&gt;</code> tag). It will run in the background without slowing down your page load.
                        </p>
                    </div>
                    
                    <div x-show="widgetType !== 'inline'" class="border-t border-gray-200 pt-2.5">
                        <span class="font-bold text-gray-800 block mb-1 uppercase tracking-wider text-[9px]"><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> How does the trigger work?</span>
                        <div class="text-gray-500 leading-relaxed">
                            <span x-show="widgetTrigger === 'exit-intent'">
                                <strong>Exit Intent:</strong> Tracks visitors' mouse cursor movements. The signup popup will appear instantly when the visitor moves their mouse away from the webpage toward the browser tabs or search bar (attempting to exit).
                            </span>
                            <span x-show="widgetTrigger === 'delay'">
                                <strong>Time Delay:</strong> Automatically displays the popup exactly <span class="font-bold text-brand" x-text="widgetDelay / 1000"></span> seconds after the visitor opens the webpage.
                            </span>
                            <span x-show="widgetTrigger === 'scroll'">
                                <strong>Scroll Percentage:</strong> Displays the popup as soon as the visitor scrolls down <span class="font-bold text-brand" x-text="widgetScroll"></span>% of the total page height.
                            </span>
                            <span x-show="widgetTrigger === 'immediate'">
                                <strong>Immediately on Load:</strong> Renders the popup overlay instantly as soon as the website page finished loading.
                            </span>
                        </div>
                    </div>
                </div>
 
                {{-- Code Output --}}
                <div class="mt-5">
                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Copy Code Block</label>
                    <div class="rounded-sm overflow-hidden border border-gray-200 bg-gray-950">
                        <div class="bg-gray-900 border-b border-gray-850 px-3.5 py-2 flex items-center justify-between">
                            <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider">HTML Script Tag</span>
                            <button @click="copyToClipboard(getGeneratedCode())" 
                                    class="px-3 py-1 bg-brand hover:bg-brand/90 text-white text-[9px] font-black uppercase tracking-wider rounded-sm transition-all cursor-pointer">
                                Copy Code
                            </button>
                        </div>
                        <pre class="text-gray-200 p-4 font-mono text-[10px] overflow-x-auto select-all max-h-36 whitespace-pre-wrap leading-relaxed" x-text="getGeneratedCode()"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
