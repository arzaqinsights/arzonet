@php
    $themeColor = $form ? $form->theme_color : '#4f46e5';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form ? $form->title : 'Subscribe to ' . $list->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-2xl">
        
        {{-- Theme Header Accent --}}
        <div class="h-2" style="background-color: {{ $themeColor }}"></div>

        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full text-white mb-4 font-extrabold text-xl shadow-sm" style="background-color: {{ $themeColor }}">
                    {{ strtoupper(substr($list->name, 0, 2)) }}
                </div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ $form ? $form->title : 'Join our newsletter' }}</h2>
                <p class="text-sm text-gray-500 mt-1.5">{{ $form ? $form->description : 'Subscribe to receive regular updates from ' . $list->name . '.' }}</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-sm font-semibold text-center animate-pulse">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-red-800 text-xs font-semibold">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('public.forms.submit', $form ? $form->token : $list->signup_form_token) }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="has_topics_field" value="1">

                {{-- Render inputs based on configuration --}}
                @if($form)
                    
                    {{-- Email is always required --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Email Address *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="e.g. jane@example.com">
                    </div>

                    {{-- Name --}}
                    @if(in_array('name', $form->custom_fields ?? []))
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Your Name *</label>
                            <input type="text" name="name" required value="{{ old('name') }}"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="e.g. Jane Doe">
                        </div>
                    @endif

                    {{-- WhatsApp Number --}}
                    @if(in_array('whatsapp_number', $form->custom_fields ?? []))
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                placeholder="e.g. +1234567890">
                        </div>
                    @endif

                    {{-- Dynamic List Custom Fields --}}
                    @foreach($form->custom_fields ?? [] as $fieldKey)
                        @if(str_starts_with($fieldKey, 'custom_'))
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">{{ $customFieldLabels[$fieldKey] ?? ucwords(str_replace('_', ' ', $fieldKey)) }}</label>
                                <input type="text" name="{{ $fieldKey }}" value="{{ old($fieldKey) }}"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                                    placeholder="Enter details...">
                            </div>
                        @endif
                    @endforeach

                @else
                    {{-- Legacy Layout: Name & Email --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Your Name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" 
                            placeholder="e.g. Jane Doe">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Email Address *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" 
                            placeholder="e.g. jane@example.com">
                    </div>
                @endif

                {{-- Topics Selection - Show checkboxes only if topics are set on the list and NOT preset on custom form --}}
                @if(!$form && count($topics) > 0)
                    <div class="space-y-3 pt-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Subscription Preferences</label>
                        <div class="space-y-2.5">
                            @foreach($topics as $topic)
                                <label class="flex items-start gap-3 p-3 bg-gray-50 hover:bg-gray-100/80 rounded-xl border border-gray-100 cursor-pointer transition-colors">
                                    <input type="checkbox" name="topics[]" value="{{ $topic->id }}" checked class="mt-1 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
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

                <button type="submit" style="background-color: {{ $themeColor }}"
                    class="w-full py-4 text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg hover:brightness-95 active:scale-[0.98] transition-all duration-150 mt-4 outline-none">
                    {{ $form ? $form->button_text : 'Subscribe Now' }}
                </button>
            </form>
        </div>
        <div class="px-8 py-4 bg-gray-50/80 border-t border-gray-100 text-center">
            <p class="text-[10px] text-gray-400 font-medium">You can unsubscribe or change your preferences at any time.</p>
        </div>
    </div>
</body>
</html>
