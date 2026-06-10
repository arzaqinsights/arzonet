<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed</title>
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
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden text-center transition-all duration-300 hover:shadow-2xl">
        <div class="p-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 mb-6 font-extrabold text-xl shadow-sm border border-emerald-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Subscription Confirmed!</h2>
            <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                Thank you, <strong>{{ $contact->name }}</strong>. Your subscription has been verified successfully.
            </p>
            <p class="text-xs text-gray-400 mt-4">
                You are now subscribed and will begin receiving updates from our newsletters.
            </p>
            
            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-[10px] text-gray-400 font-medium">To manage your preferences or unsubscribe, click the link at the bottom of any future email.</p>
            </div>
        </div>
    </div>
</body>
</html>
