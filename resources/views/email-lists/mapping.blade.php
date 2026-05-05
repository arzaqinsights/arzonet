@extends('layouts.app')
@section('title', 'Advanced Column Mapping')
@section('heading', 'CRM Column Mapping')

@section('content')
<div class="max-w-6xl mx-auto animate-slide-up" x-data="advancedMapper()">
    {{-- ── Step Indicator ── --}}
    <div class="mb-12">
        <div class="flex items-center justify-between relative max-w-4xl mx-auto">
            <div class="absolute top-1/2 left-0 w-full h-0.5 bg-surface-100 -translate-y-1/2 z-0"></div>
            <div class="absolute top-1/2 left-0 h-0.5 bg-primary-500 -translate-y-1/2 z-0 transition-all duration-500" style="width: 100%"></div>
            
            @foreach(['Select Method', 'Organize', 'Import', 'Match'] as $i => $label)
                <div class="relative z-10 flex flex-col items-center">
                    <div class="bg-primary-600 text-white border-primary-600 w-10 h-10 rounded-full border-2 flex items-center justify-center font-black shadow-sm">
                        {{ $i + 1 }}
                    </div>
                    <span class="text-primary-700 font-bold text-[10px] uppercase tracking-widest mt-2 bg-surface-50 px-2">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        {{-- ── Header ── --}}
        <div class="p-6 border-b border-surface-100 bg-gradient-to-br from-white to-surface-50">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="max-w-xl">
                    <h3 class="text-xl font-black text-surface-900 tracking-tight">{{ $emailList->name }}</h3>
                    <p class="text-surface-500 mt-1 text-sm">Connect spreadsheet columns to CRM fields.</p>
                </div>
                <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-surface-100 shadow-sm">
                    <div class="text-center px-3 border-r border-surface-100">
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Columns</p>
                        <p class="text-lg font-black text-surface-900">{{ count($headers) }}</p>
                    </div>
                    <div class="text-center px-3">
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Format</p>
                        <p class="text-lg font-black text-primary-600">{{ strtoupper(pathinfo($emailList->original_filename, PATHINFO_EXTENSION)) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('email-lists.store-mapping', $emailList->id) }}" method="POST" id="mapping-form" onsubmit="return validateMapping()">
            @csrf
            
            <div class="divide-y divide-surface-100">
                @foreach($headers as $index => $header)
                    @php
                        $isEmail = $suggestedEmail === $header;
                        $isName = $suggestedName === $header;
                        $initialValue = $isEmail ? 'email' : ($isName ? 'name' : '');
                        $samples = [];
                        foreach(array_slice($sampleRows, 0, 3) as $row) {
                            $samples[] = $row[$header] ?? '—';
                        }
                    @endphp
                    
                    <div class="group p-4 hover:bg-surface-50/30 transition-all duration-200 flex flex-col lg:flex-row lg:items-center gap-4"
                         x-data="{ selected: '{{ $initialValue }}' }">
                        
                        {{-- CSV Column Info --}}
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-surface-100 flex items-center justify-center font-black text-surface-400 text-xs group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-surface-900 leading-tight">{{ $header }}</h4>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <template x-if="selected">
                                            <span class="badge badge-success !text-[8px] !px-1.5 !py-0">Matched</span>
                                        </template>
                                        <template x-if="!selected">
                                            <span class="badge badge-neutral !text-[8px] !px-1.5 !py-0">Skipped</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Sample Data Chips --}}
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($samples as $sample)
                                    <span class="px-2 py-0.5 bg-white border border-surface-200 rounded-md text-[10px] font-medium text-surface-500 shadow-sm">
                                        {{ Str::limit($sample, 25) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- CRM Mapping Selector --}}
                        <div class="w-full lg:w-72">
                            <div class="relative" :class="selected ? 'ring-2 ring-primary-500/20 rounded-lg' : ''">
                                <select name="mapping[{{ $header }}]" 
                                        data-header="{{ $header }}"
                                        x-model="selected"
                                        class="form-select w-full !h-10 !pl-4 !pr-10 !bg-white border border-surface-200 rounded-lg font-bold text-xs text-surface-700 focus:border-primary-500 transition-all appearance-none"
                                        :class="selected ? 'border-primary-500 text-primary-900' : 'border-surface-200'">
                                    <option value="">— Skip this column —</option>
                                    <optgroup label="Core Identity">
                                        <option value="email">Email Address (Primary) *</option>
                                        <option value="name">Full Name</option>
                                        <option value="first_name">First Name</option>
                                        <option value="last_name">Last Name</option>
                                        <option value="gender">Gender</option>
                                        <option value="dob">Date of Birth</option>
                                    </optgroup>
                                    <optgroup label="Professional Data">
                                        <option value="company">Company Name</option>
                                        <option value="job_title">Job Title</option>
                                        <option value="department">Department</option>
                                        <option value="industry">Industry</option>
                                        <option value="website">Website URL</option>
                                    </optgroup>
                                    <optgroup label="Contact & Location">
                                        <option value="phone">Phone Number</option>
                                        <option value="address">Street Address</option>
                                        <option value="city">City</option>
                                        <option value="state">State / Province</option>
                                        <option value="zip">Zip / Postal Code</option>
                                        <option value="country">Country</option>
                                    </optgroup>
                                    <optgroup label="Social Profiles">
                                        <option value="linkedin">LinkedIn URL</option>
                                        <option value="twitter">Twitter / X</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="facebook">Facebook</option>
                                    </optgroup>
                                    <optgroup label="Marketing & Custom">
                                        <option value="segment">Segment Name</option>
                                        <option value="source">Lead Source</option>
                                        <option value="custom_1">Custom Field 1</option>
                                        <option value="custom_2">Custom Field 2</option>
                                        <option value="custom_3">Custom Field 3</option>
                                    </optgroup>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <p class="mt-1 text-[9px] font-bold text-surface-400 uppercase tracking-widest pl-1" x-show="selected">
                                To: <span class="text-primary-600" x-text="selected.replace('_', ' ').toUpperCase()"></span>
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Final Actions ── --}}
            <div class="p-6 border-t border-surface-100 bg-surface-50/50 backdrop-blur-md flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3 text-surface-500">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="font-black text-surface-900 text-xs uppercase tracking-tight">Ready to synchronize?</p>
                            <p class="text-[10px]">Every email will undergo full DNS & MX verification.</p>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <a href="{{ route('email-lists.index') }}" class="btn btn-ghost px-6 btn-sm">Discard</a>
                    <button type="submit" class="btn btn-primary px-10 py-2.5 shadow-xl shadow-primary-200 btn-sm">
                        Confirm & Import
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
        </form>
    </div>
</div>

<script>
window.onload = function() {
    const suggestions = @json($autoSuggestions ?? []);
    Object.entries(suggestions).forEach(([header, field]) => {
        const select = document.querySelector(`select[data-header="${header}"]`);
        if (select) select.value = field;
    });
};

function validateMapping() {
    const selects = document.querySelectorAll('select[name^="mapping"]');
    let emailMapped = false;
    selects.forEach(select => {
        if (select.value === 'email') emailMapped = true;
    });

    if (!emailMapped) {
        alert('Critical Error: You MUST map at least one column to the "Email Address (Primary)" field.');
        return false;
    }
    return true;
}

function advancedMapper() {
    return {
        customFields: [ { label: '', column: '' } ],
        addCustomField() {
            this.customFields.push({ label: '', column: '' });
        },
        removeCustomField(index) {
            this.customFields.splice(index, 1);
        }
    };
}
</script>
@endsection
