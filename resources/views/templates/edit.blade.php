@extends('layouts.app')
@section('title', 'Edit Template — ' . $template->name)

@section('heading')
    <div class="flex items-center gap-2 group cursor-pointer">
        <input type="text" id="template-name" value="{{ $template->name }}" class="bg-transparent border-0 text-lg font-black uppercase p-0 m-0 focus:ring-0 w-full min-w-[400px] text-surface-900 group-hover:text-brand transition-colors" placeholder="TEMPLATE NAME">
        <svg class="w-4 h-4 text-surface-300 group-hover:text-brand opacity-0 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost px-6 py-2 text-sm font-bold">Cancel</a>
        <button onclick="saveTemplate()" id="save-btn" class="btn btn-primary px-8 py-2 text-sm font-black shadow-xl shadow-primary-100 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Update Design
        </button>
    </div>
@endsection

@push('head')
<script src="https://editor.unlayer.com/embed.js"></script>
@endpush

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- ── Unlayer Canvas ── --}}
    <div class="glass-card overflow-hidden shadow-2xl shadow-surface-100 border-surface-200 relative" style="height: 800px;">
        {{-- Loading Skeleton --}}
        <div id="editor-loader" class="absolute inset-0 z-50 bg-white flex flex-col items-center justify-center gap-4 transition-opacity duration-500">
            <div class="w-12 h-12 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
            <div class="flex flex-col items-center">
                <p class="text-xs font-black uppercase tracking-widest text-surface-900">Arzonet Editor</p>
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-1">Loading your saved design...</p>
            </div>
        </div>

        <div id="unlayer-editor" style="height: 100%;"></div>
    </div>

    {{-- ── Form Controller ── --}}
    <form id="template-form" action="{{ route('admin.templates.update', $template) }}" method="POST" class="hidden">
        @csrf @method('PUT')
        <input type="hidden" name="name" id="hidden-name">
        <input type="hidden" name="html_content" id="html-content">
        <input type="hidden" name="json_design" id="json-design">
    </form>
</div>

