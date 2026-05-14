@extends('layouts.app')
@section('title', 'Advance Editor — ' . $template->name)

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

    /* Customize GrapesJS UI to match Arzonet */
    .gjs-one-bg { background-color: #ffffff !important; }
    .gjs-two-bg { background-color: #fafafa !important; }
    .gjs-three-bg { background-color: #f5f5f5 !important; }
    .gjs-four-bg { background-color: var(--color-brand) !important; }
    .gjs-four-color { color: var(--color-brand) !important; }
    
    .gjs-cv-canvas { background-color: #efefef !important; }
    
    .gjs-pn-commands { position: relative; border-bottom: 1px solid #e5e5e5; }
    .gjs-pn-views-container { border-left: 1px solid #e5e5e5; }
    
    /* Toolbar Buttons */
    .gjs-pn-btn {
        transition: all 0.2s;
        border-radius: 2px !important;
    }
    .gjs-pn-btn.gjs-pn-active {
        box-shadow: none !important;
        background-color: var(--color-brand) !important;
        color: white !important;
    }

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
        transition: all 0.2s !important;
    }
    .gjs-block:hover {
        border-color: var(--color-brand) !important;
        color: var(--color-brand) !important;
    }

    /* Sidebar Titles */
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
        <input type="text" id="template-name" value="{{ $template->name }}" class="bg-transparent border-0 text-lg font-black uppercase p-0 m-0 focus:ring-0 w-full min-w-[400px] text-surface-900" placeholder="TEMPLATE NAME">
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel</a>
        <button onclick="saveGrapesTemplate()" id="save-btn" class="btn btn-primary px-8 py-2 text-sm font-black shadow-xl shadow-brand/20 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Update Master Design
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6 animate-fade-in">
    {{-- Variables Helper Bar --}}
    <div class="bg-surface-50 border-b border-surface-200 p-2 flex items-center gap-3 overflow-x-auto no-scrollbar">
        <span class="text-[10px] font-black uppercase text-surface-400 whitespace-nowrap border-r border-surface-200 pr-3">Merge Tags</span>
        @foreach(['full_name', 'first_name', 'last_name', 'email', 'company', 'job_title', 'city', 'unsubscribe_url'] as $tag)
            @php $tagName = '{{ ' . $tag . ' }}'; @endphp
            <button type="button" onclick="navigator.clipboard.writeText('{{ $tagName }}'); alert('Copied: {{ $tagName }}')" 
                class="px-2 py-1 bg-white border border-surface-200 rounded text-[10px] font-bold text-surface-600 hover:border-brand hover:text-brand transition-all whitespace-nowrap">
                {{ $tagName }}
            </button>
        @endforeach
    </div>

    <div id="gjs" style="height: 750px; width:100%;">
        {{-- Skeleton Loader --}}
        <div id="editor-loader" class="absolute inset-0 z-50 bg-white flex flex-col items-center justify-center gap-4 transition-opacity duration-500">
            <div class="w-12 h-12 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-1">Synchronizing Pro Editor Assets...</p>
        </div>
    </div>

    {{-- Form Controller --}}
    <form id="template-form" action="{{ route('admin.templates.update', $template) }}" method="POST" class="hidden">
        @csrf @method('PUT')
        <input type="hidden" name="name" id="hidden-name">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
    </form>
</div>

<script>
    let editor;

    function initGrapesJS() {
        console.log('Initializing GrapesJS Pro...');
        
        editor = grapesjs.init({
            container: '#gjs',
            fromElement: false,
            height: '800px',
            width: 'auto',
            storageManager: false,
            plugins: ['grapesjs-mjml'],
            pluginsOpts: {
                'grapesjs-mjml': {
                    // mjml plugins options
                }
            },
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap'
                ]
            }
        });

        // Load existing design if available
        @if($template->json_design)
            try {
                const mjmlCode = {!! json_encode($template->json_design) !!};
                if (mjmlCode) {
                    editor.setComponents(mjmlCode);
                }
            } catch (e) {
                console.error('Error loading design:', e);
            }
        @else
            // Set a default MJML starting point
            editor.setComponents(`<mjml>
                <mj-body background-color="#f5f5f5">
                    <mj-section background-color="#ffffff" padding="20px">
                        <mj-column>
                            <mj-image width="150px" src="{{ asset('images/logo/logo.png') }}"></mj-image>
                            <mj-divider border-color="#ff6b4a"></mj-divider>
                            <mj-text font-family="Inter, sans-serif" font-size="20px" font-weight="bold">
                                Welcome to Arzonet Pro
                            </mj-text>
                            <mj-text font-family="Inter, sans-serif">
                                Start building your fully custom advanced email here. Use the blocks on the right to drag components.
                            </mj-text>
                        </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>`);
        @endif

        // Add Merge Tags Blocks
        editor.BlockManager.add('var-fullname', {
            label: 'Full Name',
            category: 'Personalization',
            content: '<span>@{{ full_name }}</span>',
            attributes: { class: 'fa fa-user' }
        });
        editor.BlockManager.add('var-firstname', {
            label: 'First Name',
            category: 'Personalization',
            content: '<span>@{{ first_name }}</span>',
        });
        editor.BlockManager.add('var-company', {
            label: 'Company',
            category: 'Personalization',
            content: '<span>@{{ company }}</span>',
            attributes: { class: 'fa fa-building' }
        });
        editor.BlockManager.add('var-job', {
            label: 'Job Title',
            category: 'Personalization',
            content: '<span>@{{ job_title }}</span>',
        });
        editor.BlockManager.add('var-email', {
            label: 'Email Address',
            category: 'Personalization',
            content: '<span>@{{ email }}</span>',
        });
        editor.BlockManager.add('var-unsubscribe', {
            label: 'Unsubscribe Link',
            category: 'Personalization',
            content: '<a href="@{{ unsubscribe_url }}">Unsubscribe here</a>',
            attributes: { class: 'fa fa-sign-out' }
        });

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
        if (!name) {
            alert('Please fill in the template name.');
            return;
        }

        const saveBtn = document.getElementById('save-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = 'Saving...';

        try {
            console.log('Starting Save Process...');
            
            try {
                // GrapesJS MJML to HTML compilation
                const result = editor.runCommand('mjml-get-code');
                if (result && result.html) {
                    html = result.html;
                    mjml = result.mjml || editor.getHtml();
                } else {
                    // Alternative way to get code if the command result is different
                    mjml = editor.getHtml();
                    // If we can't compile to HTML, we try to at least get what's in the canvas
                    html = editor.getHtml(); 
                    console.warn('MJML compilation returned no HTML, using canvas HTML.');
                }
            } catch (cmdErr) {
                console.error('MJML Command Error:', cmdErr);
                mjml = editor.getHtml();
                html = mjml;
            }

            if (!html || html === mjml) {
                console.error('Failed to generate valid HTML from MJML.');
            }

            console.log('Data captured, submitting form...');
            document.getElementById('hidden-name').value = name;
            document.getElementById('html-content').value = html;
            document.getElementById('json-design').value = mjml;

            document.getElementById('template-form').submit();
        } catch (globalErr) {
            console.error('Global Save Error:', globalErr);
            alert('Error during save: ' + globalErr.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Update Master Design';
        }
    }

    document.addEventListener('DOMContentLoaded', initGrapesJS);
</script>
@endsection
