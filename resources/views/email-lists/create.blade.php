@extends('layouts.app')

@section('title', 'Create Audience')
@section('heading', 'Create New List')

@section('content')
<div class="" x-data="importWizard()">
    <form action="{{ route('admin.email-lists.store') }}" method="POST" enctype="multipart/form-data" id="wizard-form">
        @csrf
        
        @if ($errors->any())
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold rounded-sm flex flex-col gap-1">
                <div class="flex items-center gap-2 text-rose-800 font-bold mb-1">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Import Validation Errors:
                </div>
                <ul class="list-disc list-inside font-medium text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" name="import_type" :value="method">
        <input type="hidden" name="signup_source" value="Direct Import">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            {{-- Left Column: Method Selection --}}
            <div class="lg:col-span-4 space-y-4">
                {{-- Hidden List Intent --}}
                <input type="hidden" name="list_type" value="dual">

                <div class="bg-white p-6 rounded-sm border border-gray-100 space-y-4 shadow-sm">
                    <div>
                        <label class="form-label uppercase tracking-widest text-[9px] font-black">List Name *</label>
                        <input type="text" name="name" class="form-input text-xs font-bold" value="Audience List - {{ now()->format('Y-m-d h:i A') }}" required>
                    </div>

                    <div>
                        <label class="form-label uppercase tracking-widest text-[9px] font-black">Sharing Visibility</label>
                        <div class="flex flex-col gap-2 mt-2">
                            <label class="inline-flex items-center text-xs font-semibold cursor-pointer">
                                <input type="radio" name="is_public" value="1" checked class="form-radio text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                Public (All Team Members)
                            </label>
                            <label class="inline-flex items-center text-xs font-semibold cursor-pointer">
                                <input type="radio" name="is_public" value="0" class="form-radio text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                                Private (Creator Only)
                            </label>
                        </div>
                    </div>

                    {{-- Advanced settings link --}}
                    <div class="pt-2">
                        <button type="button" @click="showAdvanced = true" class="text-brand hover:text-brand/80 text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Advanced Settings
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <h2 class="text-base font-black text-surface-800 uppercase">Import Method</h2>
                    <p class="text-[10px] text-surface-500 font-bold mt-2 uppercase">Choose how you want to add data.</p>
                </div>

                {{-- Option 1: Upload --}}
                <button type="button" @click="method = 'upload'"
                        :class="method === 'upload' ? 'border-brand ring-1 ring-brand bg-brand/5' : 'border-color bg-white hover:border-brand/50'"
                        class="w-full text-left p-6 border-2 rounded-sm transition-all group relative">
                    <div x-show="method === 'upload'" class="absolute right-4 top-1/2 -translate-y-1/2 text-brand">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div class="flex items-start gap-5 pr-8">
                        <div class="w-20 h-20 rounded-sm flex items-center justify-center shrink-0 transition-colors"
                             :class="method === 'upload' ? 'bg-brand text-white shadow-lg shadow-brand/30' : 'bg-surface-200 text-surface-900 group-hover:bg-brand/10 group-hover:text-brand'">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase text-surface-900 tracking-wide" :class="method === 'upload' ? 'text-brand' : ''">Upload File</h3>
                            <p class="mt-2 text-[11px] text-surface-500 font-bold leading-relaxed">Import contacts from a CSV or Excel file. Best for large lists with custom data fields.</p>
                        </div>
                    </div>
                </button>

                {{-- Option 2: Paste --}}
                <button type="button" @click="method = 'paste'"
                        :class="method === 'paste' ? 'border-brand ring-1 ring-brand bg-brand/5' : 'border-color bg-white hover:border-brand/50'"
                        class="w-full text-left p-6 border-2 rounded-sm transition-all group relative">
                    <div x-show="method === 'paste'" class="absolute right-4 top-1/2 -translate-y-1/2 text-brand">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div class="flex items-start gap-5 pr-8">
                        <div class="w-20 h-20 rounded-sm flex items-center justify-center shrink-0 transition-colors"
                             :class="method === 'paste' ? 'bg-brand text-white shadow-lg shadow-brand/30' : 'bg-surface-200 text-surface-900 group-hover:bg-brand/10 group-hover:text-brand'">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase text-surface-900 tracking-wide" :class="method === 'paste' ? 'text-brand' : ''">Copy & Paste</h3>
                            <p class="mt-2 text-[11px] text-surface-500 font-bold leading-relaxed">Paste names and emails directly from Excel or any text document. Fastest for quick imports.</p>
                        </div>
                    </div>
                </button>

                {{-- Option 3: Manual --}}
                <button type="button" @click="method = 'manual'"
                        :class="method === 'manual' ? 'border-brand ring-1 ring-brand bg-brand/5' : 'border-color bg-white hover:border-brand/50'"
                        class="w-full text-left p-6 border-2 rounded-sm transition-all group relative">
                    <div x-show="method === 'manual'" class="absolute right-4 top-1/2 -translate-y-1/2 text-brand">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div class="flex items-start gap-5 pr-8">
                        <div class="w-20 h-20 rounded-sm flex items-center justify-center shrink-0 transition-colors"
                             :class="method === 'manual' ? 'bg-brand text-white shadow-lg shadow-brand/30' : 'bg-surface-200 text-surface-900 group-hover:bg-brand/10 group-hover:text-brand'">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase text-surface-900 tracking-wide" :class="method === 'manual' ? 'text-brand' : ''">Manual Entry</h3>
                            <p class="mt-2 text-[11px] text-surface-500 font-bold leading-relaxed">Quickly type in a single contact manually. Good for testing or one-off additions.</p>
                        </div>
                    </div>
                </button>
            </div>

            {{-- Right Column: Action Block --}}
            <div class="lg:col-span-8">
                <div class="bg-white border border-gray-100 rounded-sm flex flex-col min-h-[500px] shadow-2xl shadow-gray-200/50">
                    
                    {{-- Dynamic Header --}}
                    <div class="p-6 border-b border-color flex items-center justify-between bg-surface-50/50">
                        <div>
                            <h2 class="text-xl font-black text-surface-900 uppercase tracking-tight" x-text="
                                method === 'upload' ? 'Upload Source File' :
                                (method === 'paste' ? 'Paste Data' : 'Add Contact')
                            "></h2>
                            <p class="text-[10px] text-surface-400 font-bold mt-1 uppercase tracking-widest" x-text="
                                method === 'upload' ? 'Data Ingestion Protocol' : 'Direct Data Entry'
                            "></p>
                        </div>
                        <div class="w-10 h-10 bg-white border border-gray-100 rounded-sm flex items-center justify-center text-brand">
                            <svg x-show="method === 'upload'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <svg x-show="method === 'paste'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <svg x-show="method === 'manual'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                    </div>

                    {{-- Dynamic Content Area --}}
                    <div class="p-6 flex-1 flex flex-col justify-center">
                        
                        {{-- Upload Section --}}
                        <div x-show="method === 'upload'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="border-2 border-dashed border-brand/30 bg-brand/5 hover:bg-brand/10 hover:border-brand/50 rounded-sm p-12 text-center relative group transition-all cursor-pointer" @click="document.getElementById('file-upload').click()">
                                <input type="file" name="file" id="file-upload" class="hidden" @change="handleFile($event)" accept=".csv,.xlsx,.xls,.txt">
                                <div class="relative z-10 pointer-events-none">
                                    <div class="w-20 h-20 bg-white shadow-xl shadow-brand/10 border border-brand/10 rounded-sm flex items-center justify-center mx-auto mb-8 text-brand font-black transition-transform group-hover:-translate-y-2">
                                        <svg x-show="!fileName" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span x-show="fileName" class="text-[12px] uppercase tracking-widest" x-text="fileName.split('.').pop()"></span>
                                    </div>
                                    <h3 class="text-2xl font-black text-surface-900 uppercase tracking-tight" x-text="fileName || 'Click to Browse Files'"></h3>
                                    <p class="text-surface-500 mt-3 text-[12px] font-bold">Supported Formats: CSV, XLSX, XLS, TXT (Max 100MB)</p>
                                    
                                    <!-- <div x-show="fileName" class="mt-6 inline-flex items-center gap-2 text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-sm uppercase tracking-widest border border-emerald-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        File Ready
                                    </div> -->
                                </div>
                            </div>
                        </div>

                        {{-- Paste Section --}}
                        <div x-show="method === 'paste'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                            <label class="block text-[11px] font-black text-surface-900 uppercase tracking-[0.2em] mb-4">Paste Contacts Below</label>
                            <div class="relative">
                                <textarea name="emails_text" x-model="pasteData" 
                                          class="w-full bg-white border-2 border-gray-100 rounded-sm p-6 h-50.5 font-mono text-sm text-surface-900 focus:bg-white focus:border-brand transition-all outline-none resize-none shadow-inner" 
                                          placeholder="email@example.com, Name, WhatsApp&#10;john@doe.com, John Doe, 919876543210"></textarea>
                                <div class="absolute bottom-4 right-4 flex gap-2">
                                    <span class="text-[10px] font-black text-surface-300 uppercase tracking-widest bg-white px-2 py-1 rounded border border-gray-100" x-text="pasteData ? pasteData.split('\n').filter(l => l.trim().length > 0).length + ' lines' : '0 lines'"></span>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Tip: Paste raw CSV data or tab-separated columns directly from Excel.
                            </p>
                        </div>

                        {{-- Manual Entry Section --}}
                        <div x-show="method === 'manual'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="max-w-md mx-auto py-9.5 w-full">
                            <div class="space-y-10">
                                <div>
                                    <label class="block text-xs font-black text-surface-900 uppercase tracking-[0.2em] mb-2">Email Address *</label>
                                    <input type="email" name="manual_email" x-model="manualEmail" class="w-full bg-white border-2 border-gray-200 rounded-sm px-5 py-4 text-sm font-bold text-surface-900 focus:bg-white focus:border-brand transition-all outline-none" placeholder="e.g. j.doe@company.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-surface-900 uppercase tracking-[0.2em] mb-2">Full Name (Optional)</label>
                                    <input type="text" name="manual_name" x-model="manualName" class="w-full bg-white border-2 border-gray-200 rounded-sm px-5 py-4 text-sm font-bold text-surface-900 focus:bg-white focus:border-brand transition-all outline-none" placeholder="e.g. John Doe">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-surface-900 uppercase tracking-[0.2em] mb-2">WhatsApp Number (Optional)</label>
                                    <input type="text" name="manual_whatsapp" x-model="manualWhatsApp" class="w-full bg-white border-2 border-gray-200 rounded-sm px-5 py-4 text-sm font-bold text-surface-900 focus:bg-white focus:border-brand transition-all outline-none" placeholder="e.g. 919876543210">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-surface-900 uppercase tracking-[0.2em] mb-2">Tags (Optional)</label>
                                    <input type="text" name="manual_tags" x-model="manualTags" class="w-full bg-white border-2 border-gray-200 rounded-sm px-5 py-4 text-sm font-bold text-surface-900 focus:bg-white focus:border-brand transition-all outline-none" placeholder="e.g. VIP, Customer">
                                    <p class="text-[10px] text-surface-400 font-bold mt-2">Comma separated values.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Dynamic Footer --}}
                    <div class="p-6 border-t border-color flex items-center justify-between">
                        <div class="text-[10px] font-bold text-surface-400 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Secure Import
                        </div>
                        <button type="submit" 
                                class="bg-brand text-white px-10 py-4 rounded-sm text-[11px] font-black uppercase tracking-[0.2em] transition-all hover:bg-brand/90 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-xl shadow-brand/20 flex items-center gap-3" 
                                :disabled="method === 'upload' ? !fileName : (method === 'paste' ? !pasteData : !manualEmail)">
                            Proceed to Import
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    {{-- Advanced Settings Modal --}}
    <div x-show="showAdvanced" x-cloak class="fixed inset-0 bg-black/30 z-[100] flex items-center justify-center p-4" @click.self="showAdvanced = false">
        <div class="bg-white rounded-sm shadow-2xl w-full max-w-md animate-slide-up" @keydown.escape.window="showAdvanced = false">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-base font-black text-surface-900 uppercase tracking-widest">Advanced List Settings</h3>
                <p class="text-xs text-surface-500 mt-1">Configure permissions for other team members on this list.</p>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Allowed Actions for Team</p>
                <div class="space-y-3">
                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                        <input type="checkbox" name="team_permissions[add_contact]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                        Add New Contacts (Manual & Imports)
                    </label>
                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                        <input type="checkbox" name="team_permissions[edit_contact]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                        Edit Existing Contacts
                    </label>
                    <label class="flex items-center text-xs font-semibold cursor-pointer">
                        <input type="checkbox" name="team_permissions[delete_contact]" value="1" checked class="form-checkbox text-brand focus:ring-brand border-gray-300 w-4 h-4 mr-2">
                        Delete Contacts
                    </label>
                </div>
            </div>
            <div class="p-6 border-t border-surface-100 flex justify-end">
                <button type="button" @click="showAdvanced = false" class="btn btn-primary btn-sm uppercase tracking-widest text-[9px]">Apply & Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function importWizard() {
    return {
        method: 'upload',
        fileName: '',
        pasteData: '',
        manualEmail: '',
        manualName: '',
        manualWhatsApp: '',
        manualTags: '',
        showAdvanced: false,

        handleFile(e) {
            if (e.target.files.length) {
                this.fileName = e.target.files[0].name;
            }
        }
    }
}
</script>
@endsection
