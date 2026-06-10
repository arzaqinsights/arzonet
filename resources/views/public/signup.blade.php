<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe to {{ $list->name }}</title>
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
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-2xl">
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 mb-4 font-extrabold text-xl shadow-sm border border-indigo-100">
                    {{ strtoupper(substr($list->name, 0, 2)) }}
                </div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Join our newsletter</h2>
                <p class="text-sm text-gray-500 mt-1">Subscribe to receive regular updates from <strong>{{ $list->name }}</strong>.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-sm font-semibold text-center animate-pulse">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('public.forms.submit', $list->signup_form_token) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="has_topics_field" value="1">

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Your Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="e.g. Jane Doe">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Email Address</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="e.g. jane@example.com">
                </div>

                @if(count($topics) > 0)
                    <div class="space-y-3 pt-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Subscription Preferences</label>
                        <div class="space-y-2.5">
                            @foreach($topics as $topic)
                                <label class="flex items-start gap-3 p-3 bg-gray-50 hover:bg-gray-100/80 rounded-xl border border-gray-100 cursor-pointer transition-colors">
                                    <input type="checkbox" name="topics[]" value="{{ $topic->id }}" checked class="mt-1 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <div>
                                        <p class="text-xs font-bold text-gray-800 leading-none">{{ $topic->name }}</p>
                                        @if($topic->description)
                                            <p class="text-[10px] text-gray-500 mt-1 leading-normal">{{ $topic->description }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white text-xs font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-150 transition-all duration-150 mt-4">
                    Subscribe Now
                </button>
            </form>
        </div>
        <div class="px-8 py-4 bg-gray-50/80 border-t border-gray-100 text-center">
            <p class="text-[10px] text-gray-400 font-medium">You can unsubscribe or change your preferences at any time.</p>
        </div>
    </div>
</body>
</html>
