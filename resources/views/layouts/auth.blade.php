<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Arzonet — Authentication')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="shortcut icon" href="{{ asset('images/logo/square-logo.png')}}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-surface-50 min-h-screen flex text-black" style="font-family:'Outfit',sans-serif;font-weight:300">
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 w-full lg:w-1/2">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div>
                <a href="{{ route('home') }}">
                    <img class="h-12 w-auto" src="{{ asset('images/logo/logo.png') }}" alt="Arzonet">
                </a>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">@yield('heading')</h2>
                <p class="mt-2 text-sm text-gray-600">
                    @yield('subheading')
                </p>
            </div>
            <div class="mt-8">
                @yield('content')
            </div>
        </div>
    </div>
    <div class="hidden lg:block relative w-0 flex-1 bg-surface-900">
        <div class="absolute inset-0 bg-brand/20 mix-blend-multiply"></div>
        <div class="absolute inset-0 flex items-center justify-center p-20">
            <div class="text-white max-w-lg">
                <h2 class="text-4xl font-bold mb-6">Join thousands of senders.</h2>
                <p class="text-xl text-gray-300 font-light leading-relaxed">Experience unparalleled deliverability, intelligent tracking, and infrastructure built for scale. Stop worrying about your emails hitting the spam folder.</p>
                <div class="mt-10 grid grid-cols-2 gap-8">
                    <div>
                        <div class="text-3xl font-black text-brand mb-1">99.9%</div>
                        <div class="text-sm text-gray-400">Uptime SLA</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-brand mb-1">100M+</div>
                        <div class="text-sm text-gray-400">Emails Daily</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
