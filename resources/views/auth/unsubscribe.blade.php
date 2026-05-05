<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe — BulkMailer</title>
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
            <div class="space-y-3">
                <button type="submit" class="btn btn-primary w-full py-3">Yes, Unsubscribe Me</button>
                <a href="/" class="btn btn-ghost w-full py-3">No, Keep Me Subscribed</a>
            </div>
        </form>
        
        <p class="text-xs text-surface-400 mt-8">We're sorry to see you go. You can re-subscribe anytime via our website.</p>
    </div>
</body>
</html>
