@extends('layouts.app')
@section('title', 'Create Signup Form')
@section('heading', 'Create Signup Form')

@section('content')
<div class="animate-slide-up" x-data="{
    name: 'Newsletter Signup Form',
    title: 'Subscribe to Our Newsletter',
    description: 'Get the latest updates, promotions, and announcements sent directly to your inbox.',
    buttonText: 'Subscribe Now',
    themeColor: '#4f46e5',
    doubleOptIn: false,
    allowTopicSelection: false,
    tags: '',
    selectedFields: ['email'],
    selectedTopics: [],
    dynamicFields: [],
    customFieldsList: @js($customFields),
    topicsList: @js($topics),
    addDynamicField() {
        this.dynamicFields.push({ label: '', key: '', required: false });
    },
    removeDynamicField(index) {
        this.dynamicFields.splice(index, 1);
    },
    generateKey(label) {
        if (!label) return '';
        return 'custom_' + label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/(^_+|_+$)/g, '');
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {{-- Left Panel: Configuration --}}
        <div class="glass-card p-6">
            <form action="{{ route('admin.signup-forms.store') }}" method="POST" class="space-y-6">
                @csrf
                
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
                            <input type="text" name="success_message" placeholder="Thank you for subscribing!"
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

                    {{-- List-Specific Custom Fields --}}
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

                    {{-- New Custom Fields --}}
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

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <a href="{{ route('admin.signup-forms.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary px-6">Create Form</button>
                </div>
            </form>
        </div>

        {{-- Right Panel: Live Preview --}}
        <div class="flex flex-col justify-start">
            <div class="sticky top-24">
                <span class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Live Interactive Preview</span>
                <div class="bg-gray-100 border border-gray-200 rounded-sm p-8 flex items-center justify-center min-h-[480px]">
                    <div class="bg-white border-t-4 shadow-md rounded-sm w-full max-w-md p-6 space-y-5" :style="{ borderTopColor: themeColor }">
                        <div>
                            <h2 class="text-xl font-black text-surface-900" x-text="title"></h2>
                            <p class="text-xs text-surface-500 mt-1.5" x-text="description"></p>
                        </div>
                        
                        <div class="space-y-4">
                            {{-- Email Field --}}
                            <div>
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                                <input type="email" placeholder="you@domain.com" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Name Field --}}
                            <div x-show="selectedFields.includes('name')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Full Name *</label>
                                <input type="text" placeholder="John Doe" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- WhatsApp Number Field --}}
                            <div x-show="selectedFields.includes('whatsapp_number')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                                <input type="text" placeholder="+1234567890" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Phone Field --}}
                            <div x-show="selectedFields.includes('phone')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Phone</label>
                                <input type="text" placeholder="+1234567890" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Company Field --}}
                            <div x-show="selectedFields.includes('company')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Company</label>
                                <input type="text" placeholder="Acme Corp" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Job Title Field --}}
                            <div x-show="selectedFields.includes('job_title')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Job Title</label>
                                <input type="text" placeholder="CEO, Manager..." disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- City Field --}}
                            <div x-show="selectedFields.includes('city')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">City</label>
                                <input type="text" placeholder="New York" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Country Field --}}
                            <div x-show="selectedFields.includes('country')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Country</label>
                                <input type="text" placeholder="United States" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- List-specific Custom Fields --}}
                            <template x-for="f in customFieldsList" :key="f.key">
                                <div x-show="selectedFields.includes(f.key)">
                                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5" x-text="f.name"></label>
                                    <input type="text" :placeholder="'Enter ' + f.name" disabled
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                                </div>
                            </template>

                            {{-- Dynamic Custom Fields (new) --}}
                            <template x-for="f in dynamicFields" :key="f.key">
                                <div>
                                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">
                                        <span x-text="f.label || 'Custom Field'"></span>
                                        <span x-show="f.required" class="text-red-500">*</span>
                                    </label>
                                    <input type="text" :placeholder="'Enter ' + (f.label || 'details')" disabled
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                                </div>
                            </template>

                            {{-- Preview Topic Selection --}}
                            <div x-show="allowTopicSelection" class="space-y-3 pt-2">
                                <label class="text-[9px] font-black text-surface-500 uppercase tracking-widest block">Subscription Preferences</label>
                                <div class="space-y-2">
                                    <template x-for="t in (selectedTopics.length > 0 ? topicsList.filter(topic => selectedTopics.includes(String(topic.id))) : topicsList)" :key="t.id">
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

                        <div>
                            <button type="button" disabled class="w-full py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm transition-all"
                                :style="{ backgroundColor: themeColor }" x-text="buttonText"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
