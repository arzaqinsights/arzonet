<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Campaign Builder') — Arzonet</title>
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
<body class="h-full bg-white text-black antialiased overflow-hidden" style="font-family:'Outfit',sans-serif;font-weight:300">
    @yield('content')
    @stack('scripts')
</body>
</html>
