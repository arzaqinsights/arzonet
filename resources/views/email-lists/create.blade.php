@extends('layouts.app')
@section('title', 'Import Contacts Wizard')
@section('heading', 'Import Wizard')

@section('content')
<div class="max-w-5xl mx-auto" x-data="importWizard()">
    {{-- ── Step Indicator ── --}}
    <div class="mb-12">
        <div class="flex items-center justify-between relative">
            <div class="absolute top-1/2 left-0 w-full h-0.5 bg-surface-100 -translate-y-1/2 z-0"></div>
            <div class="absolute top-1/2 left-0 h-0.5 bg-primary-500 -translate-y-1/2 z-0 transition-all duration-500" :style="'width: ' + ((step-1)/2 * 100) + '%'"></div>
            
            <template x-for="s in [1, 2, 3]">
                <div class="relative z-10 flex flex-col items-center">
                    <div :class="step >= s ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-surface-400 border-surface-200'"
                         class="w-10 h-10 rounded-full border-2 flex items-center justify-center font-black transition-colors duration-500 shadow-sm"
                         x-text="s"></div>
                    <span :class="step >= s ? 'text-primary-700 font-bold' : 'text-surface-400 font-medium'"
                          class="text-[10px] uppercase tracking-widest mt-2 bg-surface-50 px-2"
                          x-text="getStepLabel(s)"></span>
                </div>
            </template>
        </div>
    </div>

    <form action="{{ route('email-lists.store') }}" method="POST" enctype="multipart/form-data" id="wizard-form">
        @csrf
        <input type="hidden" name="import_type" :value="method">

        {{-- ── Step 1: Choose Method ── --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-surface-900">How would you like to add contacts?</h2>
                <p class="text-surface-500 mt-2">Select the method that works best for your data.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Method: Upload --}}
                <button type="button" @click="selectMethod('upload')" 
                        class="group p-8 bg-white border-2 border-surface-100 rounded-3xl text-left hover:border-primary-500 hover:shadow-xl hover:shadow-primary-100/50 transition-all duration-300">
                    <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-surface-900">Upload a File</h3>
                    <p class="text-surface-500 mt-2 leading-relaxed">Import contacts from a CSV or Excel file. Best for large lists with custom data.</p>
                    <div class="mt-6 flex items-center text-primary-600 font-bold text-sm">
                        Select this method
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </button>

                {{-- Method: Paste --}}
                <button type="button" @click="selectMethod('paste')" 
                        class="group p-8 bg-white border-2 border-surface-100 rounded-3xl text-left hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-100/50 transition-all duration-300">
                    <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-surface-900">Copy and Paste</h3>
                    <p class="text-surface-500 mt-2 leading-relaxed">Simply paste names and emails from another document. Fastest for quick imports.</p>
                    <div class="mt-6 flex items-center text-indigo-600 font-bold text-sm">
                        Select this method
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </button>
            </div>
        </div>

        {{-- ── Step 2: Organize ── --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8" x-cloak>
            <div class="glass-card p-10">
                <h2 class="text-2xl font-black text-surface-900 mb-2">Let's organize your contacts</h2>
                <p class="text-surface-500 mb-8">This helps you filter and target your audience later.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <label class="form-label">List Name</label>
                            <input type="text" name="name" x-model="listName" class="form-input text-lg py-3" placeholder="e.g. Summer Sale Leads" required>
                        </div>
                        <div>
                            <label class="form-label">Segment Tag (Optional)</label>
                            <input type="text" name="segment_name" class="form-input" placeholder="e.g. VIP Customers">
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <label class="form-label">Signup Source</label>
                            <select name="signup_source" class="form-input py-3">
                                <option value="Website Widget">Website Widget</option>
                                <option value="Landing Page">Landing Page</option>
                                <option value="Mobile App">Mobile App</option>
                                <option value="Event/Trade Show">Event/Trade Show</option>
                                <option value="Partner Import">Partner Import</option>
                                <option value="Referral">Referral</option>
                                <option value="Manual Entry" selected>Manual Entry</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="p-4 bg-surface-50 rounded-2xl border border-surface-100">
                            <p class="text-xs text-surface-500 italic">"Organizing your list makes your CRM more powerful by allowing you to track exactly where your leads come from."</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="button" @click="step = 1" class="btn btn-ghost px-8">Back</button>
                <button type="button" @click="nextStep()" class="btn btn-primary px-12 py-3 shadow-lg shadow-primary-200" :disabled="!listName">Continue to Import</button>
            </div>
        </div>

        {{-- ── Step 3: Input Data ── --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-8" x-cloak>
            {{-- Upload Section --}}
            <template x-if="method === 'upload'">
                <div class="space-y-6">
                    <div class="glass-card p-12 border-2 border-dashed border-primary-200 bg-primary-50/10 text-center relative group overflow-hidden">
                        <input type="file" name="file" id="file-upload" class="hidden" @change="handleFile($event)" accept=".csv,.xlsx,.xls,.txt">
                        <label for="file-upload" class="cursor-pointer block relative z-10">
                            <div class="w-24 h-24 bg-white rounded-3xl shadow-sm flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-12 h-12 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-2xl font-black text-surface-900" x-text="fileName || 'Click or drag your file here'"></h3>
                            <p class="text-surface-500 mt-2">Maximum file size: 10MB. Supports CSV, XLSX, and TXT.</p>
                        </label>
                    </div>
                </div>
            </template>

            {{-- Paste Section --}}
            <template x-if="method === 'paste'">
                <div class="glass-card p-10">
                    <label class="form-label text-lg font-black mb-4">Paste your contacts below</label>
                    <textarea name="emails_text" x-model="pasteData" class="form-input h-80 font-mono text-sm p-6" placeholder="john@example.com&#10;jane@company.com&#10;..."></textarea>
                    <p class="text-xs text-surface-400 mt-4 italic">Tip: You can paste columns from Excel or just a list of emails.</p>
                </div>
            </template>

            <div class="flex items-center justify-between">
                <button type="button" @click="step = 2" class="btn btn-ghost px-8">Back</button>
                <button type="submit" class="btn btn-primary px-12 py-3 shadow-lg shadow-primary-200" 
                        :disabled="method === 'upload' ? !fileName : !pasteData">
                    Complete and Continue to Mapping
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function importWizard() {
    return {
        step: 1,
        method: 'upload',
        listName: '',
        fileName: '',
        pasteData: '',

        getStepLabel(s) {
            return ['Select Method', 'Organize', 'Import'][s-1];
        },

        selectMethod(m) {
            this.method = m;
            this.step = 2;
        },

        nextStep() {
            if (this.step < 3) this.step++;
        },

        handleFile(e) {
            if (e.target.files.length) {
                this.fileName = e.target.files[0].name;
            }
        }
    }
}
</script>
@endsection
