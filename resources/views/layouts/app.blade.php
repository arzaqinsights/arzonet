<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Arzonet</title>
    <meta name="description" content="Advanced Bulk Email Sending Platform">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body x-data class="min-h-screen flex flex-col" style="background: var(--color-surface-50);">

    {{-- ── Global Navbar ── --}}
    <nav class="sticky top-0 z-50 w-full bg-white border-b border-color py-3 flex items-center justify-between">
        <div class="flex items-center justify-between w-full gap-4 px-4">

            <div class="flex items-center gap-6">
                <button @click="$store.sidebar.toggle()"
                    class="p-1.5 rounded-sm hover:bg-gray-100 text-gray-500 transition-colors lg:hidden cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo/logo.png') }}" class="h-8 md:h-10 shrink-0 object-contain">
                </a>
            </div>

            <div class="flex items-center gap-3 md:gap-5">

                {{-- Global Search (mock) --}}
                <div
                    class="hidden md:flex items-center bg-gray-50 border border-gray-200 rounded-sm px-3 py-3 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand/30 transition-all">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search..."
                        class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-75 placeholder-gray-400 outline-none text-gray-700">
                    <!-- <span class="text-[10px] text-gray-400 border border-gray-200 rounded-sm px-1.5 py-0.5 ml-2 font-bold tracking-widest">CTRL+K</span> -->
                </div>

                {{-- Action Icons --}}
                <div class="flex items-center text-gray-500">
                    <button
                        class="p-2 px-3 hover:bg-gray-100 transition-colors tooltip-trigger border-r border-gray-200 cursor-pointer"
                        title="Help & Documentation">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <button
                        class="p-2 px-3 hover:bg-gray-100 transition-colors tooltip-trigger border-r border-gray-200 relative cursor-pointer"
                        title="Notifications">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                    </button>
                    <a href="{{ route('admin.settings.index') }}"
                        class="p-2 pl-3 hover:bg-gray-100 transition-colors tooltip-trigger hidden sm:block cursor-pointer"
                        title="Settings">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>
                </div>

                {{-- User Profile Dropdown --}}
                <div x-data="{ open: false }" class="relative ml-2">
                    <button @click="open = !open" @click.away="open = false"
                        class="flex items-center gap-3 hover:bg-gray-50 p-1.5 rounded-sm transition-colors text-left cursor-pointer">
                        <div class="hidden md:block text-right">
                            <p class="text-sm font-bold text-gray-900 leading-tight">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] font-semibold text-gray-500 tracking-wider">
                                {{ auth()->user()->email ?? 'Admin' }}
                            </p>
                        </div>
                        <div class="w-9 h-9 rounded-sm flex items-center justify-center text-lg font-black text-white shadow-sm"
                            style="background: var(--color-brand);">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <!-- <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg> -->
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-70 bg-white border border-gray-200 rounded-sm shadow-lg py-1 z-50"
                        style="display: none;">

                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Signed in as</p>
                            <p class="text-sm font-bold text-gray-900 truncate mt-0.5">{{ auth()->user()->email }}</p>
                        </div>

                        <div class="py-1">
                            <a href="#"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                My Profile
                            </a>
                            <a href="#"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Billing & Plan
                            </a>
                        </div>

                        <div class="border-t border-gray-100 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-semibold cursor-pointer">
                                    <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1 overflow-hidden relative">
        {{-- ── Sidebar ── --}}
        <aside x-show="$store.sidebar.open" x-transition:enter="transition-transform duration-300"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed left-0 z-40 w-[260px] h-full pb-20 flex flex-col justify-between border-r border-color bg-white">

            {{-- Workspace Selector (Top) --}}
            <!-- <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between group cursor-pointer hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-sm bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-sm border border-blue-100">
                        A
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-bold text-gray-900 truncate">Arzonet Workspace</p>
                        <p class="text-[10px] text-gray-500 font-semibold tracking-wider uppercase">Pro Plan</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
            </div> -->

            {{-- Navigation --}}
            <nav class="p-3 overflow-y-auto scrollbar">
                @php
                    $sidebarMenu = [
                        'Overview' => [
                            [
                                'title' => 'Dashboard',
                                'route' => 'admin.dashboard',
                                'active' => 'admin.dashboard',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />',
                            ],
                        ],
                        'Campaigns' => [
                            [
                                'title' => 'Campaigns',
                                'route' => 'admin.campaigns.index',
                                'active' => 'admin.campaigns.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />',
                            ],
                            [
                                'title' => 'Templates',
                                'route' => 'admin.templates.index',
                                'active' => 'admin.templates.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />',
                            ],
                        ],
                        'Audience' => [
                            [
                                'title' => 'Email Lists',
                                'route' => 'admin.email-lists.index',
                                'active' => 'admin.email-lists.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                            ],
                            [
                                'title' => 'Blacklist',
                                'route' => 'admin.blacklist.index',
                                'active' => 'admin.blacklist.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />',
                            ],
                        ],
                        'Settings' => [
                            [
                                'title' => 'Senders',
                                'route' => 'admin.senders.index',
                                'active' => 'admin.senders.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />',
                            ],
                            [
                                'title' => 'Settings',
                                'route' => 'admin.settings.index',
                                'active' => 'admin.settings.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                            ],
                            [
                                'title' => 'Team',
                                'route' => 'admin.users.index',
                                'active' => 'admin.users.*',
                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />',
                            ],
                        ],
                    ];
                @endphp

                @foreach($sidebarMenu as $section => $links)
                    <!-- <p class="px-3.5 my-3 text-[10px] flex items-center gap-2 font-bold text-gray-400 tracking-wider uppercase">{{ $section }} <span class="w-full h-px bg-gray-200"></span></p> -->
                    @foreach($links as $link)
                        <a href="{{ route($link['route']) }}"
                            class="flex items-center p-2 mb-2 rounded-sm text-sm gap-2 {{ request()->routeIs($link['active']) ? 'bg-surface-200 text-surface-900 font-semibold' : 'text-surface-800 hover:bg-surface-100' }}">
                            <svg class="w-4 h-4 shrink-0"
                                fill="{{ request()->routeIs($link['active']) ? 'currentColor' : 'none' }}" stroke="currentColor"
                                viewBox="0 0 24 24">
                                {!! $link['icon'] !!}
                            </svg>
                            {{ $link['title'] }}
                        </a>
                    @endforeach
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                <div
                    class="bg-linear-to-r from-surface-50 to-surface-100 border rounded-sm p-3 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-12 h-12 bg-surface-800 rounded-full opacity-50"></div>
                    <p class="text-sm font-bold text-surface-900">Need more features?</p>
                    <p class="text-[10px] text-surface-700 mt-0.5 mb-3">Upgrade to enterprise for unlimited sending.</p>
                    <a href="#"
                        class="text-xs font-bold text-white bg-surface-900 hover:bg-surface-800 px-2.5 py-2 rounded-sm inline-block transition-colors shadow-sm">Upgrade
                        Plan</a>
                </div>
                <div class="flex items-center justify-between mt-3 text-[10px] text-gray-400 font-medium px-1">
                    <span>Arzonet v2.1.0</span>
                    <a href="#" class="hover:text-gray-600 transition-colors">Changelog</a>
                </div>
            </div>
        </aside>

        {{-- ── Main Content ── --}}
        <main class="w-full ml-[260px] overflow-y-auto bg-surface-100">
            @if(View::hasSection('heading') || View::hasSection('header-actions'))
                <div
                    class="fixed top-18 z-40 left-[260px] w-[calc(100%-260px)] flex justify-between items-center bg-surface-0 px-6 py-4 border-b border-color">
                    @if(View::hasSection('heading'))
                        <h1 class="font-black text-lg">@yield('heading')</h1>
                    @endif
                    @if(View::hasSection('header-actions'))
                        @yield('header-actions')
                    @endif
                </div>
            @endif

            {{-- Flash Messages --}}
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)" x-transition
                    class="toast border-l-4 m-6 mb-0"
                    style="border-left-color: #16a34a; position: relative; bottom: auto; right: auto; max-width: none; animation: none;">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                        style="background: #f0fdf4; color: #16a34a;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">Success</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 cursor-pointer flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
                    class="toast border-l-4 m-6 mb-0"
                    style="border-left-color: #dc2626; position: relative; bottom: auto; right: auto; max-width: none; animation: none;">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                        style="background: #fef2f2; color: #dc2626;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">Error</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 cursor-pointer flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Page Content --}}
            <div class="p-6 {{ (View::hasSection('heading') || View::hasSection('header-actions')) ? 'pt-24' : '' }}">
                @yield('content')
            </div>
        </main>

        {{-- Mobile Overlay --}}
        <div x-show="$store.sidebar.open" @click="$store.sidebar.toggle()" x-transition.opacity
            class="fixed inset-0 bg-black/20 z-30 lg:hidden"></div>
    </div>
</body>

</html>