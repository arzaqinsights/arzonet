@extends('layouts.app')

@section('title', 'Create WhatsApp Template')

@section('content')
<div class="max-w-3xl space-y-6" x-data="{
    category: 'MARKETING',
    hasHeader: false,
    hasFooter: false,
    hasButtons: false,
    headerType: 'TEXT',
    buttons: [],
    addButton(type) {
        if(this.buttons.length >= 3) return;
        this.buttons.push({ type: type, text: '', url: '', phone: '' });
    },
    removeButton(i) { this.buttons.splice(i, 1); }
}">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.whatsapp.templates.index') }}" class="text-surface-400 hover:text-surface-900 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-black text-surface-900 uppercase tracking-tight">Create Template</h1>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-0.5">Submit a new template to Meta for approval</p>
        </div>
    </div>

    @if(session('error'))
    <div class="p-4 bg-red-50 border border-red-100 rounded-sm text-red-700 text-sm font-bold">
        <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <form action="{{ route('admin.whatsapp.templates.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Account Selection --}}
        <div class="bg-white border border-color rounded-sm p-6">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-4">Basic Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">WhatsApp Number *</label>
                    <select name="whatsapp_account_id" required
                        class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                        @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->display_name }} ({{ $account->phone_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">Category *</label>
                    <select name="category" x-model="category" required
                        class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                        <option value="MARKETING">Marketing</option>
                        <option value="UTILITY">Utility</option>
                        <option value="AUTHENTICATION">Authentication</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">Template Name * (lowercase, no spaces)</label>
                    <input type="text" name="name" required placeholder="e.g. order_confirmation"
                        pattern="[a-z0-9_]+"
                        class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                    <p class="text-[9px] text-surface-400 mt-1">Only lowercase letters, numbers, underscores.</p>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">Language *</label>
                    <select name="language" required
                        class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                        <option value="en_US">English (US)</option>
                        <option value="en_GB">English (UK)</option>
                        <option value="hi">Hindi</option>
                        <option value="ur">Urdu</option>
                        <option value="ar">Arabic</option>
                        <option value="es_MX">Spanish (Mexico)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Header (Optional) --}}
        <div class="bg-white border border-color rounded-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Header <span class="text-surface-400">(Optional)</span></h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="hasHeader" class="rounded text-brand focus:ring-brand">
                    <span class="text-[10px] font-black uppercase tracking-widest text-surface-600">Add Header</span>
                </label>
            </div>
            <div x-show="hasHeader" x-cloak class="space-y-3">
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border rounded-sm transition-colors" :class="headerType === 'TEXT' ? 'border-brand bg-brand/5' : 'border-color'">
                        <input type="radio" value="TEXT" x-model="headerType" class="text-brand"><span class="text-[10px] font-black uppercase">Text</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border rounded-sm transition-colors" :class="headerType === 'IMAGE' ? 'border-brand bg-brand/5' : 'border-color'">
                        <input type="radio" value="IMAGE" x-model="headerType" class="text-brand"><span class="text-[10px] font-black uppercase">Image</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border rounded-sm transition-colors" :class="headerType === 'DOCUMENT' ? 'border-brand bg-brand/5' : 'border-color'">
                        <input type="radio" value="DOCUMENT" x-model="headerType" class="text-brand"><span class="text-[10px] font-black uppercase">Document</span>
                    </label>
                </div>
                <input type="hidden" name="header_type" :value="headerType">
                <div x-show="headerType === 'TEXT'">
                    <input type="text" name="header_text" placeholder="Header text (max 60 chars)" maxlength="60"
                        class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                </div>
                <div x-show="headerType !== 'TEXT'">
                    <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest bg-surface-50 rounded-sm p-3">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        Media header URLs are provided at send time in the campaign.
                    </p>
                </div>
            </div>
        </div>

        {{-- Body (Required) --}}
        <div class="bg-white border border-color rounded-sm p-6">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-4">Body Text *</h2>
            <textarea name="body" required rows="5" placeholder="Your message body. Use {{1}}, {{2}} for variables. e.g. Hello {{1}}, your order {{2}} is confirmed!"
                class="w-full border border-color rounded-sm px-4 py-3 text-sm focus:ring-brand focus:border-brand bg-surface-50 resize-none"></textarea>
            <p class="text-[9px] text-surface-400 mt-2 font-bold">Use <code class="bg-surface-100 px-1 rounded">&#123;&#123;1&#125;&#125;</code>, <code class="bg-surface-100 px-1 rounded">&#123;&#123;2&#125;&#125;</code> as variable placeholders. These will be filled in during campaign creation.</p>
        </div>

        {{-- Footer (Optional) --}}
        <div class="bg-white border border-color rounded-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Footer <span class="text-surface-400">(Optional)</span></h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="hasFooter" class="rounded text-brand focus:ring-brand">
                    <span class="text-[10px] font-black uppercase tracking-widest text-surface-600">Add Footer</span>
                </label>
            </div>
            <div x-show="hasFooter" x-cloak>
                <input type="text" name="footer_text" placeholder="Footer text (max 60 chars)" maxlength="60"
                    class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
            </div>
        </div>

        {{-- Buttons (Optional) --}}
        <div class="bg-white border border-color rounded-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Buttons <span class="text-surface-400">(Optional, max 3)</span></h2>
            </div>
            <div class="space-y-3 mb-4">
                <template x-for="(btn, i) in buttons" :key="i">
                    <div class="flex items-start gap-3 p-3 bg-surface-50 rounded-sm border border-color">
                        <div class="flex-grow grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1">Button Text *</label>
                                <input type="text" :name="'buttons[' + i + '][text]'" x-model="btn.text" placeholder="Button label" maxlength="25"
                                    class="w-full border border-color rounded-sm px-3 py-2 text-sm focus:ring-brand focus:border-brand bg-white">
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1">Type</label>
                                <input type="hidden" :name="'buttons[' + i + '][type]'" :value="btn.type">
                                <div x-show="btn.type === 'URL'">
                                    <input type="url" :name="'buttons[' + i + '][url]'" x-model="btn.url" placeholder="https://example.com/{{1}}"
                                        class="w-full border border-color rounded-sm px-3 py-2 text-sm focus:ring-brand focus:border-brand bg-white">
                                </div>
                                <div x-show="btn.type === 'PHONE_NUMBER'">
                                    <input type="text" :name="'buttons[' + i + '][phone_number]'" x-model="btn.phone" placeholder="+919876543210"
                                        class="w-full border border-color rounded-sm px-3 py-2 text-sm focus:ring-brand focus:border-brand bg-white">
                                </div>
                                <div x-show="btn.type === 'QUICK_REPLY'">
                                    <p class="text-[9px] text-surface-400 font-bold uppercase tracking-widest py-2">Quick reply (user taps to respond)</p>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="removeButton(i)" class="mt-6 text-red-400 hover:text-red-600 transition-colors">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </template>
            </div>
            <div class="flex gap-2 flex-wrap" x-show="buttons.length < 3">
                <button type="button" @click="addButton('QUICK_REPLY')" class="text-[9px] font-black uppercase tracking-widest px-3 py-2 border border-color rounded-sm hover:bg-surface-50 transition-colors">
                    <i class="fa-solid fa-reply mr-1"></i> Quick Reply
                </button>
                <button type="button" @click="addButton('URL')" class="text-[9px] font-black uppercase tracking-widest px-3 py-2 border border-color rounded-sm hover:bg-surface-50 transition-colors">
                    <i class="fa-solid fa-link mr-1"></i> Visit URL
                </button>
                <button type="button" @click="addButton('PHONE_NUMBER')" class="text-[9px] font-black uppercase tracking-widest px-3 py-2 border border-color rounded-sm hover:bg-surface-50 transition-colors">
                    <i class="fa-solid fa-phone mr-1"></i> Call Phone
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.whatsapp.templates.index') }}" class="text-[10px] font-black uppercase tracking-widest text-surface-500 hover:text-surface-900 transition-colors">Cancel</a>
            <button type="submit" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-10 py-3.5 rounded-sm hover:bg-black transition-all shadow-lg shadow-brand/10">
                <i class="fa-solid fa-paper-plane mr-2"></i> Submit for Meta Approval
            </button>
        </div>
    </form>
</div>
@endsection