<script>
    window.uploadPdfForUnlayer = function(input) {
        let file = input.files[0];
        if (!file) return;
        let infoDiv = document.getElementById('my_file_url');
        if(infoDiv) infoDiv.innerText = 'Uploading...';

        let formData = new FormData();
        formData.append('file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        fetch('{{ route("admin.media.upload") }}', { method: 'POST', body: formData })
        .then(r => r.json()).then(res => {
            if(res.success) {
                if(window.unlayerUpdateValue) window.unlayerUpdateValue(res.url);
                if(infoDiv) infoDiv.innerText = 'Link: ' + res.url;
            } else {
                alert('Upload failed: ' + res.message);
                if(infoDiv) infoDiv.innerText = 'Upload failed';
            }
        }).catch(e => {
            console.error(e);
            alert('Upload error.');
            if(infoDiv) infoDiv.innerText = 'Upload error';
        });
    };

    function initUnlayer() {
        if (typeof unlayer === 'undefined') {
            console.log('Unlayer script not loaded yet, retrying in 200ms...');
            setTimeout(initUnlayer, 200);
            return;
        }

        console.log('Initializing Unlayer for Editing...');
        
        try {
            unlayer.init({
                id: 'unlayer-editor',
                displayMode: 'email',
                appearance: { 
                    theme: 'light',
                    panels: { tools: { dock: 'left' } }
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

            unlayer.registerPropertyEditor({
              name: 'my_file_uploader',
              Widget: unlayer.createWidget({
                render(value, updateValue, data) {
                  return `
                    <div style="text-align:center;">
                      <button type="button" onclick="document.getElementById('my_file_input').click()" style="background:#10b981; color:#fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer; width:100%; font-weight:bold;">Upload File (PDF/Docs)</button>
                      <input type="file" id="my_file_input" style="display:none;" onchange="window.uploadPdfForUnlayer(this)">
                      <div style="font-size:11px; margin-top:8px; word-break:break-all; color:#4b5563;" id="my_file_url">${value ? 'Link: ' + value : 'No file uploaded yet.'}</div>
                    </div>
                  `;
                },
                mount(node, value, updateValue, data) {
                  window.unlayerUpdateValue = updateValue;
                }
              })
            });

            unlayer.registerTool({
              name: 'custom_file',
              label: 'File / PDF',
              icon: 'fa-paperclip',
              supportedDisplayModes: ['web', 'email'],
              options: {
                buttonColors: {
                  title: "Button Style",
                  position: 1,
                  options: {
                    backgroundColor: {
                      label: "Background Color",
                      defaultValue: "#10b981",
                      widget: "color_picker"
                    },
                    textColor: {
                      label: "Text Color",
                      defaultValue: "#ffffff",
                      widget: "color_picker"
                    }
                  }
                },
                fileData: {
                  title: "File Attachment",
                  position: 2,
                  options: {
                    buttonText: {
                      label: "Button Text",
                      defaultValue: "Download File",
                      widget: "text"
                    },
                    fileUrl: {
                      label: "Upload your file",
                      defaultValue: "",
                      widget: "my_file_uploader"
                    }
                  }
                }
              },
              values: {},
              renderer: {
                Viewer: unlayer.createViewer({
                  render(values) {
                    return `<div style="text-align: center; padding: 10px;"><a href="${values.fileUrl}" style="display: inline-block; padding: 12px 24px; background-color: ${values.backgroundColor}; color: ${values.textColor}; text-decoration: none; border-radius: 4px; font-family: sans-serif; font-weight: bold;">📎 ${values.buttonText}</a></div>`;
                  }
                }),
                exporters: {
                  web: function(values) { return `<div style="text-align: center; padding: 10px;"><a href="${values.fileUrl}" style="display: inline-block; padding: 12px 24px; background-color: ${values.backgroundColor}; color: ${values.textColor}; text-decoration: none; border-radius: 4px; font-family: sans-serif; font-weight: bold;">📎 ${values.buttonText}</a></div>`; },
                  email: function(values) { return `<div style="text-align: center; padding: 10px;"><a href="${values.fileUrl}" style="display: inline-block; padding: 12px 24px; background-color: ${values.backgroundColor}; color: ${values.textColor}; text-decoration: none; border-radius: 4px; font-family: sans-serif; font-weight: bold;">📎 ${values.buttonText}</a></div>`; }
                },
                head: { css: function() {}, js: function() {} }
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

            unlayer.addEventListener('editor:ready', () => {
                console.log('Editor is ready!');
                try {
                    @if($template->json_design)
                        // Safely pass the JSON from PHP to a JS object
                        const design = {!! json_encode(json_decode($template->json_design) ?: []) !!};
                        if (design && Object.keys(design).length > 0) {
                            unlayer.loadDesign(design);
                            console.log('Design loaded successfully');
                        } else {
                            console.log('Design data was empty or invalid');
                        }
                    @endif
                } catch(e) {
                    console.error('Critical error loading design:', e);
                }
                
                hideLoader();
            });

            // Fallback: hide loader after 10 seconds anyway
            setTimeout(hideLoader, 10000);

        } catch (err) {
            console.error('Unlayer Init Error:', err);
            hideLoader();
        }
    }

    function hideLoader() {
        const loader = document.getElementById('editor-loader');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
    }

    document.addEventListener('DOMContentLoaded', initUnlayer);

    function saveTemplate() {
        let name = document.getElementById('template-name').value;
        if (!name) {
            alert('Please fill in the template name.');
            return;
        }
        document.getElementById('save-btn').disabled = true;
        document.getElementById('save-btn').innerHTML = 'Saving...';
        
        document.getElementById('hidden-name').value = name;
        
        unlayer.exportHtml((data) => {
            document.getElementById('html-content').value = data.html;
            document.getElementById('json-design').value = JSON.stringify(data.design);
            document.getElementById('template-form').submit();
        });
    }
</script>
@endsection
