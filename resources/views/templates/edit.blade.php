@extends('layouts.app')
@section('title', 'Email Editor — ' . $template->name)

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('heading')
    <div class="flex items-center gap-2 group">
        <input type="text" id="template-name" value="{{ $template->name }}" class="bg-transparent border-0 text-lg font-black uppercase p-0 m-0 focus:ring-0 w-full min-w-[400px] text-surface-900" placeholder="TEMPLATE NAME">
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        @if(request()->has('return_to_campaign'))
            <a href="{{ route('admin.campaigns.wizard', request('return_to_campaign')) }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel & Return</a>
        @else
            <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel</a>
        @endif
        <button onclick="saveTemplate()" id="save-btn" class="btn btn-primary px-8 py-2 text-sm font-black shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Update Template
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6 animate-fade-in">
    <div id="editor-container" style="height: 800px; width:100%; border: 1px solid #e5e5e5; border-radius: 4px; overflow: hidden;"></div>

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
                    // Check if it's Unlayer JSON (has 'body' or 'counters') vs GrapesJS MJML
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
