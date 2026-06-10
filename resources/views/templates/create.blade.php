@extends('layouts.fullscreen-builder')
@section('title', 'Create Template')

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('content')
<div class="fixed inset-0 z-50 bg-white flex flex-col overflow-hidden">
    {{-- Top Navigation for Editor --}}
    <div class="h-16 border-b border-color bg-white px-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-6">
            <a href="{{ route('admin.templates.index') }}" class="text-gray-400 hover:text-gray-900 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            
            <div class="flex flex-col">
                <input type="text" id="template-name" value="Template - {{ now()->format('M d, Y h:i A') }}" class="bg-transparent border-0 text-xl font-bold p-0 m-0 focus:ring-0 w-full min-w-[300px] text-gray-900" placeholder="TEMPLATE NAME">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Template Creator</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.templates.index') }}" class="px-6 py-2 text-sm font-bold text-gray-500 hover:text-gray-900">Cancel</a>
            <button onclick="saveTemplate()" id="save-btn" class="px-8 py-2 bg-gray-900 text-white rounded-sm font-bold text-sm hover:bg-black transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Template
            </button>
        </div>
    </div>

    {{-- Editor Container --}}
    <div id="editor-container" class="flex-1 w-full bg-gray-50"></div>

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
