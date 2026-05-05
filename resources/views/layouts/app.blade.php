<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Arzonet</title>
    <meta name="description" content="Advanced Bulk Email Sending Platform">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body x-data class="min-h-screen flex bg-surface-50">    {{-- ── Sidebar ── --}}
    <aside
        x-show="$store.sidebar.open"
        x-transition:enter="transition-transform duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed lg:sticky top-0 left-0 z-40 w-72 h-screen bg-white border-r border-surface-200 flex flex-col overflow-y-auto sidebar"
    >
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-8 py-8">
            <div class="w-11 h-11 rounded-xl bg-white flex items-center justify-center shadow-sm border border-surface-100 overflow-hidden">
                <img src="{{ asset('images/logo/logo.png') }}" alt="Arzonet Logo" class="w-full h-full object-contain p-1">
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-surface-900 tracking-tight font-outfit">Arzonet</h1>
                <p class="text-[10px] font-bold text-primary-500 uppercase tracking-[0.2em]">Enterprise</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-4 py-4 space-y-1">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>

            <a href="{{ route('contacts.index') }}" class="sidebar-link {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Contacts (CRM)
            </a>

            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-8 mb-3 px-4">Management</p>

            <a href="{{ route('email-lists.index') }}" class="sidebar-link {{ request()->routeIs('email-lists.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Email Lists
            </a>

            <a href="{{ route('templates.index') }}" class="sidebar-link {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                Templates
            </a>

            <a href="{{ route('campaigns.index') }}" class="sidebar-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                Campaigns
            </a>

            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mt-8 mb-3 px-4">System Settings</p>

            <a href="{{ route('blacklist.index') }}" class="sidebar-link {{ request()->routeIs('blacklist.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Blacklist
            </a>

            <a href="{{ route('senders.index') }}" class="sidebar-link {{ request()->routeIs('senders.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                Sender Emails
            </a>

            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924-1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>

            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Team Access
            </a>
        </nav>

        {{-- Footer --}}
        @auth
        <div class="px-8 py-6 border-t border-surface-100 bg-surface-50/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-[10px] font-bold text-primary-700">
                    {{ substr(auth()->user()->name, 0, 2) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-surface-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-surface-500 truncate capitalize">{{ auth()->user()->role }} Account</p>
                </div>
            </div>
        </div>
        @endauth
    </aside>

    {{-- ── Main Content ── --}}
    <main class="flex-1 min-h-screen flex flex-col overflow-x-hidden">
        {{-- Header --}}
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-surface-200 px-8 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <button @click="$store.sidebar.toggle()" class="p-2 -ml-2 rounded-lg hover:bg-surface-100 text-surface-500 transition-colors cursor-pointer lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-2xl font-extrabold text-surface-900 tracking-tight font-outfit">@yield('heading', 'Dashboard')</h2>
                </div>
                <div class="flex items-center gap-4">
                    @yield('header-actions')
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="toast border-l-4 border-emerald-500">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-surface-700">{{ session('success') }}</p>
                    <button @click="show = false" class="ml-auto text-surface-400 hover:text-surface-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="toast border-l-4 border-red-500">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-surface-700">{{ session('error') }}</p>
                    <button @click="show = false" class="ml-auto text-surface-400 hover:text-surface-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Page Content --}}
        <div class="flex-1 p-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
