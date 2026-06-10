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

            {{-- Global Opt-out --}}
            <div class="mb-8 text-left border-t border-gray-150 pt-6">
                <label class="flex items-start gap-3 p-4 bg-red-50/20 border border-red-100 rounded-xl cursor-pointer hover:bg-red-50/40 transition-all select-none">
                    <input type="checkbox" id="global_unsubscribe" name="global_unsubscribe" value="1" 
                           class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500 focus:ring-offset-0"
                           {{ $email->subscription_status === 'unsubscribed' ? 'checked' : '' }}>
                    <div class="flex-1">
                        <div class="text-sm font-black text-red-700 leading-tight">Unsubscribe from all updates</div>
                        <div class="text-xs text-red-600 mt-1 leading-normal">Opt out of all emails from this list. You can update this preference at any time.</div>
                    </div>
                </label>
            </div>

            <div class="space-y-3">
                <button type="submit" class="btn btn-primary w-full py-3">Save Preferences</button>
                <a href="/" class="btn btn-ghost w-full py-3 text-center block">Cancel & Keep Subscriptions</a>
            </div>
        </form>
        
        <p class="text-xs text-surface-400 mt-8">We respect your inbox. You can update your email preferences or subscribe back at any time.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const globalCheck = document.getElementById('global_unsubscribe');
            const topicChecks = document.querySelectorAll('.topic-checkbox');
            const topicsSection = document.getElementById('topics-section');
            
            function updateState() {
                if (globalCheck && globalCheck.checked) {
                    topicChecks.forEach(cb => {
                        cb.disabled = true;
                        cb.parentElement.classList.add('opacity-50');
                    });
                    if (topicsSection) {
                        topicsSection.classList.add('opacity-60');
                    }
                } else {
                    topicChecks.forEach(cb => {
                        cb.disabled = false;
                        cb.parentElement.classList.remove('opacity-50');
                    });
                    if (topicsSection) {
                        topicsSection.classList.remove('opacity-60');
                    }
                }
            }
            
            if (globalCheck) {
                globalCheck.addEventListener('change', updateState);
                updateState();
            }
        });
    </script>
</body>
</html>
