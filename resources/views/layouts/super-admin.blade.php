<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin — Arzonet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-brand: #ff6b00;
            --color-admin-bg: #0f172a;
        }
    </style>
</head>

<body x-data="{ sidebarOpen: true }" class="min-h-screen bg-slate-50 font-['Inter']">

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-[#0f172a] text-white transition-all duration-300 z-50 overflow-hidden flex flex-col shadow-2xl">
        <div class="p-6 flex items-center gap-3 border-b border-slate-800">
            <div class="w-8 h-8 bg-brand rounded-sm flex items-center justify-center shrink-0">
                <i class="fa-solid fa-shield-halved text-white text-xs"></i>
            </div>
            <span x-show="sidebarOpen" class="font-black text-lg tracking-tighter font-['Outfit']">SUPER ADMIN</span>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route('admin.super.dashboard') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('admin.super.dashboard') ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'text-slate-400' }}">
                <i class="fa-solid fa-gauge-high w-5"></i>
                <span x-show="sidebarOpen" class="text-sm font-bold">Platform Overview</span>
            </a>
            <a href="{{ route('admin.super.users') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('admin.super.users') ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'text-slate-400' }}">
                <i class="fa-solid fa-users w-5"></i>
                <span x-show="sidebarOpen" class="text-sm font-bold">User Management</span>
            </a>
            <a href="{{ route('admin.super.settings') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm hover:bg-slate-800 transition-colors {{ request()->routeIs('admin.super.settings') ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'text-slate-400' }}">
                <i class="fa-solid fa-coins w-5"></i>
                <span x-show="sidebarOpen" class="text-sm font-bold">Pricing Rules</span>
            </a>
            <a href="#" class="flex items-center gap-4 px-4 py-3 rounded-sm hover:bg-slate-800 transition-colors text-slate-400">
                <i class="fa-solid fa-clock-rotate-left w-5"></i>
                <span x-show="sidebarOpen" class="text-sm font-bold">System Logs</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-4 px-4 py-3 rounded-sm bg-slate-800/50 hover:bg-slate-800 text-slate-300 transition-colors">
                <i class="fa-solid fa-arrow-right-from-bracket w-5"></i>
                <span x-show="sidebarOpen" class="text-xs font-bold uppercase tracking-widest">User Dashboard</span>
            </a>
        </div>
    </aside>

    {{-- Main Content --}}
    <main :class="sidebarOpen ? 'ml-64' : 'ml-20'" class="transition-all duration-300">
        {{-- Header --}}
        <header class="h-20 bg-white border-b border-slate-200 sticky top-0 z-40 px-8 flex items-center justify-between shadow-sm">
            <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-bars-staggered text-xl"></i>
            </button>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-black text-slate-900 leading-tight">Master Admin</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Platform Owner</p>
                    </div>
                    <div class="w-10 h-10 rounded-sm bg-slate-900 flex items-center justify-center text-white font-black">
                        M
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8">
            @yield('content')
        </div>
    </main>

    <footer :class="sidebarOpen ? 'ml-64' : 'ml-20'" class="p-8 text-center text-slate-400 text-[10px] font-bold uppercase tracking-widest transition-all duration-300">
        Arzonet Platform Management — v2.1.0-Master
    </footer>

</body>

</html>
