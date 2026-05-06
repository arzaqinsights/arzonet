<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Arzonet — Enterprise Bulk Email Platform')</title>
    <meta name="description"
        content="@yield('meta_description', 'Send millions of emails reliably. Advanced bulk email platform with smart analytics, bounce handling, and 99.9% deliverability.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="antialiased" style="background: #fff; color: #111; font-family:'Outfit',sans-serif;font-weight:300">
    {{-- NAV --}}
    <nav class="sticky top-0 z-50 w-full left-0 bg-white/95 backdrop-blur-md border-b border-gray-100">
        <div class="container flex items-center justify-between py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo/logo.png') }}" class="md:h-10 h-8 shrink-0 object-contain"></a>

            <div class="hidden md:flex items-center gap-8 text-base text-black uppercase">
                <a href="{{ route('home') }}#features" class="hover:text-gray-900 transition-colors">Features</a>
                <a href="{{ route('home') }}#pricing" class="hover:text-gray-900 transition-colors">Pricing</a>
                <a href="{{ route('home') }}#faq" class="hover:text-gray-900 transition-colors">FAQ</a>
                <div class="flex gap-4">
                    <a href="{{ route('login') }}"
                    class="text-base text-white rounded-sm font-semibold bg-surface-800 px-6 py-3 transition-all">Sign In</a>
                <a href="{{ route('register') }}" class="text-base text-white rounded-sm font-semibold bg-brand px-6 py-3 transition-all">Get Started</a>
                </div>
            </div>
            <button class="md:hidden">
                <i class="fa-regular fa-user text-2xl"></i>
            </button>
        </div>
    </nav>

    @yield('content')

    {{-- FOOTER --}}
    <footer style="border-top:1px solid #e5e5e5;background:#fafafa;">
        <div class="max-w-6xl mx-auto px-6 py-12 flex flex-col md:flex-row items-start justify-between gap-10">
            <div class="flex flex-col gap-4 max-w-sm">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded overflow-hidden"><img src="{{ asset('images/logo/logo.png') }}"
                            class="w-full h-full object-contain"></div>
                    <span class="font-bold text-xl text-gray-900"
                        style="font-family:'Outfit',sans-serif;">Arzonet</span>
                </a>
                <p class="text-sm text-gray-500">Enterprise bulk email platform built for maximum deliverability, smart
                    analytics, and high-performance sending.</p>
                <span class="text-gray-400 text-xs">© {{ date('Y') }} Arzonet. All rights reserved.</span>
            </div>

            <div class="flex gap-16">
                <div class="flex flex-col gap-3 text-sm text-gray-500">
                    <span class="font-bold text-gray-900 mb-1" style="font-family:'Outfit',sans-serif;">Product</span>
                    <a href="{{ route('home') }}#features" class="hover:text-gray-900 transition-colors">Features</a>
                    <a href="{{ route('home') }}#pricing" class="hover:text-gray-900 transition-colors">Pricing</a>
                    <a href="{{ route('home') }}#faq" class="hover:text-gray-900 transition-colors">FAQ</a>
                    <a href="{{ route('login') }}" class="hover:text-gray-900 transition-colors">Admin
                        Login</a>
                </div>

                <div class="flex flex-col gap-3 text-sm text-gray-500">
                    <span class="font-bold text-gray-900 mb-1" style="font-family:'Outfit',sans-serif;">Legal &
                        Support</span>
                    <a href="{{ route('contact') }}" class="hover:text-gray-900 transition-colors">Contact Us</a>
                    <a href="{{ route('privacy') }}" class="hover:text-gray-900 transition-colors">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="hover:text-gray-900 transition-colors">Terms & Conditions</a>
                    <a href="{{ route('refund') }}" class="hover:text-gray-900 transition-colors">Refund Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>