<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Arzonet</title>
    <meta name="description" content="Advanced Bulk Email Sending Platform">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="shortcut icon" href="{{ asset('images/logo/square-logo.png')}}" type="image/x-icon">
    <link
        href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&family=Righteous&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('head')
</head>

<body x-data class="min-h-screen flex flex-col">

    {{-- ── Global Navbar ── --}}
    <nav class="sticky top-0 z-50 w-full bg-white border-b border-color py-2 flex items-center justify-between">
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
                    <img src="{{ asset('images/logo/logo.png') }}" class="h-7 md:h-9 shrink-0 object-contain">
                </a>
            </div>

            <div class="flex w-3/4 items-center gap-3 md:gap-5">

                {{-- Global Search (mock) --}}
                <div
                    class="hidden md:flex items-center bg-brand/10 rounded-full px-5 py-2 w-full focus-within:border-brand focus-within:ring-1 focus-within:ring-brand/30 transition-all">
                    <svg class="w-4 h-4 text-brand/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search here..."
                        class="bg-transparent border-none focus:ring-0 text-base ml-2 w-75 placeholder-brand/50 outline-none text-brand">
                    <!-- <span class="text-[10px] text-gray-400 border border-gray-200 rounded-sm px-1.5 py-0.5 ml-2 font-bold tracking-widest">CTRL+K</span> -->
                </div>

                {{-- Action Icons --}}
                <div class="flex items-center text-gray-500">
                    <button
                        class="p-2 px-3 hover:bg-gray-100 transition-colors tooltip-trigger border-r border-gray-200 cursor-pointer"
                        title="Help & Documentation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <button
                        class="p-2 px-3 hover:bg-gray-100 transition-colors tooltip-trigger border-r border-gray-200 relative cursor-pointer"
                        title="Notifications">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                    </button>
                    <a href="{{ route('admin.settings.index') }}"
                        class="p-2 pl-3 hover:bg-gray-100 transition-colors tooltip-trigger hidden sm:block cursor-pointer"
                        title="Settings">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <p class="text-sm font-semibold text-gray-900 leading-tight text-nowrap">
                                {{ app()->has('team_user') ? app('team_user')->name : auth()->user()->name }}
                            </p>
                            <p class="text-xs text-gray-500 tracking-wider">
                                {{ Str::limit(app()->has('team_user') ? app('team_user')->email : (auth()->user()->email ?? 'Admin'), 15) }}
                            </p>
                        </div>
                        <div class="w-9 h-9 rounded-sm flex items-center justify-center text-lg font-black text-white shadow-sm"
                            style="background: var(--color-brand);">
                            {{ strtoupper(substr(app()->has('team_user') ? app('team_user')->name : auth()->user()->name, 0, 1)) }}
                        </div>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 min-w-70 bg-white border border-gray-200 rounded-sm shadow-lg py-1 z-50"
                        style="display: none;">

                        <div
                            class="px-4 py-3 border-b border-gray-200 bg-gray-50/50 flex items-center gap-3 hover:bg-gray-50 p-1.5 rounded-sm transition-colors text-left cursor-pointer">
                            <div class="w-9 h-9 rounded-sm flex items-center justify-center text-lg font-black text-white shadow-sm"
                                style="background: var(--color-brand);">
                                {{ strtoupper(substr(app()->has('team_user') ? app('team_user')->name : auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="">
                                <p class="text-base font-semibold text-gray-900 leading-tight">
                                    {{ app()->has('team_user') ? app('team_user')->name : auth()->user()->name }}
                                </p>
                                <p class="text-xs text-gray-500 tracking-wider">
                                    {{ app()->has('team_user') ? app('team_user')->email : (auth()->user()->email ?? 'Admin') }}
                                </p>
                            </div>
                        </div>

                        <div class="py-1">
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.super.dashboard') }}"
                                    class="flex items-center px-4 py-2 text-sm text-brand font-black hover:bg-brand/5 transition-colors border-b border-brand/10">
                                    <i class="fa-solid fa-shield-halved mr-3"></i>
                                    Super Admin Panel
                                </a>
                            @endif
                            <a href="{{ route('admin.profile.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                My Profile
                            </a>
                            @if(\App\Models\User::canAccess('domains.view'))
                            <a href="{{ route('admin.domains.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <i class="fa-solid fa-globe w-4 h-4 mr-3 text-gray-400"></i>
                                Domains
                            </a>
                            @endif
                            @if(\App\Models\User::canAccess('senders.view'))
                            <a href="{{ route('admin.senders.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <i class="fa-solid fa-envelope w-4 h-4 mr-3 text-gray-400"></i>
                                Sender Emails
                            </a>
                            @endif
                            @if(\App\Models\User::canAccess('settings.view'))
                            <a href="{{ route('admin.settings.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <i class="fa-solid fa-gear w-4 h-4 mr-3 text-gray-400"></i>
                                Settings
                            </a>
                            @endif
                            @if(!app()->has('team_user'))
                            <a href="{{ route('admin.users.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <i class="fa-solid fa-users w-4 h-4 mr-3 text-gray-400"></i>
                                Team
                            </a>
                            @endif
                            @if(\App\Models\User::canAccess('billing.view'))
                            <a href="{{ route('admin.billing.plans') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Billing & Plan
                            </a>
                            <a href="{{ route('admin.billing.invoices.index') }}"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition-colors">
                                <i class="fa-solid fa-file-invoice w-4 h-4 mr-3 text-gray-400"></i>
                                Invoices
                            </a>
                            @endif
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

        @php
            $workspaceQuery = \App\Models\EmailList::query();
            if (app()->has('team_user')) {
                $teamUserId = app('team_user')->id;
                $workspaceQuery->where(function($q) use ($teamUserId) {
                    $q->where('is_public', true)
                      ->orWhere('created_by_id', $teamUserId);
                });
            }
            $workspaces = $workspaceQuery->orderBy('name')->get();
            $activeWorkspaceId = session('last_opened_list_id');
            $activeWorkspace = $workspaces->firstWhere('id', $activeWorkspaceId) ?? $workspaces->first();
            if ($activeWorkspace && $activeWorkspace->id != $activeWorkspaceId) {
                session(['last_opened_list_id' => $activeWorkspace->id]);
                $activeWorkspaceId = $activeWorkspace->id;
            }
        @endphp

        {{-- ── Sidebar ── --}}
        <aside x-show="$store.sidebar.open" x-transition:enter="transition-transform duration-300"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed left-0 z-40 w-[260px] h-full pt-16 flex flex-col border-r border-color bg-white">

            {{-- Workspace Switcher Dropdown --}}
            <div class="p-3 border-b border-gray-200 mb-3 relative" x-data="{ openWorkspace: false }">
                <button @click="openWorkspace = !openWorkspace" @click.away="openWorkspace = false"
                    class="bg-gray-50 border border-gray-200 hover:bg-gray-100 p-2.5 rounded-sm w-full flex items-center justify-between gap-2 cursor-pointer transition-all">
                    <div class="flex items-center justify-center gap-2 overflow-hidden">
                        <div class="w-6 h-6 pt-0.5 rounded-sm bg-brand text-white flex items-center justify-center shrink-0 text-xs font-bold font-mono">
                            {{ $activeWorkspace ? strtoupper(substr($activeWorkspace->name, 0, 2)) : 'WS' }}
                        </div>
                        <span class="font-bold text-xs text-gray-700 truncate">
                            {{ $activeWorkspace ? $activeWorkspace->name : 'Select Workspace' }}
                        </span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-gray-500 shrink-0 transition-transform duration-250" :class="openWorkspace ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="openWorkspace" x-cloak x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 -translate-y-2 scale-98"
                    x-transition:enter-end="transform opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="transform opacity-0 -translate-y-2 scale-98"
                    class="absolute left-3 right-3 top-14 mt-1 bg-white border border-gray-100 rounded-sm shadow-sm py-1 z-50 max-h-72 flex flex-col">
                    
                    <div class="text-[9px] uppercase tracking-wider text-gray-400 font-bold px-3 py-1.5 border-b border-gray-100">
                        Workspaces
                    </div>

                    <div class="overflow-y-auto flex-1 py-1 scrollbar-thin">
                        @foreach($workspaces as $workspace)
                            <a href="{{ route('admin.switch-workspace', $workspace->id) }}"
                                class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-brand/5 hover:text-brand transition-colors {{ $activeWorkspace && $workspace->id == $activeWorkspace->id ? 'bg-brand/5 text-brand' : '' }}">
                                <span class="truncate">{{ $workspace->name }}</span>
                                @if($activeWorkspace && $workspace->id == $activeWorkspace->id)
                                    <svg class="w-3.5 h-3.5 text-brand shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    @if(\App\Models\User::canAccess('workspace.create'))
                        <div class="border-t border-brand/20 p-1.5 pt-2 bg-gray-50/50">
                            <a href="{{ route('admin.email-lists.create') }}"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-brand hover:bg-brand hover:text-white border border-dashed border-brand/50 rounded-sm transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Workspace
                            </a>
                        </div>
                    @endif
                </div>
            </div>


            {{-- Navigation --}}
            <nav class="px-3 flex-1 overflow-y-auto scrollbar">
                @php
                    $can = function ($permission) {
                        return \App\Models\User::canAccess($permission);
                    };

                    $sidebarMenu = [];

                    $sidebarMenu[] = [
                        'title' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'active' => 'admin.dashboard',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />',
                    ];

                    $audienceSub = [];
                    if ($can('crm.view')) {
                        $audienceSub[] = ['title' => 'Contacts', 'route' => 'admin.email-lists.index', 'active' => 'admin.email-lists.*'];
                        $audienceSub[] = ['title' => 'Tags', 'route' => 'admin.tags.index', 'active' => 'admin.tags.*'];
                    }
                    if ($can('segments.view')) {
                        $audienceSub[] = ['title' => 'Segments', 'route' => 'admin.segments.index', 'active' => 'admin.segments.*'];
                    }
                    if ($can('crm.view')) {
                        $audienceSub[] = ['title' => 'Forms', 'route' => 'admin.signup-forms.index', 'active' => 'admin.signup-forms.*'];
                        $audienceSub[] = ['title' => 'Topics', 'route' => 'admin.subscription-topics.index', 'active' => 'admin.subscription-topics.*'];
                        $audienceSub[] = ['title' => 'Insights', 'route' => 'admin.insights.index', 'active' => 'admin.insights.*'];
                    }
                    if ($can('blacklist.manage')) {
                        $audienceSub[] = ['title' => 'Blacklist', 'route' => 'admin.blacklist.index', 'active' => 'admin.blacklist.*'];
                    }
                    if (!empty($audienceSub)) {
                        $sidebarMenu[] = [
                            'title' => 'Audience',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                            'active' => ['admin.email-lists.*', 'admin.contacts.*', 'admin.blacklist.*', 'admin.segments.*', 'admin.custom-fields.*', 'admin.subscription-topics.*', 'admin.tags.*', 'admin.signup-forms.*', 'admin.insights.*'],
                            'submenu' => $audienceSub
                        ];
                    }
 
                    $campaignsSub = [];
                    if ($can('campaigns.view')) {
                        $campaignsSub[] = ['title' => 'Email', 'route' => 'admin.campaigns.index', 'active' => 'admin.campaigns.*'];
                    }
                    if ($can('whatsapp.view')) {
                        $campaignsSub[] = ['title' => 'WhatsApp', 'route' => 'admin.whatsapp.campaigns.index', 'active' => 'admin.whatsapp.campaigns.*'];
                    }
                    if (!empty($campaignsSub)) {
                        $sidebarMenu[] = [
                            'title' => 'Campaigns',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />',
                            'active' => ['admin.campaigns.*', 'admin.whatsapp.campaigns.*'],
                            'submenu' => $campaignsSub
                        ];
                    }
 
                    if ($can('workflows.view')) {
                        $sidebarMenu[] = [
                            'title' => 'Automations',
                            'route' => 'admin.workflows.index',
                            'active' => 'admin.workflows.*',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
                        ];
                    }
 
                    $crmSub = [];
                    if ($can('pipelines.view')) {
                        $crmSub[] = ['title' => 'Deals Pipeline', 'route' => 'admin.pipelines.index', 'active' => 'admin.pipelines.*'];
                    }
                    if ($can('campaigns.view')) {
                        $crmSub[] = ['title' => 'Sequences', 'route' => 'admin.sequences.index', 'active' => 'admin.sequences.*'];
                    }
                    if ($can('tasks.view')) {
                        $crmSub[] = ['title' => 'Tasks & Calendar', 'route' => 'admin.tasks.index', 'active' => 'admin.tasks.*'];
                    }
                    if ($can('pipelines.view')) {
                        $crmSub[] = ['title' => 'Reports & Forecasting', 'route' => 'admin.crm-reports.index', 'active' => 'admin.crm-reports.*'];
                    }
                    if (!empty($crmSub)) {
                        $sidebarMenu[] = [
                            'title' => 'CRM & Sales',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                            'active' => ['admin.pipelines.*', 'admin.tasks.*', 'admin.sequences.*', 'admin.crm-reports.*'],
                            'submenu' => $crmSub
                        ];
                    }

                    $templatesSub = [];
                    if ($can('templates.view')) {
                        $templatesSub[] = ['title' => 'Email', 'route' => 'admin.templates.index', 'active' => 'admin.templates.*'];
                    }
                    if ($can('whatsapp.view')) {
                        $templatesSub[] = ['title' => 'WhatsApp', 'route' => 'admin.whatsapp.templates.index', 'active' => 'admin.whatsapp.templates.*'];
                    }
                    if (!empty($templatesSub)) {
                        $sidebarMenu[] = [
                            'title' => 'Templates',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />',
                            'active' => ['admin.templates.*', 'admin.whatsapp.templates.*'],
                            'submenu' => $templatesSub
                        ];
                    }

                    $waSub = [];
                    if ($can('whatsapp.view')) {
                        $waSub[] = ['title' => 'Chats', 'route' => 'admin.whatsapp.conversations.index', 'active' => 'admin.whatsapp.conversations.*'];
                        $waSub[] = ['title' => 'Numbers', 'route' => 'admin.whatsapp.accounts.index', 'active' => 'admin.whatsapp.accounts.*'];
                        $waSub[] = ['title' => 'Analytics', 'route' => 'admin.whatsapp.analytics', 'active' => 'admin.whatsapp.analytics'];
                        $waSub[] = ['title' => 'Settings', 'route' => 'admin.whatsapp.settings', 'active' => 'admin.whatsapp.settings'];
                    }
                    if (!empty($waSub)) {
                        $sidebarMenu[] = [
                            'title' => 'WhatsApp',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
                            'active' => 'admin.whatsapp.*',
                            'submenu' => $waSub
                        ];
                    }
                @endphp

                @foreach($sidebarMenu as $item)
                    @if(isset($item['submenu']))
                        <div x-data="{ open: {{ request()->routeIs($item['active']) ? 'true' : 'false' }} }" class="mb-1">
                            <button @click="open = !open"
                                class="flex items-center justify-between w-full p-2 rounded-sm text-sm transition-colors group cursor-pointer {{ request()->routeIs($item['active']) ? 'text-black bg-brand/10' : 'text-surface-700 hover:bg-surface-100 hover:text-black' }}">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs($item['active']) ? 'text-brand' : 'text-gray-400 group-hover:text-black' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $item['icon'] !!}
                                    </svg>
                                    {{ $item['title'] }}
                                </div>
                                <svg class="w-3.5 h-3.5 transform transition-transform duration-200"
                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 -translate-y-2"
                                x-transition:enter-end="transform opacity-100 translate-y-0"
                                class="mt-1 ml-4 pl-2.5 border-l-2 border-brand/20 space-y-1">
                                @foreach($item['submenu'] as $sub)
                                    <a href="{{ route($sub['route']) }}"
                                        class="block py-1.5 px-2 rounded-sm text-[13px] transition-colors {{ request()->routeIs($sub['active']) ? 'text-brand font-semibold' : 'text-surface-600 hover:text-black hover:bg-surface-50' }}">
                                        {{ $sub['title'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}"
                            class="flex items-center p-2 mb-1 rounded-sm text-sm gap-2.5 transition-colors group {{ request()->routeIs($item['active']) ? 'bg-brand/10 text-black' : 'text-surface-700 hover:bg-surface-100 hover:text-black' }}">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs($item['active']) ? 'text-brand' : 'text-gray-400 group-hover:text-black' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $item['icon'] !!}
                            </svg>
                            {{ $item['title'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="p-3 border-t border-gray-300 bg-white mt-auto space-y-3">
                @php
                    $globalTotalSent = \App\Models\EmailLog::count();
                    $globalTotalComplaints = \App\Models\EmailLog::where('status', 'complaint')->count();
                    $globalTotalBounced = \App\Models\EmailLog::where('status', 'bounced')->count();
                    $globalComplaintRate = $globalTotalSent > 0 ? ($globalTotalComplaints / $globalTotalSent) * 100 : 0;
                    $globalBounceRate = $globalTotalSent > 0 ? ($globalTotalBounced / $globalTotalSent) * 100 : 0;
                    $globalReputation = max(0, min(100, round(100 - ($globalBounceRate * 2) - ($globalComplaintRate * 5))));
                @endphp

                <div class="bg-surface-50 border border-gray-200 rounded-sm p-3 relative overflow-hidden group hover:border-brand/30 transition-colors">
                    <p class="text-[9px] font-bold text-surface-500 uppercase tracking-widest mb-1 flex items-center justify-between">
                        Sender Reputation
                        @if($globalReputation >= 90)
                            <span class="text-emerald-500">Excellent</span>
                        @elseif($globalReputation >= 70)
                            <span class="text-yellow-500">Average</span>
                        @else
                            <span class="text-red-500">Poor</span>
                        @endif
                    </p>
                    <div class="flex items-baseline gap-1">
                        <h3 class="text-2xl font-black text-surface-900 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $globalReputation }}</h3>
                        <span class="text-[10px] font-bold text-surface-400">/ 100</span>
                    </div>
                    <div class="mt-2 w-full h-1 bg-surface-200 rounded-sm flex overflow-hidden">
                        <div class="bg-emerald-500 h-full" style="width: {{ $globalReputation >= 90 ? $globalReputation : ($globalReputation > 70 ? 70 : $globalReputation) }}%"></div>
                        @if($globalReputation < 90 && $globalReputation > 0)
                            <div class="bg-yellow-400 h-full" style="width: {{ $globalReputation >= 70 ? $globalReputation - 70 : 0 }}%"></div>
                        @endif
                        @if($globalReputation < 70 && $globalReputation > 0)
                            <div class="bg-red-500 h-full" style="width: {{ $globalReputation }}%"></div>
                        @endif
                    </div>
                </div>

                <a href="{{ route('admin.billing.plans') }}"
                    class="flex items-center justify-between w-full border-2 border-brand text-xs text-white bg-brand hover:bg-brand/90 p-3 rounded-sm transition-all uppercase tracking-wider group">
                    Need More Access?
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </aside>

        {{-- ── Main Content ── --}}
        <main class="w-full pl-[260px] overflow-y-auto">
            {{-- Limit Warning Banner --}}
            @php
                $cUsage = auth()->user()->getContactsUsage();
                $eUsage = auth()->user()->getEmailsUsage();
            @endphp

            @if($cUsage->is_exceeded || $eUsage->is_exceeded)
                <div
                    class="bg-red-600 text-white px-6 py-3 flex items-center justify-between shadow-lg sticky top-0 z-[60]">
                    <div class="flex items-center gap-3 text-left">
                        <i class="fa-solid fa-triangle-exclamation text-xl animate-pulse"></i>
                        <div class="text-sm font-bold">
                            @if($cUsage->is_exceeded)
                                You have exceeded your contact limit ({{ number_format($cUsage->total) }} /
                                {{ number_format($cUsage->limit) }}).
                            @elseif($eUsage->is_exceeded)
                                You have exceeded your email sending limit ({{ number_format($eUsage->total) }} /
                                {{ number_format($eUsage->limit) }}).
                            @endif
                            Campaigns and Imports are currently blocked.
                        </div>
                    </div>
                    <a href="{{ route('admin.dashboard') }}"
                        class="px-4 py-1.5 bg-white text-red-600 text-xs font-black rounded-sm uppercase tracking-widest hover:bg-red-50 transition-all shrink-0">
                        Upgrade Now
                    </a>
                </div>
            @endif

            @if(View::hasSection('heading') || View::hasSection('header-actions'))
                <div
                    class="fixed top-16 z-40 left-[260px] w-[calc(100%-260px)] flex justify-between items-center bg-surface-0 px-6 py-4 border-b border-color">
                    @if(View::hasSection('heading'))
                        <h1 class="font-black uppercase text-lg">@yield('heading')</h1>
                    @endif
                    @if(View::hasSection('header-actions'))
                        @yield('header-actions')
                    @endif
                </div>
            @endif

            {{-- Flash Messages --}}
            @if(session('success'))
                <x-toast type="success" :message="session('success')" />
            @endif

            @if(session('error'))
                <x-toast type="error" :message="session('error')" />
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
    @stack('scripts')
</body>

</html>