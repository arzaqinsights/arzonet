@extends('layouts.app')
@section('title', 'Edit Signup Form')
@section('heading', 'Edit Signup Form')

@section('content')
<div class="animate-slide-up" x-data="{
    name: @js($signupForm->name),
    title: @js($signupForm->title),
    description: @js($signupForm->description),
    buttonText: @js($signupForm->button_text),
    themeColor: @js($signupForm->theme_color),
    doubleOptIn: @js($signupForm->double_opt_in),
    selectedFields: @js($signupForm->custom_fields ?? ['email']),
    selectedTopics: @js(array_map('strval', $signupForm->subscribed_topics ?? [])),
    customFieldsList: @js($customFields),
    topicsList: @js($topics)
}">
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
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Fields to Display</h3>
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" checked disabled class="rounded border-gray-300 text-brand">
                            <span class="text-xs font-bold text-gray-400">Email Address (Required)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="name" x-model="selectedFields" id="field_name"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_name" class="text-xs font-bold text-gray-700 cursor-pointer">Name</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="custom_fields[]" value="whatsapp_number" x-model="selectedFields" id="field_whatsapp"
                                class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                            <label for="field_whatsapp" class="text-xs font-bold text-gray-700 cursor-pointer">WhatsApp Number</label>
                        </div>
                        
                        @if(!empty($customFields))
                            <div class="h-px bg-gray-100 my-2"></div>
                            <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Custom Fields (List specific)</span>
                            @foreach($customFields as $field)
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="custom_fields[]" value="{{ $field['key'] }}" x-model="selectedFields" id="field_{{ $field['key'] }}"
                                        class="rounded border-gray-300 text-brand focus:ring-0 cursor-pointer">
                                    <label for="field_{{ $field['key'] }}" class="text-xs font-bold text-gray-700 cursor-pointer">{{ $field['name'] }}</label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Topics Auto-Enrollment</h3>
                    <p class="text-[10px] text-gray-500 mb-3">Subscribers joining through this form will be automatically subscribed to selected topics below.</p>
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
                    <button type="submit" class="btn btn-primary px-6">Save Changes</button>
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
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">Full Name</label>
                                <input type="text" placeholder="John Doe" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- WhatsApp Number Field --}}
                            <div x-show="selectedFields.includes('whatsapp_number')">
                                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                                <input type="text" placeholder="+1234567890" disabled
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                            </div>

                            {{-- Custom Fields --}}
                            <template x-for="f in customFieldsList" :key="f.key">
                                <div x-show="selectedFields.includes(f.key)">
                                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-1.5" x-text="f.name"></label>
                                    <input type="text" :placeholder="'Enter ' + f.name" disabled
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none">
                                </div>
                            </template>
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
