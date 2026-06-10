@extends('layouts.fullscreen-builder')
@section('title', 'Email Editor — ' . $template->name)

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('content')
<div class="fixed inset-0 z-50 bg-white flex flex-col overflow-hidden">
    {{-- Top Navigation for Editor --}}
    <div class="h-16 border-b border-color bg-white px-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-6">
            @if(request()->has('return_to_campaign'))
                <a href="{{ route('admin.campaigns.wizard', request('return_to_campaign')) }}" class="text-gray-400 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @else
                <a href="{{ route('admin.templates.index') }}" class="text-gray-400 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif
            
            <div class="flex flex-col">
                <input type="text" id="template-name" value="{{ $template->name }}" class="bg-transparent border-0 text-xl font-bold p-0 m-0 focus:ring-0 w-full min-w-[300px] text-gray-900" placeholder="TEMPLATE NAME">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Template Editor</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            @if(request()->has('return_to_campaign'))
                <a href="{{ route('admin.campaigns.wizard', request('return_to_campaign')) }}" class="px-6 py-2 text-sm font-bold text-gray-500 hover:text-gray-900">Cancel</a>
            @else
                <a href="{{ route('admin.templates.index') }}" class="px-6 py-2 text-sm font-bold text-gray-500 hover:text-gray-900">Cancel</a>
            @endif
            <button onclick="saveTemplate()" id="save-btn" class="px-8 py-2 bg-gray-900 text-white rounded-sm font-bold text-sm hover:bg-black transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Save & Return
            </button>
        </div>
    </div>

    {{-- Editor Container --}}
    <div id="editor-container" class="flex-1 w-full bg-gray-50"></div>

    <form id="template-form" action="{{ route('admin.templates.update', $template->id) }}" method="POST" class="hidden">
        @csrf
        @method('PUT')
        <input type="hidden" name="name" id="hidden-name">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
        @if(request()->has('return_to_campaign'))
            <input type="hidden" name="return_to_campaign" value="{{ request('return_to_campaign') }}">
        @endif
    </form>
</div>

<script>
    function initUnlayer() {
        unlayer.init({
            id: 'editor-container',
            displayMode: 'email',
            appearance: {
                theme: 'light',
                panels: {
                    tools: { dock: 'left' }
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

        unlayer.addEventListener('editor:ready', function() {
            // Load existing design
            @if($template->json_design)
                try {
                    const design = {!! $template->json_design !!};
                    if (design && (design.body || design.counters)) {
                        unlayer.loadDesign(design);
                    } else {
                        console.warn('Incompatible design format detected. Starting fresh.');
                    }
                } catch (e) {
                    console.error('Error parsing saved design:', e);
                }
            @endif
        });
    }

    function saveTemplate() {
        const name = document.getElementById('template-name').value;
        if (!name) { alert('Please enter a template name.'); return; }

        const saveBtn = document.getElementById('save-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = 'Saving...';

        unlayer.exportHtml(function(data) {
            document.getElementById('hidden-name').value = name;
            document.getElementById('html-content').value = data.html;
            document.getElementById('json-design').value = JSON.stringify(data.design);
            
            document.getElementById('template-form').submit();
        });
    }

    document.addEventListener('DOMContentLoaded', initUnlayer);
</script>
@endsection
