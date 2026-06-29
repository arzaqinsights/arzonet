<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Suspended — Arzonet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('images/logo/square-logo.png')}}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        :root {
            --color-brand: #ff6b00;
        }
    </style>
</head>
<body class="min-h-screen bg-[#0f172a] text-white flex items-center justify-center p-6 font-['Inter']">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-sm p-8 text-center shadow-2xl relative overflow-hidden">
        {{-- Glow background --}}
        <div class="absolute -right-20 -top-20 w-48 h-48 bg-brand/10 rounded-full blur-3xl"></div>
        
        {{-- Icon --}}
        <div class="w-16 h-16 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-ban text-2xl"></i>
        </div>

        {{-- Heading --}}
        <h1 class="text-2xl font-black font-['Outfit'] tracking-tight uppercase mb-3 text-white">Account Suspended</h1>
        
        {{-- Custom Suspension Message --}}
        <div class="bg-slate-950/50 border border-slate-800 rounded p-4 mb-8 text-sm text-slate-300 leading-relaxed font-medium text-left">
            {{ $reason ?? 'Your account has been suspended due to system policy violations. Please contact administration.' }}
        </div>

        {{-- Logout Button --}}
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit" class="w-full py-4 bg-brand hover:bg-[#e05638] text-white text-xs font-black uppercase tracking-widest rounded-sm transition-all cursor-pointer">
                Logout
            </button>
        </form>
    </div>
</body>
</html>
