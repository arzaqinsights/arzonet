@extends('layouts.app')
@section('title', 'Edit Signup Form')
@section('heading', 'Edit Signup Form')

@push('head')
<style>
    main {
        overflow: visible !important;
    }
</style>
@endpush

@section('content')
<script>
    window.formConfig = {
        name: @js($signupForm->name),
        title: @js($signupForm->title),
        description: @js($signupForm->description),
        buttonText: @js($signupForm->button_text),
        themeColor: @js($signupForm->theme_color),
        doubleOptIn: @js((bool)$signupForm->double_opt_in),
        allowTopicSelection: @js((bool)$signupForm->allow_topic_selection),
        tags: @js(is_array($signupForm->tags) ? implode(', ', $signupForm->tags) : ''),
        selectedFields: @js(array_values(array_filter($signupForm->custom_fields ?? ['email'], 'is_string'))),
        selectedTopics: @js(array_map('strval', $signupForm->subscribed_topics ?? [])),
        dynamicFields: @js(array_values(array_filter($signupForm->custom_fields ?? [], 'is_array'))),
        customFieldsList: @js($customFields),
        topicsList: @js($topics),
        isMultiStep: @js(!empty($signupForm->steps)),
        steps: @js($signupForm->steps ?? [])
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('formBuilder', (config) => ({
            name: config.name,
            title: config.title,
            description: config.description,
            buttonText: config.buttonText,
            themeColor: config.themeColor,
            doubleOptIn: config.doubleOptIn,
            allowTopicSelection: config.allowTopicSelection,
            tags: config.tags,
            selectedFields: config.selectedFields,
            selectedTopics: config.selectedTopics,
            dynamicFields: config.dynamicFields,
            customFieldsList: config.customFieldsList,
            topicsList: config.topicsList,
            isMultiStep: config.isMultiStep,
            steps: config.steps,
            previewStepIndex: 0,
            previewBg: 'light',

            addStep() {
                let defaultFields = [];
                let emailAlreadyAssigned = this.steps.some(s => s.fields && s.fields.includes('email'));
                if (!emailAlreadyAssigned) {
                    defaultFields.push('email');
                }
                this.steps.push({ title: 'Step ' + (this.steps.length + 1), description: '', fields: defaultFields, show_topics: false });
            },
            removeStep(index) {
                this.steps.splice(index, 1);
                this.previewStepIndex = Math.min(this.previewStepIndex, Math.max(0, this.steps.length - 1));
            },
            moveStepUp(index) {
                if (index === 0) return;
                let temp = this.steps[index];
                this.steps[index] = this.steps[index - 1];
                this.steps[index - 1] = temp;
                this.steps = [...this.steps];
                if (this.previewStepIndex === index) this.previewStepIndex--;
                else if (this.previewStepIndex === index - 1) this.previewStepIndex++;
            },
            moveStepDown(index) {
                if (index === this.steps.length - 1) return;
                let temp = this.steps[index];
                this.steps[index] = this.steps[index + 1];
                this.steps[index + 1] = temp;
                this.steps = [...this.steps];
                if (this.previewStepIndex === index) this.previewStepIndex++;
                else if (this.previewStepIndex === index + 1) this.previewStepIndex--;
            },
            getFieldAssignmentStep(key, currentStepIndex) {
                let stepNum = null;
                this.steps.forEach((s, i) => {
                    if (i !== currentStepIndex && s.fields && s.fields.includes(key)) {
                        stepNum = i + 1;
                    }
                });
                return stepNum;
            },
            getValidationErrors() {
                let errors = [];
                if (!this.isMultiStep) return errors;

                if (this.steps.length === 0) {
                    errors.push('At least one step must be defined.');
                    return errors;
                }

                this.steps.forEach((s, idx) => {
                    let hasFields = s.fields && s.fields.length > 0;
                    let hasTopics = this.allowTopicSelection && s.show_topics;
                    if (!hasFields && !hasTopics) {
                        errors.push(`Step ${idx + 1} ("${s.title}") must have at least one field or show topic preferences.`);
                    }
                });

                let emailAssigned = false;
                this.steps.forEach(s => {
                    if (s.fields && s.fields.includes('email')) {
                        emailAssigned = true;
                    }
                });
                if (!emailAssigned) {
                    errors.push('The Email Address field is required and must be assigned to at least one step.');
                }

                let unassigned = this.getUnassignedFields();
                if (unassigned.length > 0) {
                    errors.push(`The following active fields are not assigned to any step: ${unassigned.map(f => f.label).join(', ')}.`);
                }

                return errors;
            },
            getFormFields() {
                let fields = [{ key: 'email', label: 'Email Address' }];
                this.selectedFields.forEach(key => {
                    if (key === 'email') return;
                    let customField = this.customFieldsList.find(f => f.key === key);
                    let label = customField ? customField.name : this.capitalize(key);
                    fields.push({ key: key, label: label });
                });
                this.dynamicFields.forEach(f => {
                    if (f.key) {
                        fields.push({ key: f.key, label: f.label || 'Custom Field' });
                    }
                });
                return fields;
            },
            getUnassignedFields() {
                let fields = this.getFormFields();
                let assignedKeys = [];
                this.steps.forEach(s => {
                    if (s.fields) {
                        assignedKeys = assignedKeys.concat(s.fields);
                    }
                });
                return fields.filter(f => !assignedKeys.includes(f.key));
            },
            isFieldVisibleInPreview(key) {
                if (!this.isMultiStep) return true;
                let currentStep = this.steps[this.previewStepIndex];
                if (!currentStep) return false;
                return currentStep.fields && currentStep.fields.includes(key);
            },
            isTopicsVisibleInPreview() {
                if (!this.isMultiStep) return this.allowTopicSelection;
                let currentStep = this.steps[this.previewStepIndex];
                if (!currentStep) return false;
                return this.allowTopicSelection && currentStep.show_topics;
            },
            capitalize(str) {
                return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            },
            addDynamicField() {
                this.dynamicFields.push({ label: '', key: '', required: false });
            },
            removeDynamicField(index) {
                this.dynamicFields.splice(index, 1);
            },
            generateKey(label) {
                if (!label) return '';
                return 'custom_' + label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/(^_+|_+$)/g, '');
            },
            hasUnassignedFields() {
                return this.getUnassignedFields().length > 0;
            },
            getUnassignedFieldsLabel() {
                return this.getUnassignedFields().map(f => f.label).join(', ');
            },
            hasValidationErrors() {
                return this.isMultiStep && this.getValidationErrors().length > 0;
            },
            getFilteredTopics() {
                if (this.selectedTopics.length > 0) {
                    return this.topicsList.filter(topic => this.selectedTopics.includes(String(topic.id)));
                }
                return this.topicsList;
            },
            prevPreviewStep() {
                if (this.previewStepIndex > 0) {
                    this.previewStepIndex--;
                }
            },
            nextPreviewStep() {
                if (this.previewStepIndex < this.steps.length - 1) {
                    this.previewStepIndex++;
                }
            },
            hasNextPreviewStep() {
                return this.previewStepIndex < this.steps.length - 1;
            },
            getFieldMeta(key) {
                if (key === 'email') {
                    return { key: 'email', label: 'Email Address', required: true, placeholder: 'you@domain.com' };
                }
                let standardFields = {
                    name: { label: 'Full Name', required: true, placeholder: 'John Doe' },
                    whatsapp_number: { label: 'WhatsApp Number', required: false, placeholder: '+1234567890' },
                    phone: { label: 'Phone', required: false, placeholder: '+1234567890' },
                    company: { label: 'Company', required: false, placeholder: 'Acme Corp' },
                    job_title: { label: 'Job Title', required: false, placeholder: 'CEO, Manager...' },
                    city: { label: 'City', required: false, placeholder: 'New York' },
                    country: { label: 'Country', required: false, placeholder: 'United States' }
                };
                if (standardFields[key]) {
                    return {
                        key: key,
                        label: standardFields[key].label,
                        required: standardFields[key].required,
                        placeholder: standardFields[key].placeholder
                    };
                }
                let customField = this.customFieldsList.find(f => f.key === key);
                if (customField) {
                    return {
                        key: key,
                        label: customField.name,
                        required: false,
                        placeholder: 'Enter ' + customField.name
                    };
                }
                let dynField = this.dynamicFields.find(f => f.key === key);
                if (dynField) {
                    return {
                        key: key,
                        label: dynField.label || 'Custom Field',
                        required: dynField.required,
                        placeholder: 'Enter ' + (dynField.label || 'details')
                    };
                }
                return null;
            },
            getPreviewFields() {
                if (this.isMultiStep) {
                    let currentStep = this.steps[this.previewStepIndex];
                    if (currentStep && currentStep.fields) {
                        return currentStep.fields.map(key => this.getFieldMeta(key)).filter(f => f !== null);
                    }
                    return [];
                } else {
                    let fields = [{ key: 'email', label: 'Email Address', required: true, placeholder: 'you@domain.com' }];
                    this.selectedFields.forEach(key => {
                        if (key === 'email') return;
                        let meta = this.getFieldMeta(key);
                        if (meta) fields.push(meta);
                    });
                    this.dynamicFields.forEach(f => {
                        if (f.key) {
                            fields.push({ key: f.key, label: f.label || 'Custom Field', required: f.required, placeholder: 'Enter ' + (f.label || 'details') });
                        }
                    });
                    return fields;
                }
            },
            moveFieldUp(stepIndex, fieldIndex) {
                if (fieldIndex === 0) return;
                let fields = this.steps[stepIndex].fields;
                let temp = fields[fieldIndex];
                fields[fieldIndex] = fields[fieldIndex - 1];
                fields[fieldIndex - 1] = temp;
                this.steps[stepIndex].fields = [...fields];
            },
            moveFieldDown(stepIndex, fieldIndex) {
                let fields = this.steps[stepIndex].fields;
                if (fieldIndex === fields.length - 1) return;
                let temp = fields[fieldIndex];
                fields[fieldIndex] = fields[fieldIndex + 1];
                fields[fieldIndex + 1] = temp;
                this.steps[stepIndex].fields = [...fields];
            }
        }));
    });
