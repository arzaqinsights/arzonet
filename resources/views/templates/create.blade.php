@extends('layouts.app')
@section('title', 'Create Template')
@section('heading', 'Create Template')

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('content')
<div class="space-y-6 animate-fade-in" x-data="templateEditor()">
    {{-- ── Premium Editor Header ── --}}
    <div class="glass-card p-6 border-surface-200">
        <div class="flex flex-col xl:flex-row items-end xl:items-center gap-6">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Template Identity</label>
                    <div class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <input type="text" x-model="name" class="form-input pl-11 py-3 text-sm font-bold bg-surface-50/50 border-surface-100 focus:bg-white transition-all" placeholder="Enter template name..." required>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Upload File (PDF/Docs)</label>
                    <div class="flex gap-2">
                        <input type="file" id="file-uploader" class="hidden" @change="uploadFile($event)">
                        <button type="button" @click="document.getElementById('file-uploader').click()" class="btn border-surface-200 hover:bg-surface-50 text-surface-700 py-3 px-4 w-full text-left flex justify-between items-center" :disabled="uploadingFile">
                            <span x-text="uploadingFile ? 'Uploading...' : 'Choose File...'" class="text-sm font-bold text-surface-500"></span>
                            <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </button>
                    </div>
                    <div x-show="uploadedUrl" class="mt-1 flex items-center gap-2" style="display: none;">
                        <input type="text" readonly :value="uploadedUrl" class="form-input text-[10px] py-1 px-2 flex-1 bg-green-50 border-green-200 text-green-700 font-mono">
                        <button type="button" @click="navigator.clipboard.writeText(uploadedUrl); alert('Link copied! You can now paste this link in any Button or Text in the editor.');" class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">Copy Link</button>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 w-full xl:w-auto">
                <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-3 text-sm font-bold flex-1 xl:flex-none text-center">Cancel</a>
                <button @click="saveTemplate()" class="btn btn-success px-10 py-3 text-sm font-black shadow-xl shadow-emerald-100 flex-1 xl:flex-none justify-center gap-2" :disabled="saving">
                    <span x-show="saving" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save Template
                </button>
            </div>
        </div>

        {{-- ── Smart Personalization Helper ── --}}
        <div class="mt-6 flex items-center gap-4 p-4 bg-primary-50/50 rounded-2xl border border-primary-100/50">
            <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-primary-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-black text-primary-700 uppercase tracking-widest leading-none mb-1">CRM Personalization Tips</p>
                <p class="text-xs text-surface-500 leading-relaxed">
                    Personalize your email with tags like <span class="px-1.5 py-0.5 bg-white border border-primary-100 rounded font-bold text-primary-700">@{{ first_name }}</span>, <span class="px-1.5 py-0.5 bg-white border border-primary-100 rounded font-bold text-primary-700">@{{ company }}</span>, or custom CSV fields.
                </p>
            </div>
        </div>
    </div>

    {{-- ── Unlayer Canvas ── --}}
    <div class="glass-card overflow-hidden shadow-2xl shadow-surface-100 border-surface-200" style="height: 750px;">
        <div id="unlayer-editor" style="height: 100%;"></div>
    </div>

    {{-- ── Form Controller ── --}}
    <form id="template-form" action="{{ route('admin.templates.store') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="name" :value="name">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
    </form>
</div>

<script>
function templateEditor() {
    return {
        name: '',
        saving: false,
        uploadingFile: false,
        uploadedUrl: '',

        init() {
            this.$nextTick(() => {
                unlayer.init({
                    id: 'unlayer-editor',
                    projectId: 1,
                    displayMode: 'email',
                    appearance: { 
                        theme: 'light',
                        panels: {
                            tools: { dock: 'left' }
                        }
                    },
                    features: {
                        preview: true,
                        imageEditor: true,
                        textEditor: {
                            spellChecker: true,
                            tables: true,
                            codeView: true,
                            emojis: true,
                        }
                    },
                    mergeTags: {
                        first_name: { name: "First Name", value: "@{{ first_name }}" },
                        last_name: { name: "Last Name", value: "@{{ last_name }}" },
                        full_name: { name: "Full Name", value: "@{{ full_name }}" },
                        email: { name: "Email Address", value: "@{{ email }}" },
                        company: { name: "Company Name", value: "@{{ company }}" },
                        job_title: { name: "Job Title", value: "@{{ job_title }}" },
                        city: { name: "City", value: "@{{ city }}" },
                        unsubscribe_url: { name: "Unsubscribe Link", value: "@{{ unsubscribe_url }}" }
                    }
                });

                unlayer.registerCallback('image', function(file, done) {
                    let formData = new FormData();
                    formData.append('file', file.attachments[0]);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fetch('{{ route("admin.media.upload") }}', { method: 'POST', body: formData })
                    .then(r => r.json()).then(data => {
                        if (data.success) done({ url: data.url });
                        else alert('Upload failed: ' + data.message);
                    }).catch(e => { console.error(e); alert('Upload error.'); });
                });
            });
        },

        uploadFile(event) {
            let file = event.target.files[0];
            if (!file) return;
            this.uploadingFile = true;
            let formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            fetch('{{ route("admin.media.upload") }}', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    this.uploadedUrl = data.url;
                } else alert('Upload failed: ' + data.message);
            }).catch(e => { console.error(e); alert('Upload error.'); })
            .finally(() => { this.uploadingFile = false; event.target.value = ''; });
        },

        saveTemplate() {
            if (!this.name) {
                alert('Please fill in the template name.');
                return;
            }
            this.saving = true;
            unlayer.exportHtml((data) => {
                document.getElementById('html-content').value = data.html;
                document.getElementById('json-design').value = JSON.stringify(data.design);
                document.getElementById('template-form').submit();
            });
        }
    };
}
</script>
@endsection
