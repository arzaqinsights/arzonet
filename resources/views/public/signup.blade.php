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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* bg-gray-100 */
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-md bg-white border-t-4 shadow-md rounded-sm p-6 space-y-5" style="border-top-color: {{ $themeColor }}">
        
        <div>
            <h2 class="text-xl font-black text-gray-900">{{ $form ? $form->title : 'Join our newsletter' }}</h2>
            <p class="text-xs text-gray-500 mt-1.5">{{ $form ? $form->description : 'Subscribe to receive regular updates from ' . $list->name . '.' }}</p>
        </div>

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

            {{-- Render inputs based on configuration --}}
            @if($form)
                
                {{-- Email is always required --}}
                <div>
                    <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                    <input type="email" name="email" required value="{{ old('email') }}"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                        placeholder="you@domain.com">
                </div>

                {{-- Name --}}
                @if(in_array('name', $form->custom_fields ?? []))
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Full Name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="John Doe">
                    </div>
                @endif

                {{-- WhatsApp Number --}}
                @if(in_array('whatsapp_number', $form->custom_fields ?? []))
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-sm text-sm outline-none transition-all focus:border-gray-400 focus:ring-1 focus:ring-gray-400" 
                            placeholder="+1234567890">
                    </div>
                @endif

                {{-- Dynamic Custom Fields & Legacy List Fields --}}
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
                {{-- Legacy Layout: Name & Email --}}
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

            {{-- Topics Selection --}}
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

            <div>
                <button type="submit" style="background-color: {{ $themeColor }}"
                    class="w-full py-2.5 text-white text-xs font-black uppercase tracking-wider rounded-sm hover:brightness-95 active:scale-[0.98] transition-all duration-150 outline-none">
                    {{ $form ? $form->button_text : 'Subscribe Now' }}
                </button>
            </div>
        </form>
    </div>
    
    <div class="text-center mt-4">
        <p class="text-[10px] text-gray-400 font-medium">You can unsubscribe or change your preferences at any time.</p>
    </div>
</body>
</html>
