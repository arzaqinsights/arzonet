@extends('layouts.app')
@section('title', 'New Campaign')
@section('heading', 'Initiate New Mission')

@section('content')
<div class="animate-slide-up" x-data="campaignCreator()">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Form Column --}}
        <div class="lg:col-span-7">
            <div class="glass-card rounded-md">
                <div class="p-8">
                    <form action="{{ route('admin.campaigns.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Campaign Name</label>
                                <input type="text" name="name" class="form-input rounded-md py-3" placeholder="e.g. Q4 Growth Sequence" required>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Email Subject Line</label>
                                <input type="text" name="subject" x-model="subject" @input="onSubjectChange()" class="form-input rounded-md py-3 border-primary-100 focus:border-primary-500" placeholder="Enter catch subject..." required>
                                <p class="text-[9px] text-primary-500 font-bold uppercase">This will be shown in the recipient's inbox.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Target Audience</label>
                                <select name="email_list_id" x-model="email_list_id" @change="fetchPreview()" class="form-select rounded-md" required>
                                    <option value="">Select Audience...</option>
                                    @foreach($emailLists as $list)
                                        <option value="{{ $list->id }}">{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Content Template</label>
                                <select name="template_id" x-model="template_id" @change="fetchPreview()" class="form-select rounded-md" required>
                                    <option value="">Select Template...</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="p-6 bg-primary-50/30 rounded-md border border-primary-100/50 space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-md bg-primary-100 flex items-center justify-center text-primary-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <h4 class="font-black text-surface-900 text-[10px] uppercase tracking-widest">Infrastructure Routing</h4>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Sender Profile</label>
                                <select name="sender_id" class="form-select !bg-white rounded-md" required>
                                    <option value="">Select Node...</option>
                                    @foreach($senders as $sender)
                                        <option value="{{ $sender->id }}">{{ $sender->from_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="pt-8 flex items-center justify-end">
                            <button type="submit" class="btn btn-primary rounded-md px-12 py-4 shadow-xl">
                                Launch Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Preview Column --}}
        <div class="lg:col-span-5">
            <div class="sticky top-8 space-y-4">
                <div class="glass-card rounded-md overflow-hidden flex flex-col h-[650px] border border-surface-200 shadow-2xl">
                    <div class="bg-surface-50 p-4 border-b border-surface-200">
                        <div class="flex gap-1.5 mb-4">
                            <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black text-surface-400 uppercase">Subject:</span>
                                <p class="text-xs font-black text-surface-900 truncate" x-text="personalizedSubject || 'Waiting...'"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-auto bg-white p-8 relative">
                        {{-- Debug Error Alert --}}
                        <div x-show="errorMessage" class="mb-4 p-4 bg-rose-50 border border-rose-100 rounded text-rose-600 text-xs font-bold uppercase tracking-tight">
                            ERROR: <span x-text="errorMessage"></span>
                        </div>

                        <template x-if="isLoading">
                            <div class="absolute inset-0 bg-white/90 flex flex-col items-center justify-center space-y-3 z-10">
                                <div class="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                                <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Syncing Preview...</p>
                            </div>
                        </template>
                        
                        <div x-show="!previewHtml && !isLoading" class="h-full flex flex-col items-center justify-center text-center p-8">
                            <p class="text-sm font-black text-surface-900 uppercase">Preview Area</p>
                            <p class="text-xs text-surface-400 mt-1">Select audience & template to load visual preview.</p>
                        </div>

                        <div x-show="previewHtml" x-html="previewHtml" class="prose max-w-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function campaignCreator() {
    return {
        template_id: '{{ $templates->first()?->id ?? '' }}',
        email_list_id: '{{ $emailLists->first()?->id ?? '' }}',
        subject: '',
        personalizedSubject: '',
        previewHtml: '',
        isLoading: false,
        errorMessage: '',
        subjectTimeout: null,

        init() {
            this.fetchPreview();
        },

        onSubjectChange() {
            clearTimeout(this.subjectTimeout);
            this.subjectTimeout = setTimeout(() => this.fetchPreview(), 500);
        },

        fetchPreview() {
            if(!this.template_id || !this.email_list_id) return;
            
            this.isLoading = true;
            this.errorMessage = '';
            
            const url = new URL('{{ route('admin.campaigns.preview') }}');
            url.searchParams.append('template_id', this.template_id);
            url.searchParams.append('email_list_id', this.email_list_id);
            if(this.subject) url.searchParams.append('subject', this.subject);

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error('Invalid JSON response from server. Check logs.');
                }
            })
            .then(data => {
                this.previewHtml = data.html || '<p>No content in template</p>';
                this.personalizedSubject = data.subject || this.subject;
                this.isLoading = false;
            })
            .catch(err => {
                this.errorMessage = err.message;
                this.isLoading = false;
            });
        }
    }
}
</script>
@endsection
