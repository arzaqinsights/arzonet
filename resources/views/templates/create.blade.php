@extends('layouts.app')
@section('title', 'Create Template')

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('heading')
    <div class="flex items-center gap-2 group">
        <input type="text" id="template-name" value="Template - {{ now()->format('M d, Y h:i A') }}" class="bg-transparent border-0 text-lg font-black uppercase p-0 m-0 focus:ring-0 w-full min-w-[400px] text-surface-900" placeholder="TEMPLATE NAME">
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel</a>
        <button onclick="saveTemplate()" id="save-btn" class="btn btn-primary px-8 py-2 text-sm font-black shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Save Template
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6 animate-fade-in">
    <div id="editor-container" style="height: 800px; width:100%; border: 1px solid #e5e5e5; border-radius: 4px; overflow: hidden;"></div>

    <form id="template-form" action="{{ route('admin.templates.store') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="name" id="hidden-name">
        <input type="hidden" name="subject" id="hidden-subject" value="New Campaign">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
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
                first_name: { name: "First Name", value: "{{ '{{' }} first_name {{ '}}' }}" },
                last_name: { name: "Last Name", value: "{{ '{{' }} last_name {{ '}}' }}" },
                full_name: { name: "Full Name", value: "{{ '{{' }} full_name {{ '}}' }}" },
                email: { name: "Email Address", value: "{{ '{{' }} email {{ '}}' }}" },
                company: { name: "Company Name", value: "{{ '{{' }} company {{ '}}' }}" },
                job_title: { name: "Job Title", value: "{{ '{{' }} job_title {{ '}}' }}" },
                city: { name: "City", value: "{{ '{{' }} city {{ '}}' }}" },
                unsubscribe_url: { name: "Unsubscribe Link", value: "{{ '{{' }} unsubscribe_url {{ '}}' }}" }
            }
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