</script>

<div class="animate-slide-up" x-data="formBuilder(window.formConfig)">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- Left Panel: Configuration --}}
        <div class="glass-card p-6">
            <form action="{{ route('admin.signup-forms.update', $signupForm) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Form Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Form Name (Internal)</label>
                            <input type="text" name="name" x-model="name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Form Title (Public)</label>
                            <input type="text" name="title" x-model="title" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Description (Public)</label>
                            <textarea name="description" x-model="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Form Actions & Customization</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Button Text</label>
                                <input type="text" name="button_text" x-model="buttonText" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Theme Color</label>
                                <div class="flex gap-2">
                                    <input type="color" name="theme_color" x-model="themeColor" required
                                        class="w-10 h-9 p-0.5 border border-gray-300 rounded-sm cursor-pointer outline-none">
                                    <input type="text" x-model="themeColor" readonly
                                        class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none bg-gray-50">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Success Message</label>
                            <input type="text" name="success_message" value="{{ $signupForm->success_message }}" placeholder="Thank you for subscribing!"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="double_opt_in" value="1" x-model="doubleOptIn" id="double_opt_in"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="double_opt_in" class="text-xs font-bold text-gray-700 cursor-pointer">Enable Double Opt-In confirmation email</label>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Auto-Assign Tags (Comma-separated)</label>
                            <input type="text" name="tags" x-model="tags" placeholder="e.g. Lead, Form Signup"
                                class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Fields to Display</h3>
                    
                    {{-- Standard Fields --}}
                    <div class="space-y-2.5">
                        <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-2">Standard Fields</span>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" checked disabled class="rounded border-gray-300 text-brand">
                            <span class="text-xs font-bold text-gray-400">Email Address (Required)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="name" x-model="selectedFields" id="field_name"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_name" class="text-xs font-bold text-gray-700 cursor-pointer">Full Name</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="whatsapp_number" x-model="selectedFields" id="field_whatsapp"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_whatsapp" class="text-xs font-bold text-gray-700 cursor-pointer">WhatsApp Number</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="phone" x-model="selectedFields" id="field_phone"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_phone" class="text-xs font-bold text-gray-700 cursor-pointer">Phone</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="company" x-model="selectedFields" id="field_company"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_company" class="text-xs font-bold text-gray-700 cursor-pointer">Company</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="job_title" x-model="selectedFields" id="field_job_title"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_job_title" class="text-xs font-bold text-gray-700 cursor-pointer">Job Title</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="city" x-model="selectedFields" id="field_city"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_city" class="text-xs font-bold text-gray-700 cursor-pointer">City</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="country" x-model="selectedFields" id="field_country"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_country" class="text-xs font-bold text-gray-700 cursor-pointer">Country</label>
                        </div>
                    </div>

                    {{-- List-Specific Custom Fields (from imports & past form submissions) --}}
                    @if(!empty($customFields))
                        <div class="mt-4">
                            <div class="h-px bg-gray-100 mb-3"></div>
                            <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-2.5">List Custom Fields</span>
                            <div class="space-y-2.5">
                                @foreach($customFields as $field)
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="custom_fields[]" value="{{ $field['key'] }}" x-model="selectedFields" id="field_{{ $field['key'] }}"
                                            class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                                        <label for="field_{{ $field['key'] }}" class="text-xs font-bold text-gray-700 cursor-pointer">{{ $field['name'] }}</label>
                                        <span class="text-[9px] text-gray-400 font-mono">{{ $field['key'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Dynamic / New Custom Fields --}}
                    <div class="mt-5">
                        <div class="h-px bg-gray-100 mb-3"></div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">New Custom Fields</span>
                            <button type="button" @click="addDynamicField()"
                                class="px-2.5 py-1.5 bg-brand/5 hover:bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm border border-brand/20 transition-all flex items-center gap-1 cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                Add Field
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                            <template x-for="(field, index) in dynamicFields" :key="index">
                                <div class="bg-gray-50 border border-gray-200 rounded-sm p-3 space-y-3 relative animate-slide-up">
                                    <button type="button" @click="removeDynamicField(index)"
                                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-wider mb-1">Field Label</label>
                                            <input type="text" x-model="field.label" @input="field.key = generateKey(field.label)" required placeholder="e.g. Company Name"
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-sm text-xs font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-wider mb-1">Field Key (Auto)</label>
                                            <input type="text" x-model="field.key" readonly
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-sm text-xs font-bold text-gray-400 bg-gray-100 outline-none font-mono">
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" x-model="field.required" :id="'req_' + index"
                                            class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                                        <label :for="'req_' + index" class="text-xs font-bold text-gray-700 cursor-pointer">Required Field</label>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="dynamicFields.length === 0">
                                <div class="text-center py-4 border border-dashed border-gray-200 rounded-sm bg-gray-50/50">
                                    <p class="text-xs text-gray-400 font-medium">Click "Add Field" to add a new custom field to this form.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Hidden Fields for form submit --}}
                <template x-for="(field, index) in dynamicFields" :key="'submit_' + index">
                    <div>
                        <input type="hidden" :name="'custom_fields[dyn_' + index + '][key]'" :value="field.key">
                        <input type="hidden" :name="'custom_fields[dyn_' + index + '][label]'" :value="field.label">
                        <input type="hidden" :name="'custom_fields[dyn_' + index + '][required]'" :value="field.required ? '1' : '0'">
                    </div>
                </template>

                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Topics Configuration</h3>
                    
                    <div class="flex items-center gap-2 mb-4">
                        <input type="checkbox" name="allow_topic_selection" value="1" x-model="allowTopicSelection" id="allow_topic_selection"
                            class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                        <label for="allow_topic_selection" class="text-xs font-bold text-gray-700 cursor-pointer">Allow subscriber to choose their own topics (from selected topics below)</label>
                    </div>

                    <p class="text-[10px] text-gray-500 mb-3">Selected topics for the form:</p>
                    <div class="space-y-2.5">
                        @foreach($topics as $topic)
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="subscribed_topics[]" value="{{ $topic->id }}" x-model="selectedTopics" id="topic_{{ $topic->id }}"
                                    class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                                <label for="topic_{{ $topic->id }}" class="text-xs font-bold text-gray-700 cursor-pointer">{{ $topic->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Multi-Step Wizard Configuration --}}
                <div class="border-t border-gray-100 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider pb-1">Multi-Step Form Wizard</h3>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_multi_step" value="1" id="is_multi_step" x-model="isMultiStep" @change="if(isMultiStep && steps.length === 0) addStep()"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="is_multi_step" class="text-xs font-bold text-gray-700 cursor-pointer">Enable Steps</label>
                        </div>
                    </div>

                    <div x-show="isMultiStep" class="space-y-6 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Form Steps Configuration</span>
                            <button type="button" @click="addStep()"
                                class="px-2.5 py-1.5 bg-brand/5 hover:bg-brand/10 text-brand text-[10px] font-black uppercase tracking-widest rounded-sm border border-brand/20 transition-all flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                Add Step
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(step, sIdx) in steps" :key="sIdx">
                                <div class="bg-gray-50 border border-gray-200 rounded-sm p-4 space-y-4 relative animate-slide-up">
                                    {{-- Reordering & Delete Controls --}}
                                    <div class="absolute top-3 right-3 flex items-center gap-1.5 bg-gray-100/70 p-0.5 rounded-sm">
                                        <button type="button" @click="moveStepUp(sIdx)" :disabled="sIdx === 0"
                                            class="text-gray-400 hover:text-brand transition-colors disabled:opacity-30 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" /></svg>
                                        </button>
                                        <button type="button" @click="moveStepDown(sIdx)" :disabled="sIdx === steps.length - 1"
                                            class="text-gray-400 hover:text-brand transition-colors disabled:opacity-30 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                        <button type="button" @click="removeStep(sIdx)" x-show="steps.length > 1"
                                            class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>

                                    <span class="block text-[10px] font-black text-brand uppercase tracking-wider">Step <span x-text="sIdx + 1"></span></span>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-wider mb-1">Step Title</label>
                                            <input type="text" x-model="step.title" required placeholder="e.g. Contact Info"
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-sm text-xs font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-500 uppercase tracking-wider mb-1">Step Description</label>
                                            <input type="text" x-model="step.description" placeholder="e.g. Enter your email and name"
                                                class="w-full px-2 py-1.5 border border-gray-300 rounded-sm text-xs font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none bg-white">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-wider mb-2">Assign Fields to this Step</label>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                            <template x-for="f in getFormFields()" :key="f.key">
                                                <label :class="getFieldAssignmentStep(f.key, sIdx) ? 'opacity-60 cursor-not-allowed bg-gray-100/50' : 'bg-white cursor-pointer hover:bg-gray-50'" 
                                                    class="flex items-center justify-between p-2 border border-gray-200 rounded-sm transition-colors min-h-[38px]">
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <input type="checkbox" :value="f.key" x-model="step.fields" :disabled="getFieldAssignmentStep(f.key, sIdx) !== null"
                                                            class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer disabled:cursor-not-allowed">
                                                        <span class="text-xs font-bold text-gray-700 truncate" x-text="f.label"></span>
                                                    </div>
                                                    <template x-if="getFieldAssignmentStep(f.key, sIdx) !== null">
                                                        <span class="text-[8px] font-black uppercase text-amber-700 bg-amber-50 border border-amber-200/50 px-1 py-0.5 rounded-sm whitespace-nowrap" x-text="'Step ' + getFieldAssignmentStep(f.key, sIdx)"></span>
                                                    </template>
                                                </label>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Field Reordering controls --}}
                                    <div x-show="step.fields && step.fields.length > 0" class="space-y-2 pt-2 border-t border-gray-150 animate-slide-up">
                                        <span class="block text-[9px] font-black text-gray-500 uppercase tracking-wider">Field Rendering Order (Click arrows to reorder)</span>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(fKey, fIdx) in step.fields" :key="fKey">
                                                <div class="inline-flex items-center gap-1 bg-white border border-gray-200 px-2 py-1 rounded-sm text-xs font-bold text-gray-700 shadow-sm">
                                                    <span x-text="getFieldMeta(fKey)?.label"></span>
                                                    <div class="flex items-center gap-0.5 ml-1.5 border-l border-gray-200 pl-1.5 shrink-0">
                                                        <button type="button" @click="moveFieldUp(sIdx, fIdx)" :disabled="fIdx === 0"
                                                            class="text-gray-400 hover:text-brand disabled:opacity-30 cursor-pointer p-0.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                                        </button>
                                                        <button type="button" @click="moveFieldDown(sIdx, fIdx)" :disabled="fIdx === step.fields.length - 1"
                                                            class="text-gray-400 hover:text-brand disabled:opacity-30 cursor-pointer p-0.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" x-model="step.show_topics" :id="'step_topics_' + sIdx"
                                            class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                                        <label :for="'step_topics_' + sIdx" class="text-xs font-bold text-gray-700 cursor-pointer">Show Topic Preferences on this step</label>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Hidden input to submit steps configuration as JSON --}}
                        <input type="hidden" name="steps_json" :value="JSON.stringify(steps)">

                        <div class="bg-amber-50 border border-amber-200 rounded-sm p-3 flex items-start gap-2" x-show="hasUnassignedFields()">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 text-xs"></i>
                            <div>
                                <h4 class="text-xs font-black text-amber-800 uppercase tracking-wider">Unassigned Fields</h4>
                                <p class="text-[10px] text-amber-700 font-medium mt-0.5">The following active fields are not assigned to any step: <span class="font-bold font-mono" x-text="getUnassignedFieldsLabel()"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Validation Errors Display --}}
                <div class="bg-red-50 border border-red-200 rounded-sm p-4 space-y-2 animate-slide-up" x-show="hasValidationErrors()" x-cloak>
                    <div class="flex items-center gap-2 text-red-800">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h4 class="text-xs font-black uppercase tracking-wider">Please resolve form errors before saving</h4>
                    </div>
                    <ul class="list-disc pl-5 text-[11px] text-red-700 font-medium space-y-1">
                        <template x-for="err in getValidationErrors()">
                            <li x-text="err"></li>
                        </template>
                    </ul>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <a href="{{ route('admin.signup-forms.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" 
                        :disabled="isMultiStep && getValidationErrors().length > 0"
                        :class="isMultiStep && getValidationErrors().length > 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        class="btn btn-primary px-6">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- Right Panel: Live Preview --}}
        <div class="sticky top-24 self-start">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="block text-[10px] font-black text-gray-500 uppercase tracking-widest">Live Interactive Preview</span>
                    <div class="flex items-center gap-1.5 bg-gray-150 p-1 rounded-sm text-[10px] font-bold">
                        <span class="text-gray-400 mr-1 pl-1">BG Style:</span>
                        <button type="button" @click="previewBg = 'light'" :class="previewBg === 'light' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'" class="px-2 py-0.5 rounded-sm transition-all cursor-pointer">Light</button>
                        <button type="button" @click="previewBg = 'dark'" :class="previewBg === 'dark' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'" class="px-2 py-0.5 rounded-sm transition-all cursor-pointer">Dark</button>
                        <button type="button" @click="previewBg = 'gradient'" :class="previewBg === 'gradient' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'" class="px-2 py-0.5 rounded-sm transition-all cursor-pointer">Gradient</button>
                    </div>
                </div>
                
                {{-- Mock Browser Window Wrapper --}}
                <div class="border border-gray-200/80 rounded-lg overflow-hidden shadow-lg transition-all duration-300" 
                    :class="{ 'bg-gray-100': previewBg === 'light', 'bg-slate-900/60': previewBg === 'dark', 'bg-white/40 backdrop-blur-md': previewBg === 'gradient' }">
                    
                    {{-- Browser Header Bar --}}
                    <div class="bg-white/90 border-b border-gray-200/60 px-4 py-2.5 flex items-center gap-3">
                        <div class="flex gap-1.5 shrink-0">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-400 block"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 block"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 block"></span>
                        </div>
                        <div class="flex-1 bg-gray-100/80 rounded-md py-1 px-3 text-[10px] text-gray-400 font-mono select-none flex items-center justify-between">
                            <span class="truncate">https://arzonet.email/forms/live-preview</span>
                            <i class="fa-solid fa-lock text-[8px] text-emerald-500"></i>
                        </div>
                    </div>
                    
                    {{-- Mock Browser Content Area --}}
                    <div class="p-8 flex items-center justify-center min-h-[440px] transition-all"
                        :class="{
                            'bg-gray-50': previewBg === 'light',
                            'bg-slate-950': previewBg === 'dark',
                            'bg-gradient-to-tr from-indigo-500/20 via-purple-500/15 to-pink-500/20': previewBg === 'gradient'
                        }">
                        
                        {{-- Actual Form Card --}}
                        <div class="bg-white border-t-4 shadow-xl rounded-sm w-full max-w-sm p-6 space-y-5 transition-all duration-300"
                            :style="{ borderTopColor: themeColor }">
                            <div>
                                <h2 class="text-xl font-black text-surface-900" x-text="title"></h2>
                                <p class="text-xs text-surface-500 mt-1.5" x-text="description"></p>
                            </div>
                            
                            {{-- Stepper Progress Bar (Multi-Step) --}}
                            <template x-if="isMultiStep && steps.length > 0">
                                <div class="space-y-2 pb-2 border-b border-gray-100 animate-slide-up">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black uppercase tracking-wider" :style="{ color: themeColor }">Step <span x-text="previewStepIndex + 1"></span> of <span x-text="steps.length"></span></span>
                                        <span class="text-xs font-black text-surface-900" x-text="steps[previewStepIndex]?.title || 'Step Title'"></span>
                                    </div>
                                    <p class="text-[10px] text-surface-500" x-text="steps[previewStepIndex]?.description || ''"></p>
                                    
                                    {{-- Visually expanded stepper line pill dots --}}
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <template x-for="(s, sIdx) in steps" :key="sIdx">
                                            <span class="h-1 rounded-full transition-all duration-300"
                                                :style="{ 
                                                    width: sIdx === previewStepIndex ? '20px' : '6px',
                                                    backgroundColor: sIdx === previewStepIndex ? themeColor : '#e5e7eb'
                                                }"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            
                            {{-- Dynamically Sorted Form Fields --}}
                            <div class="space-y-4">
                                <template x-for="field in getPreviewFields()" :key="field.key">
                                    <div class="animate-slide-up">
                                        <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">
                                            <span x-text="field.label"></span>
                                            <span x-show="field.required" class="text-red-500">*</span>
                                        </label>
                                        <input type="text" :placeholder="field.placeholder" disabled
                                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                                    </div>
                                </template>
                                
                                {{-- Empty Step Placeholder --}}
                                <template x-if="isMultiStep && getPreviewFields().length === 0 && !isTopicsVisibleInPreview()">
                                    <div class="text-center py-6 border border-dashed border-gray-200 rounded-sm bg-gray-50/50 animate-slide-up">
                                        <p class="text-xs text-gray-400 font-medium">No fields or preferences assigned to this step yet.</p>
                                    </div>
                                </template>

                                {{-- Preview Topic Selection --}}
                                <div x-show="isTopicsVisibleInPreview()" class="space-y-3 pt-2">
                                    <label class="text-[9px] font-black text-surface-500 uppercase tracking-widest block">Subscription Preferences</label>
                                    <div class="space-y-2">
                                        <template x-for="t in getFilteredTopics()" :key="t.id">
                                            <div class="flex items-start gap-2.5 p-2.5 bg-gray-50 border border-gray-200 rounded-sm">
                                                <input type="checkbox" checked disabled class="mt-0.5 w-3.5 h-3.5 text-brand rounded border-gray-300">
                                                <div>
                                                    <p class="text-xs font-bold text-gray-800 leading-none" x-text="t.name"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Navigation / Submit buttons in preview --}}
                            <div class="flex gap-2 pt-4 border-t border-gray-50" x-show="isMultiStep">
                                <button type="button" @click="prevPreviewStep()" :disabled="previewStepIndex === 0"
                                    class="flex-1 py-2 border border-gray-200 text-gray-750 text-xs font-bold uppercase tracking-wider rounded-sm bg-white hover:bg-gray-50 disabled:opacity-40 cursor-pointer">Back</button>
                                <button type="button" @click="nextPreviewStep()"
                                    x-show="hasNextPreviewStep()"
                                    class="flex-1 py-2 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all cursor-pointer"
                                    :style="{ backgroundColor: themeColor }">Next</button>
                                <button type="button" disabled
                                    x-show="!hasNextPreviewStep()"
                                    class="flex-1 py-2 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all"
                                    :style="{ backgroundColor: themeColor }" x-text="buttonText"></button>
                            </div>
                            <div x-show="!isMultiStep">
                                <button type="button" disabled class="w-full py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all"
                                    :style="{ backgroundColor: themeColor }" x-text="buttonText"></button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
