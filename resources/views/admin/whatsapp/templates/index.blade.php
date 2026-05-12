@extends('layouts.app')

@section('title', 'WhatsApp Templates')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp Templates</h1>
            <p class="mt-1 text-sm text-gray-500">Official Meta-approved templates for outgoing messages.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ $template->category }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $template->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $template->status }}
                </span>
            </div>
            <div class="p-4 flex-grow">
                <h3 class="font-bold text-gray-900 mb-2">{{ $template->name }}</h3>
                <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 font-mono line-clamp-4">
                    {{ $template->body }}
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs text-gray-500">{{ strtoupper($template->language) }}</span>
                <button class="text-brand text-sm font-bold hover:underline">Preview Details</button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center bg-white rounded-xl border border-dashed border-gray-300">
            <p class="text-gray-500">No templates synced yet.</p>
            <p class="text-sm text-gray-400">Go to "WhatsApp Numbers" and click "Sync Templates" for a connected account.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
