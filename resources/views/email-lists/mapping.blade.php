@extends('layouts.app')
@section('title', 'Advanced Data Mapping')@section('header-actions')
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-sm bg-brand text-white flex items-center justify-center font-black">2</div>
        <div>
            <h3 class="text-lg font-black text-surface-900 tracking-tight uppercase">Structure Synchronization</h3>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest">Connect your spreadsheet
                columns to CRM identity fields</p>
        </div>
    </div>
    <div class="hidden md:flex items-center gap-2">
        <div class="h-1 w-24 bg-brand rounded-full"></div>
        <div class="h-1 w-24 bg-gray-200 rounded-full"></div>
        <div class="h-1 w-24 bg-gray-200 rounded-full"></div>
    </div>
@endsection
@section('content')
    <div x-data="mappingAssistant()">
        {{-- Filtering headers to show only those with data --}}
        @php
            $activeHeaders = [];
            $initialMappings = [];
            $usedTargets = [];

            foreach ($headers as $header) {
                $hasData = false;
                foreach ($sampleRows as $row) {
                    if (!empty($row[$header]) && trim($row[$header]) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $activeHeaders[] = $header;
                    
                    // ── SMART AUTO-MAPPING LOGIC ──
                    $h = strtolower($header);
                    $target = '';

                    // 1. Explicit Suggestions from Service
                    if (isset($autoSuggestions[$header])) {
                        $target = $autoSuggestions[$header];
                    } 
                    // 2. Heuristic Matching
                    else {
                        if (str_contains($h, 'email') || str_contains($h, 'mail')) $target = 'email';
                        elseif (str_contains($h, 'name')) {
                             if (str_contains($h, 'first')) $target = 'first_name';
                             elseif (str_contains($h, 'last')) $target = 'last_name';
                             else $target = 'name';
                        }
                        elseif (str_contains($h, 'company') || str_contains($h, 'firm') || str_contains($h, 'organization')) $target = 'company';
                        elseif (str_contains($h, 'whatsapp') || str_contains($h, 'wa number')) $target = 'whatsapp_number';
                        elseif (str_contains($h, 'phone') || str_contains($h, 'mobile') || str_contains($h, 'contact') || str_contains($h, 'number')) $target = 'whatsapp_number';
                        elseif (str_contains($h, 'city')) $target = 'city';
                        elseif (str_contains($h, 'state')) $target = 'state';
                        elseif (str_contains($h, 'country')) $target = 'country';
                        elseif (str_contains($h, 'title') || str_contains($h, 'designation')) $target = 'job_title';
                    }

                    if ($target && in_array($target, $usedTargets)) {
                        $target = ''; 
                    }

                    if ($target) $usedTargets[] = $target;
                    // If not explicitly mapped to a system field, default to "save as custom field"
                    $initialMappings[$header] = $target ?: '__custom__';
                }
            }
        @endphp

        <div class="bg-white border border-color rounded-sm overflow-hidden">
            {{-- Table Header --}}
            <div class="grid grid-cols-12 gap-4 p-6 bg-surface-50 border-b border-color items-center">
                <div class="col-span-3">
                    <p class="text-xs font-black text-surface-600 uppercase tracking-widest">Source Header</p>
                </div>
                <div class="col-span-1"></div>
                <div class="col-span-4">
                    <p class="text-xs font-black text-surface-600 uppercase tracking-widest">Target Identity</p>
                </div>
                <div class="col-span-4">
                    <p class="text-xs font-black text-surface-600 uppercase tracking-widest pl-4">Value Insight</p>
                </div>
            </div>

            <form action="{{ route('admin.email-lists.store-mapping', $emailList->id) }}" method="POST" id="mapping-form"
                @submit.prevent="validateAndSubmit">
                @csrf
                <div class="divide-y divide-gray-200">
                    @foreach($activeHeaders as $index => $header)
                        @php
                            $samples = [];
                            foreach (array_slice($sampleRows, 0, 4) as $row) {
                                if (!empty($row[$header])) {
                                    $samples[] = $row[$header];
                                }
                            }
                        @endphp

                        <div class="grid grid-cols-12 gap-4 px-6 py-3 items-center hover:bg-surface-50 transition-colors">
                            {{-- 1. Excel Header --}}
                            <div class="col-span-3">
                                <h4 class="text-sm text-surface-900 truncate">{{ $header }}</h4>
                            </div>

                            {{-- 2. Connector --}}
                            <div class="col-span-1 flex justify-center text-surface-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>

                            {{-- 3. System Mapping --}}
                            <div class="col-span-4">
                                <div class="relative">
                                    <select name="mapping[{{ $header }}]" x-model="mappings['{{ $header }}']"
                                        class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-100 rounded-sm font-bold text-[11px] uppercase tracking-wider transition-all appearance-none outline-none focus:border-brand"
                                        :class="mappings['{{ $header }}'] ? 'text-surface-600 border-surface-600/30 bg-surface-600/5' : 'text-surface-400'">
                                        <option value="">— Skip Column —</option>
                                        <option value="__custom__" selected>✦ Save as Custom Field</option>
                                        <optgroup label="Main Identity" class="bg-white">
                                            <option value="email" :disabled="isOptionDisabled('email', '{{ $header }}')" x-text="getOptionText('email', 'Email Address *', '{{ $header }}')"></option>
                                            <option value="name" :disabled="isOptionDisabled('name', '{{ $header }}')" x-text="getOptionText('name', 'Full Name', '{{ $header }}')"></option>
                                            <option value="first_name" :disabled="isOptionDisabled('first_name', '{{ $header }}')" x-text="getOptionText('first_name', 'First Name', '{{ $header }}')"></option>
                                            <option value="last_name" :disabled="isOptionDisabled('last_name', '{{ $header }}')" x-text="getOptionText('last_name', 'Last Name', '{{ $header }}')"></option>
                                        </optgroup>
                                        <optgroup label="Corporate" class="bg-white">
                                            <option value="company" :disabled="isOptionDisabled('company', '{{ $header }}')" x-text="getOptionText('company', 'Company', '{{ $header }}')"></option>
                                            <option value="job_title" :disabled="isOptionDisabled('job_title', '{{ $header }}')" x-text="getOptionText('job_title', 'Job Title', '{{ $header }}')"></option>
                                            <option value="department" :disabled="isOptionDisabled('department', '{{ $header }}')" x-text="getOptionText('department', 'Department', '{{ $header }}')"></option>
                                            <option value="industry" :disabled="isOptionDisabled('industry', '{{ $header }}')" x-text="getOptionText('industry', 'Industry', '{{ $header }}')"></option>
                                            <option value="website" :disabled="isOptionDisabled('website', '{{ $header }}')" x-text="getOptionText('website', 'Website', '{{ $header }}')"></option>
                                        </optgroup>
                                        <optgroup label="Communication" class="bg-white">
                                            <option value="phone" :disabled="isOptionDisabled('phone', '{{ $header }}')" x-text="getOptionText('phone', 'Phone Number', '{{ $header }}')"></option>
                                            <option value="whatsapp_number" :disabled="isOptionDisabled('whatsapp_number', '{{ $header }}')" x-text="getOptionText('whatsapp_number', 'WhatsApp Number', '{{ $header }}')"></option>
                                            <option value="city" :disabled="isOptionDisabled('city', '{{ $header }}')" x-text="getOptionText('city', 'City', '{{ $header }}')"></option>
                                            <option value="state" :disabled="isOptionDisabled('state', '{{ $header }}')" x-text="getOptionText('state', 'State', '{{ $header }}')"></option>
                                            <option value="zip" :disabled="isOptionDisabled('zip', '{{ $header }}')" x-text="getOptionText('zip', 'Zip Code', '{{ $header }}')"></option>
                                            <option value="country" :disabled="isOptionDisabled('country', '{{ $header }}')" x-text="getOptionText('country', 'Country', '{{ $header }}')"></option>
                                            <option value="address" :disabled="isOptionDisabled('address', '{{ $header }}')" x-text="getOptionText('address', 'Address', '{{ $header }}')"></option>
                                        </optgroup>
                                        <optgroup label="Internal" class="bg-white">
                                            <option value="segment_name" :disabled="isOptionDisabled('segment_name', '{{ $header }}')" x-text="getOptionText('segment_name', 'Segment', '{{ $header }}')"></option>
                                            <option value="tags" :disabled="isOptionDisabled('tags', '{{ $header }}')" x-text="getOptionText('tags', 'Tags', '{{ $header }}')"></option>
                                            <option value="signup_source" :disabled="isOptionDisabled('signup_source', '{{ $header }}')" x-text="getOptionText('signup_source', 'Source', '{{ $header }}')"></option>
                                            @for($i = 1; $i <= 10; $i++)
                                                <option value="custom_{{ $i }}" :disabled="isOptionDisabled('custom_{{ $i }}', '{{ $header }}')" x-text="getOptionText('custom_{{ $i }}', 'Custom Field {{ $i }}', '{{ $header }}')"></option>
                                            @endfor
                                        </optgroup>
                                    </select>
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                        :class="mappings['{{ $header }}'] ? 'text-brand' : 'text-surface-300'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. Value Preview --}}
                            <div class="col-span-4 pl-4 overflow-hidden">
                                <div class="flex items-center gap-2 overflow-x-auto scrollbar">
                                    @foreach($samples as $sample)
                                        <span
                                            class="px-3 py-1 bg-gray-50 border border-gray-100 rounded-sm text-[10px] font-medium text-surface-600 whitespace-nowrap">
                                            {{ Str::limit($sample, 40) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Compact Action Footer --}}
                <div class="px-6 py-5 bg-surface-50 border-t border-color flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">At least "Email Address"
                            must be connected to proceed.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.email-lists.index') }}"
                            class="text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-900 transition-colors">Discard</a>
                        <button type="submit"
                            class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-10 py-3 rounded-sm hover:bg-black transition-all">
                            Import to Contacts
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mappingAssistant() {
            return {
                mappings: @json($initialMappings),
                
                isOptionDisabled(val, currentHeader) {
                    if (!val || val === '__custom__' || val === '') return false; // Allow multiple __custom__
                    // Check if this value is selected by ANY other header
                    return Object.entries(this.mappings).some(([header, selectedVal]) => {
                        return header !== currentHeader && selectedVal === val;
                    });
                },

                getOptionText(val, label, currentHeader) {
                    if (this.isOptionDisabled(val, currentHeader)) {
                        return label + ' (Mapped)';
                    }
                    return label;
                },

                validateAndSubmit() {
                    const emailMapped = Object.values(this.mappings).includes('email');

                    if (!emailMapped) {
                        alert('Primary Email column is required.');
                        return;
                    }

                    // Check for duplicates — but allow multiple __custom__ and '' (skip)
                    const selectedValues = Object.values(this.mappings).filter(v => v !== '' && v !== '__custom__');
                    const uniqueValues = [...new Set(selectedValues)];
                    if (selectedValues.length !== uniqueValues.length) {
                        alert('Duplicate mapping detected. Each system field can only be mapped once.');
                        return;
                    }

                    document.getElementById('mapping-form').submit();
                }
            };
        }
    </script>
@endsection