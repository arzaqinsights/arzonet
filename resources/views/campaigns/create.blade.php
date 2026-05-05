@extends('layouts.app')
@section('title', 'Create Campaign')
@section('heading', 'Create Campaign')

@section('content')
<div class="max-w-6xl mx-auto animate-slide-up" x-data="campaignCreator()">
    <form action="{{ route('campaigns.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- ── Left: Campaign Configuration ── --}}
            <div class="lg:col-span-7 space-y-6">
                <div class="glass-card p-8 border-surface-200">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-primary-50 flex items-center justify-center text-primary-600 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-surface-900">Configure Campaign</h3>
                            <p class="text-xs text-surface-500 font-medium">Define your target audience and sender details.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        {{-- Campaign Name --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Campaign Name</label>
                            <div class="relative">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                <input type="text" name="name" class="form-input pl-11 py-3 text-sm font-bold bg-surface-50/50 border-surface-100 focus:bg-white transition-all" placeholder="e.g. Weekly Product Update" value="{{ old('name') }}" required>
                            </div>
                            @error('name') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Email List Selection --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Target Audience (Email List)</label>
                            <div class="relative">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <select name="email_list_id" class="form-input pl-11 py-3 text-sm font-bold bg-surface-50/50 border-surface-100 focus:bg-white transition-all appearance-none" required x-model="emailListId" @change="updatePreview()">
                                    <option value="">— Select Recipient Database —</option>
                                    @foreach($emailLists as $list)
                                        <option value="{{ $list->id }}">{{ $list->name }} ({{ number_format($list->active_count) }} Active)</option>
                                    @endforeach
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-surface-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Sender Selection --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Sender Profile</label>
                                <div class="relative">
                                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <select name="sender_id" class="form-input pl-11 py-3 text-sm font-bold bg-surface-50/50 border-surface-100 focus:bg-white transition-all appearance-none" required x-model="senderId">
                                        <option value="">— Select Sender Email —</option>
                                        @foreach($senders as $sender)
                                            <option value="{{ $sender->id }}">{{ $sender->email }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-surface-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                                </div>
                            </div>

                            {{-- Template Selection --}}
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Email Template</label>
                                <div class="relative">
                                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                    <select name="template_id" class="form-input pl-11 py-3 text-sm font-bold bg-surface-50/50 border-surface-100 focus:bg-white transition-all appearance-none" required x-model="templateId" @change="updatePreview()">
                                        <option value="">— Select Design Template —</option>
                                        @foreach($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-surface-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-surface-100" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="flex items-center gap-2 text-[10px] font-black uppercase text-primary-600 tracking-widest hover:text-primary-700 transition-colors">
                                <svg :class="{ 'rotate-90': open }" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                Advanced Delivery Controls
                            </button>
                            <div x-show="open" x-collapse class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 p-6 bg-surface-50 rounded-2xl border border-surface-100">
                                <div>
                                    <label class="form-label text-[10px]">Frequency (Emails/Min)</label>
                                    <input type="number" name="emails_per_minute" class="form-input !bg-white" value="{{ old('emails_per_minute', config('emailplatform.limits.emails_per_minute')) }}" min="1">
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Batch Processing Size</label>
                                    <input type="number" name="batch_size" class="form-input !bg-white" value="{{ old('batch_size', config('emailplatform.batch_size')) }}" min="10">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="form-label text-[10px]">Scheduled Delivery</label>
                                    <input type="datetime-local" name="scheduled_at" class="form-input !bg-white" value="{{ old('scheduled_at') }}">
                                    <p class="text-[9px] text-surface-400 font-bold uppercase mt-2">Leave blank for immediate deployment.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost px-8 py-3 text-sm font-bold flex-1">Cancel</a>
                    <button type="submit" class="btn btn-primary px-12 py-3 text-sm font-black shadow-xl shadow-primary-200 flex-1 justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Launch Campaign
                    </button>
                </div>
            </div>

            {{-- ── Right: Insights & Preview ── --}}
            <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-8">
                <div class="grid grid-cols-1 gap-4">
                    {{-- Recipient Insight --}}
                    <div class="bg-white border border-surface-200 p-6 rounded-2xl shadow-sm hover:border-primary-200 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center text-primary-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
                            <div class="flex-1">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest leading-none mb-1">Target Audience</p>
                                <p class="text-xl font-black text-surface-900 leading-tight" x-text="totalRecipients.toLocaleString()">0</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cost Insight --}}
                    <div class="bg-white border border-surface-200 p-6 rounded-2xl shadow-sm hover:border-emerald-200 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                            <div class="flex-1">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest leading-none mb-1">Estimated Budget</p>
                                <p class="text-xl font-black text-emerald-600 leading-tight">$<span x-text="(totalRecipients * {{ config('emailplatform.cost_per_email') }}).toFixed(4)">0.0000</span></p>
                            </div>
                        </div>
                    </div>

                    {{-- Subject Insight --}}
                    <div class="bg-white border border-surface-200 p-6 rounded-2xl shadow-sm">
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest leading-none mb-2 px-1">Active Subject Line</p>
                        <div class="p-3 bg-surface-50 rounded-xl border border-surface-100">
                            <p class="text-xs font-bold text-surface-700 italic" x-text="previewSubject || 'No template selected...'"></p>
                        </div>
                    </div>
                </div>

                {{-- Live Preview Container --}}
                <div class="glass-card overflow-hidden border-surface-200 shadow-xl" x-show="previewHtml" x-cloak>
                    <div class="bg-surface-50/80 px-5 py-3 border-b border-surface-100 flex items-center justify-between">
                        <div class="flex gap-1.5">
                            <div class="w-2 h-2 rounded-full bg-red-400"></div>
                            <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                            <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                        </div>
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Real-time Preview</span>
                    </div>
                    <div class="bg-white">
                        <iframe :srcdoc="previewHtml" class="w-full border-0" style="min-height: 400px;"
                                onload="this.style.height = Math.min(this.contentDocument.body.scrollHeight, 600) + 'px'"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function campaignCreator() {
    return {
        emailListId: '{{ old("email_list_id") }}',
        templateId: '{{ old("template_id") }}',
        senderId: '{{ old("sender_id") }}',
        previewHtml: '',
        previewSubject: '',
        totalRecipients: 0,

        updatePreview() {
            if (!this.emailListId || !this.templateId) return;

            fetch('{{ route("campaigns.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email_list_id: this.emailListId,
                    template_id: this.templateId
                })
            })
            .then(r => r.json())
            .then(data => {
                this.previewHtml = data.html;
                this.previewSubject = data.subject;
                this.totalRecipients = data.total_recipients;
            });
        }
    };
}
</script>
@endsection
