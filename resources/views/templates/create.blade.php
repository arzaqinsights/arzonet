@extends('layouts.app')
@section('title', 'New Pro Template')

@push('head')
{{-- GrapesJS Core --}}
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<script src="https://unpkg.com/grapesjs"></script>
{{-- GrapesJS MJML Plugin --}}
<script src="https://unpkg.com/grapesjs-mjml"></script>

<style>
    /* Premium Sharp Industrial Theme for GrapesJS */
    #gjs {
        border: 1px solid var(--color-surface-200);
        border-radius: 4px;
        overflow: hidden;
    }

    .gjs-one-bg { background-color: #ffffff !important; }
    .gjs-two-bg { background-color: #fafafa !important; }
    .gjs-three-bg { background-color: #f5f5f5 !important; }
    .gjs-four-bg { background-color: var(--color-brand) !important; }
    .gjs-four-color { color: var(--color-brand) !important; }
    
    .gjs-cv-canvas { background-color: #efefef !important; }
    .gjs-pn-commands { position: relative; border-bottom: 1px solid #e5e5e5; }
    .gjs-pn-views-container { border-left: 1px solid #e5e5e5; }
    
    .gjs-pn-btn { transition: all 0.2s; border-radius: 2px !important; }
    .gjs-pn-btn.gjs-pn-active { background-color: var(--color-brand) !important; color: white !important; }

    .gjs-block {
        width: 100% !important;
        min-height: auto !important;
        padding: 12px !important;
        border-radius: 2px !important;
        border: 1px solid #e5e5e5 !important;
        margin-bottom: 8px !important;
        font-family: 'Inter', sans-serif !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        font-size: 10px !important;
        letter-spacing: 0.05em !important;
    }
    .gjs-block:hover { border-color: var(--color-brand) !important; color: var(--color-brand) !important; }

    .gjs-sm-title, .gjs-layers-title, .gjs-blocks-title, .gjs-clm-title {
        background-color: #fafafa !important;
        border-bottom: 1px solid #e5e5e5 !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        font-size: 11px !important;
        letter-spacing: 0.1em !important;
    }
</style>
@endpush

@section('heading')
    <div class="flex items-center gap-2 group">
        <input type="text" id="template-name" value="Template - {{ now()->format('M d, Y h:i A') }}" class="bg-transparent border-0 text-lg font-black uppercase p-0 m-0 focus:ring-0 w-full min-w-[400px] text-surface-900" placeholder="TEMPLATE NAME">
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel</a>
        <button onclick="saveGrapesTemplate()" id="save-btn" class="btn btn-primary px-8 py-2 text-sm font-black shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Master Design
        </button>
    </div>
@endsection

@section('content')
    {{-- Variables Helper Bar --}}
    <div class="bg-surface-50 border border-surface-200 p-2 flex items-center gap-3 overflow-x-auto no-scrollbar rounded-t-lg">
        <span class="text-[10px] font-black uppercase text-surface-400 whitespace-nowrap border-r border-surface-200 pr-3">Merge Tags</span>
        @foreach(['full_name', 'first_name', 'last_name', 'email', 'company', 'job_title', 'city', 'unsubscribe_url'] as $tag)
            <button type="button" onclick="navigator.clipboard.writeText('{{ '{{ ' . $tag . ' }}' }}'); alert('Copied: {{ '{{ ' . $tag . ' }}' }}')" 
                class="px-2 py-1 bg-white border border-surface-200 rounded text-[10px] font-bold text-surface-600 hover:border-brand hover:text-brand transition-all whitespace-nowrap">
                {{ '{{ ' . $tag . ' }}' }}
            </button>
        @endforeach
    </div>

    <div id="gjs" style="height: 750px; width:100%;">
        <div id="editor-loader" class="absolute inset-0 z-50 bg-white flex flex-col items-center justify-center gap-4 transition-opacity duration-500">
            <div class="w-12 h-12 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-1">Synchronizing Pro Editor Assets...</p>
        </div>
    </div>

    <form id="template-form" action="{{ route('admin.templates.store') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="name" id="hidden-name">
        <input type="hidden" name="subject" id="hidden-subject" value="New Campaign">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
    </form>
</div>

<script>
    let editor;

    function initGrapesJS() {
        editor = grapesjs.init({
            container: '#gjs',
            fromElement: false,
            height: '800px',
            width: 'auto',
            storageManager: false,
            plugins: ['grapesjs-mjml'],
            canvas: {
                styles: ['https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap']
            }
        });

        // Add Merge Tags Blocks
        editor.BlockManager.add('var-fullname', {
            label: 'Full Name',
            category: 'Personalization',
            content: '<span>{{ '{{ full_name }}' }}</span>',
            attributes: { class: 'fa fa-user' }
        });
        editor.BlockManager.add('var-firstname', {
            label: 'First Name',
            category: 'Personalization',
            content: '<span>{{ '{{ first_name }}' }}</span>',
        });
        editor.BlockManager.add('var-company', {
            label: 'Company',
            category: 'Personalization',
            content: '<span>{{ '{{ company }}' }}</span>',
            attributes: { class: 'fa fa-building' }
        });
        editor.BlockManager.add('var-job', {
            label: 'Job Title',
            category: 'Personalization',
            content: '<span>{{ '{{ job_title }}' }}</span>',
        });
        editor.BlockManager.add('var-email', {
            label: 'Email Address',
            category: 'Personalization',
            content: '<span>{{ '{{ email }}' }}</span>',
        });
        editor.BlockManager.add('var-unsubscribe', {
            label: 'Unsubscribe Link',
            category: 'Personalization',
            content: '<a href="{{ '{{ unsubscribe_url }}' }}">Unsubscribe here</a>',
            attributes: { class: 'fa fa-sign-out' }
        });

        // Default Starting Template
        editor.setComponents(`<mjml>
            <mj-body background-color="#f5f5f5">
                <mj-section background-color="#ffffff" padding="20px">
                    <mj-column>
                        <mj-text font-family="Inter, sans-serif" font-size="24px" font-weight="900" text-transform="uppercase">
                            New Master Design
                        </mj-text>
                        <mj-divider border-color="#ff6b4a" border-width="4px"></mj-divider>
                        <mj-text font-family="Inter, sans-serif" line-height="1.6">
                            Start creating your high-conversion advanced email template. Drag blocks from the right panel to build your layout.
                        </mj-text>
                        <mj-button background-color="#ff6b4a" font-family="Inter, sans-serif" font-weight="bold" border-radius="2px">
                            CALL TO ACTION
                        </mj-button>
                    </mj-column>
                </mj-section>
            </mj-body>
        </mjml>`);

        editor.on('load', () => {
            const loader = document.getElementById('editor-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });
    }

    function saveGrapesTemplate() {
        const name = document.getElementById('template-name').value;
        if (!name) { alert('Please fill in name.'); return; }

        const saveBtn = document.getElementById('save-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = 'Creating...';

        const html = editor.runCommand('mjml-get-code').html;
        const json = {
            components: editor.getComponents(),
            styles: editor.getStyle()
        };

        document.getElementById('hidden-name').value = name;
        document.getElementById('html-content').value = html;
        document.getElementById('json-design').value = JSON.stringify(json);

        document.getElementById('template-form').submit();
    }

    document.addEventListener('DOMContentLoaded', initGrapesJS);
</script>
@endsection
