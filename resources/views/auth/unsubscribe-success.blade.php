<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed Successfully — Arzonet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-8 text-center animate-fade-in">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-surface-900 mb-3 tracking-tight">Preferences Updated</h1>
        
        @if(isset($email))
            <p class="text-surface-600 mb-4 leading-relaxed">
                The email address <strong class="text-surface-900 font-semibold">{{ $email->email }}</strong> has been unsubscribed <strong class="text-brand font-bold">{{ $durationText ?? 'permanently' }}</strong>.
            </p>
            @if(isset($expiresAt) && $expiresAt)
                <p class="text-xs text-brand font-medium bg-brand/5 border border-brand/10 rounded-lg p-3.5 mb-8 leading-relaxed">
                    ⏰ <strong>Temporary Snooze Active:</strong> You will be automatically re-subscribed and resume receiving updates on <strong>{{ \Carbon\Carbon::parse($expiresAt)->format('F d, Y') }}</strong>.
                </p>
            @else
                <p class="text-surface-500 text-xs mb-8">
                    You will no longer receive marketing or updates from this list. You can re-subscribe anytime.
                </p>
            @endif
        @else
            <p class="text-surface-600 mb-8 leading-relaxed">
                Your email has been successfully unsubscribed from this list.
            </p>
        @endif
        
        <div class="pt-4 border-t border-surface-150">
            <a href="/" class="btn btn-secondary w-full py-3 text-xs tracking-wider uppercase font-bold">Go to Homepage</a>
        </div>
        
        <div class="text-xs text-surface-400 mt-6">
            Arzonet v1.0 • Clean & Fast Emailing
        </div>
    </div>
</body>
</html>
