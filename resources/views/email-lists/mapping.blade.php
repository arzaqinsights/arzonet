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
            foreach ($headers as $header) {
                $hasData = false;
                foreach ($sampleRows as $row) {
                    if (!empty($row[$header]) && trim($row[$header]) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData)
                    $activeHeaders[] = $header;
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
                            $isEmail = (str_contains(strtolower($header), 'email') || str_contains(strtolower($header), 'mail'));
                            $isName = (str_contains(strtolower($header), 'name'));
                            $initialValue = $isEmail ? 'email' : ($isName ? 'name' : '');

                            if (isset($autoSuggestions[$header])) {
                                $initialValue = $autoSuggestions[$header];
                            }

                            $samples = [];
                            foreach (array_slice($sampleRows, 0, 4) as $row) {
                                if (!empty($row[$header])) {
                                    $samples[] = $row[$header];
                                }
                            }
                        @endphp

                        <div class="grid grid-cols-12 gap-4 px-6 py-3 items-center hover:bg-surface-50 transition-colors"
                            x-data="{ selected: '{{ $initialValue }}' }">
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
                                    <select name="mapping[{{ $header }}]" x-model="selected"
                                        class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-100 rounded-sm font-bold text-[11px] uppercase tracking-wider transition-all appearance-none outline-none focus:border-brand"
                                        :class="selected ? 'text-surface-600 border-surface-600/30 bg-surface-600/5' : 'text-surface-400'">
                                        <option value="">— Skip Column —</option>
                                        <optgroup label="Main Identity" class="bg-white">
                                            <option value="email">Email Address *</option>
                                            <option value="name">Full Name</option>
                                            <option value="first_name">First Name</option>
                                            <option value="last_name">Last Name</option>
                                        </optgroup>
                                        <optgroup label="Corporate" class="bg-white">
                                            <option value="company">Company</option>
                                            <option value="job_title">Job Title</option>
                                            <option value="department">Department</option>
                                            <option value="industry">Industry</option>
                                            <option value="website">Website</option>
                                        </optgroup>
                                        <optgroup label="Communication" class="bg-white">
                                            <option value="phone">Phone Number</option>
                                            <option value="city">City</option>
                                            <option value="state">State</option>
                                            <option value="zip">Zip Code</option>
                                            <option value="country">Country</option>
                                            <option value="address">Address</option>
                                        </optgroup>
                                        <optgroup label="Internal" class="bg-white">
                                            <option value="segment_name">Segment</option>
                                            <option value="signup_source">Source</option>
                                            @for($i = 1; $i <= 10; $i++)
                                                <option value="custom_{{ $i }}">Custom Field {{ $i }}</option>
                                            @endfor
                                        </optgroup>
                                    </select>
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                        :class="selected ? 'text-brand' : 'text-surface-300'">
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
                validateAndSubmit() {
                    const form = document.getElementById('mapping-form');
                    const selects = form.querySelectorAll('select[name^="mapping"]');
                    let emailMapped = false;

                    selects.forEach(select => {
                        if (select.value === 'email') emailMapped = true;
                    });

                    if (!emailMapped) {
                        alert('Primary Email column is required.');
                        return;
                    }

                    form.submit();
                }
            };
        }
    </script>
@endsection