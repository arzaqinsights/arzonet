<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe — Arzonet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-8 text-center">
        <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-surface-900 mb-2">Unsubscribe</h1>
        <p class="text-surface-600 mb-8">Are you sure you want to unsubscribe <strong>{{ $email->email }}</strong> from this mailing list?</p>
        
        <form action="{{ route('unsubscribe.confirm', $email->id) }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="lid" value="{{ $logId }}">
            
            <div class="mb-6 text-left">
                <label class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-2">Unsubscribe Option</label>
                <select name="duration" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-sm font-semibold focus:outline-none focus:border-brand cursor-pointer">
                    <option value="forever">Permanently (Forever)</option>
                    <option value="1">Temporary Unsubscribe (1 Day)</option>
                    <option value="3">Temporary Unsubscribe (3 Days)</option>
                    <option value="7">Temporary Unsubscribe (7 Days)</option>
                    <option value="14">Temporary Unsubscribe (14 Days)</option>
                    <option value="30">Temporary Unsubscribe (30 Days)</option>
                    <option value="90">Temporary Unsubscribe (90 Days)</option>
                    <option value="365">Temporary Unsubscribe (1 Year)</option>
                </select>
            </div>

            <div class="space-y-3">
                <button type="submit" class="btn btn-primary w-full py-3">Yes, Unsubscribe Me</button>
                <a href="/" class="btn btn-ghost w-full py-3 text-center block">No, Keep Me Subscribed</a>
            </div>
        </form>
        
        <p class="text-xs text-surface-400 mt-8">We're sorry to see you go. You can re-subscribe anytime via our website.</p>
    </div>
</body>
</html>
