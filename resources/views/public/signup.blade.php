@php
    $themeColor = $form ? $form->theme_color : '#4f46e5';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form ? $form->title : 'Subscribe to ' . $list->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: {{ request('widget') ? 'transparent' : '#f3f4f6' }};
        }
    </style>
</head>
<body class="{{ request('widget') ? 'p-2 flex flex-col items-center' : 'min-h-screen flex flex-col items-center justify-center p-6' }}" x-data="{
    currentStep: 0,
    isMultiStep: @js($form && !empty($form->steps)),
    steps: @js($form ? ($form->steps ?? []) : []),
    validateStep() {
        let inputs = this.$el.querySelectorAll('[data-step=\'' + this.currentStep + '\'] input, [data-step=\'' + this.currentStep + '\'] textarea, [data-step=\'' + this.currentStep + '\'] select');
        let isValid = true;
        inputs.forEach(input => {
            if (!input.reportValidity()) {
                isValid = false;
            }
        });
        return isValid;
    },
    trackProgress(customStep = null) {
        if (!this.isMultiStep) return;
        let emailInput = this.$el.querySelector('input[name=\'email\']');
        let emailValue = emailInput ? emailInput.value : '';
        let stepToRecord = customStep !== null ? customStep : this.currentStep;
        
        fetch('{{ $form ? route('public.forms.progress', $form->token) : '' }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: '{{ $sessionId ?? '' }}',
                step: stepToRecord,
                email: emailValue
            })
        }).catch(err => console.error('Error tracking progress:', err));
    },
    nextStep() {
        if (this.validateStep()) {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                this.trackProgress(this.currentStep);
            }
        }
    },
    prevStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
        }
    }
}">
    <div class="{{ request('widget') ? 'w-full max-w-md bg-white p-4 space-y-4' : 'w-full max-w-md bg-white border-t-4 shadow-md rounded-sm p-6 space-y-5' }}" style="{{ request('widget') ? '' : 'border-top-color: ' . $themeColor }}">
        
        <div>
            <h2 class="text-xl font-black text-gray-900">{{ $form ? $form->title : 'Join our newsletter' }}</h2>
            <p class="text-xs text-gray-500 mt-1.5">{{ $form ? $form->description : 'Subscribe to receive regular updates from ' . $list->name . '.' }}</p>
        </div>

        @if($form && !empty($form->steps))
            <div class="space-y-2 pb-2 border-b border-gray-100" x-show="isMultiStep">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider" style="color: {{ $themeColor }}">Step <span x-text="currentStep + 1"></span> of {{ count($form->steps) }}</span>
                    <span class="text-xs font-black text-gray-900" x-text="steps[currentStep]?.title || 'Step'"></span>
                </div>
                <template x-if="steps[currentStep]?.description">
                    <p class="text-[10px] text-gray-500" x-text="steps[currentStep]?.description"></p>
                </template>
                <div class="w-full bg-gray-100 h-1 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300" :style="{ width: (((currentStep + 1) / steps.length) * 100) + '%', backgroundColor: '{{ $themeColor }}' }"></div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-sm text-emerald-800 text-xs font-bold text-center">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 bg-red-50 border border-red-100 rounded-sm text-red-800 text-xs font-semibold">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('public.forms.submit', $form ? $form->token : $list->signup_form_token) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="has_topics_field" value="1">
            @if($form && isset($sessionId))
                <input type="hidden" name="form_session_id" value="{{ $sessionId }}">
            @endif

            @if($form && !empty($form->steps))
                {{-- Multi-step Layout --}}
                @foreach($form->steps as $step)
                    <div x-show="currentStep === {{ $loop->index }}" data-step="{{ $loop->index }}" class="space-y-4">
                        @foreach($step['fields'] ?? [] as $fieldKey)
                            @if($fieldKey === 'email')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                                    <input type="email" name="email" required value="{{ old('email') }}" @blur="trackProgress()"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="you@domain.com">
                                </div>
                            @elseif($fieldKey === 'name')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Full Name *</label>
                                    <input type="text" name="name" required value="{{ old('name') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="John Doe">
                                </div>
                            @elseif($fieldKey === 'whatsapp_number')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="+1234567890">
                                </div>
                            @elseif($fieldKey === 'phone')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="+1234567890">
                                </div>
                            @elseif($fieldKey === 'company')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Company</label>
                                    <input type="text" name="company" value="{{ old('company') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="Acme Corp">
                                </div>
                            @elseif($fieldKey === 'job_title')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Job Title</label>
                                    <input type="text" name="job_title" value="{{ old('job_title') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="CEO, Manager...">
                                </div>
                            @elseif($fieldKey === 'city')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">City</label>
                                    <input type="text" name="city" value="{{ old('city') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="New York">
                                </div>
                            @elseif($fieldKey === 'country')
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Country</label>
                                    <input type="text" name="country" value="{{ old('country') }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="United States">
                                </div>
                            @else
                                {{-- Render List-Specific or Dynamic Custom Field --}}
                                @php
                                    $dynField = null;
                                    foreach($form->custom_fields ?? [] as $cf) {
                                        if (is_array($cf) && ($cf['key'] ?? '') === $fieldKey) {
                                            $dynField = $cf;
                                            break;
                                        }
                                    }
                                @endphp
                                @if($dynField)
                                    @php
                                        $fieldLabel = $dynField['label'] ?? '';
                                        $required = !empty($dynField['required']) && ($dynField['required'] === '1' || $dynField['required'] === true);
                                    @endphp
                                    <div>
                                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">
                                            {{ $fieldLabel }} {!! $required ? '<span class="text-red-500">*</span>' : '' !!}
                                        </label>
                                        <input type="text" name="{{ $fieldKey }}" {{ $required ? 'required' : '' }} value="{{ old($fieldKey) }}"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                            placeholder="Enter {{ strtolower($fieldLabel) }}...">
                                    </div>
                                @elseif(str_starts_with($fieldKey, 'custom_'))
                                    <div>
                                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">{{ $customFieldLabels[$fieldKey] ?? ucwords(str_replace('_', ' ', $fieldKey)) }}</label>
                                        <input type="text" name="{{ $fieldKey }}" value="{{ old($fieldKey) }}"
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                            placeholder="Enter details...">
                                    </div>
                                @endif
                            @endif
                        @endforeach

                        {{-- If topics should be displayed on this step --}}
                        @if(!empty($step['show_topics']) && (($form && $form->allow_topic_selection) || (!$form && count($topics) > 0)))
                            <div class="space-y-3 pt-2">
                                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest block">Subscription Preferences</label>
                                <div class="space-y-2">
                                    @php
                                        $formTopics = array_map('strval', $form ? ($form->subscribed_topics ?? []) : []);
                                        $displayTopics = $form ? (empty($formTopics) ? $topics : $topics->filter(fn($t) => in_array((string)$t->id, $formTopics))) : $topics;
                                    @endphp
                                    @foreach($displayTopics as $topic)
                                        <label class="flex items-start gap-2.5 p-2.5 bg-gray-50 border border-gray-200 rounded-sm cursor-pointer hover:bg-gray-100/80 transition-colors">
                                            <input type="checkbox" name="topics[]" value="{{ $topic->id }}" checked 
                                                class="mt-0.5 w-3.5 h-3.5 rounded border-gray-300 focus:ring-0" style="accent-color: {{ $themeColor }}">
                                            <div>
                                                <p class="text-xs font-bold text-gray-800 leading-none">{{ $topic->name }}</p>
                                                @if($topic->description)
                                                    <p class="text-[10px] text-gray-550 mt-1 leading-normal">{{ $topic->description }}</p>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                {{-- Single-page Layout (existing logic) --}}
                @if($form)
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="you@domain.com">
                    </div>

                    @if(in_array('name', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Full Name *</label>
                            <input type="text" name="name" required value="{{ old('name') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="John Doe">
                        </div>
                    @endif

                    @if(in_array('whatsapp_number', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="+1234567890">
                        </div>
                    @endif

                    @if(in_array('phone', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="+1234567890">
                        </div>
                    @endif

                    @if(in_array('company', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Company</label>
                            <input type="text" name="company" value="{{ old('company') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="Acme Corp">
                        </div>
                    @endif

                    @if(in_array('job_title', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Job Title</label>
                            <input type="text" name="job_title" value="{{ old('job_title') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="CEO, Manager...">
                        </div>
                    @endif

                    @if(in_array('city', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">City</label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="New York">
                        </div>
                    @endif

                    @if(in_array('country', $form->custom_fields ?? []))
                        <div>
                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Country</label>
                            <input type="text" name="country" value="{{ old('country') }}"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="United States">
                        </div>
                    @endif

                    @foreach($form->custom_fields ?? [] as $field)
                        @if(is_array($field))
                            @php
                                $fieldKey = $field['key'] ?? '';
                                $fieldLabel = $field['label'] ?? '';
                                $required = !empty($field['required']) && ($field['required'] === '1' || $field['required'] === true);
                            @endphp
                            @if($fieldKey)
                                <div>
                                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">
                                        {{ $fieldLabel }} {!! $required ? '<span class="text-red-500">*</span>' : '' !!}
                                    </label>
                                    <input type="text" name="{{ $fieldKey }}" {{ $required ? 'required' : '' }} value="{{ old($fieldKey) }}"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                        placeholder="Enter {{ strtolower($fieldLabel) }}...">
                                </div>
                            @endif
                        @elseif(is_string($field) && str_starts_with($field, 'custom_'))
                            <div>
                                <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">{{ $customFieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field)) }}</label>
                                <input type="text" name="{{ $field }}" value="{{ old($field) }}"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                    placeholder="Enter details...">
                            </div>
                        @endif
                    @endforeach
                @else
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Your Name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="John Doe">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="you@domain.com">
                    </div>
                @endif

                @if(($form && $form->allow_topic_selection) || (!$form && count($topics) > 0))
                    <div class="space-y-3 pt-2">
                        <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest block">Subscription Preferences</label>
                        <div class="space-y-2">
                            @php
                                $formTopics = array_map('strval', $form ? ($form->subscribed_topics ?? []) : []);
                                $displayTopics = $form ? (empty($formTopics) ? $topics : $topics->filter(fn($t) => in_array((string)$t->id, $formTopics))) : $topics;
                            @endphp
                            @foreach($displayTopics as $topic)
                                <label class="flex items-start gap-2.5 p-2.5 bg-gray-50 border border-gray-200 rounded-sm cursor-pointer hover:bg-gray-100/80 transition-colors">
                                    <input type="checkbox" name="topics[]" value="{{ $topic->id }}" checked 
                                        class="mt-0.5 w-3.5 h-3.5 rounded border-gray-300 focus:ring-0" style="accent-color: {{ $themeColor }}">
                                    <div>
                                        <p class="text-xs font-bold text-gray-800 leading-none">{{ $topic->name }}</p>
                                        @if($topic->description)
                                            <p class="text-[10px] text-gray-500 mt-1 leading-normal">{{ $topic->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            @if($form && !empty($form->steps))
                <div class="flex gap-2 pt-4 border-t border-gray-100" x-show="isMultiStep">
                    <button type="button" @click="prevStep()" x-show="currentStep > 0"
                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-xs font-bold uppercase tracking-wider rounded-sm bg-white hover:bg-gray-50 cursor-pointer">Back</button>
                    <button type="button" @click="nextStep()" x-show="currentStep < steps.length - 1"
                        class="flex-1 py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all cursor-pointer"
                        style="background-color: {{ $themeColor }}">Next</button>
                    <button type="submit" x-show="currentStep === steps.length - 1"
                        class="flex-1 py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all duration-150 cursor-pointer"
                        style="background-color: {{ $themeColor }}">
                        {{ $form ? $form->button_text : 'Subscribe Now' }}
                    </button>
                </div>
            @else
                <div>
                    <button type="submit" style="background-color: {{ $themeColor }}"
                        class="w-full py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm hover:brightness-95 active:scale-[0.98] transition-all duration-150 outline-none">
                        {{ $form ? $form->button_text : 'Subscribe Now' }}
                    </button>
                </div>
            @endif
        </form>
    </div>
    
    <div class="text-center mt-4">
        <p class="text-[10px] text-gray-400 font-medium">You can unsubscribe or change your preferences at any time.</p>
    </div>
</body>
</html>
