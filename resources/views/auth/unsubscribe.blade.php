<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences — Arzonet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full p-8 text-center">
        <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-surface-900 mb-2">Email Preferences</h1>
        <p class="text-surface-600 mb-8 text-sm">Manage what emails you receive at <strong class="text-surface-900">{{ $email->email }}</strong>, or opt out completely.</p>
        
        <form action="{{ route('unsubscribe.confirm', $email->id) }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="lid" value="{{ $logId }}">
            
            {{-- Topic Preferences --}}
            @if($topics->isNotEmpty())
                <div id="topics-section" class="mb-6 text-left space-y-4 transition-opacity duration-300">
                    <label class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-2">Subscription Topics</label>
                    <div class="space-y-3">
                        @foreach($topics as $topic)
                            @php
                                $isSubscribed = is_null($email->subscribed_topics) || in_array($topic->id, $email->subscribed_topics);
                            @endphp
                            <label class="flex items-start gap-3 p-3.5 bg-white border border-gray-200/60 rounded-xl cursor-pointer hover:border-gray-300 hover:bg-gray-50/30 transition-all select-none">
                                <input type="checkbox" name="topics[]" value="{{ $topic->id }}" 
                                       class="topic-checkbox mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
                                       {{ $isSubscribed ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-gray-900 leading-snug">{{ $topic->name }}</div>
                                    @if($topic->description)
                                        <div class="text-xs text-gray-500 mt-1 leading-normal">{{ $topic->description }}</div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-3">
                <button type="submit" class="btn btn-primary w-full py-3">Save Preferences</button>
            </div>
        </form>
        
        <p class="text-xs text-surface-400 mt-8">We respect your inbox. You can update your email preferences or subscribe back at any time.</p>
    </div>

</body>
</html>
