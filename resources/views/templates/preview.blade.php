@extends('layouts.app')
@section('title', 'Preview — ' . $template->name)
@section('heading', 'Template Preview')

@section('header-actions')
    <div x-data="{ showTestModal: false }">
        <button @click="showTestModal = true" class="btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Send Test Email
        </button>

        {{-- Test Email Modal --}}
        <div x-show="showTestModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTestModal = false"></div>
            <div class="glass-card p-6 w-full max-w-md relative z-10">
                <h3 class="text-lg font-semibold text-white mb-4">Send Test Email</h3>
                <form action="{{ route('admin.templates.send-test', $template) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Test Email Address</label>
                            <input type="email" name="test_email" class="form-input" required placeholder="your@email.com">
                        </div>
                        <div>
                            <label class="form-label">Test Name</label>
                            <input type="text" name="test_name" class="form-input" value="Test User" placeholder="Test User">
                        </div>
                        <div>
                            <label class="form-label">Send From</label>
                            <select name="sender_id" class="form-select" required>
                                @foreach($senders as $sender)
                                    <option value="{{ $sender->id }}">{{ $sender->email }}</option>
                                @endforeach
                            </select>
                            @if($senders->isEmpty())
                                <p class="text-amber-400 text-xs mt-1">No verified senders. Please add one in Sender Emails.</p>
                            @endif
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button type="button" @click="showTestModal = false" class="btn-ghost">Cancel</button>
                            <button type="submit" class="btn-primary">Send Test</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.templates.edit', $template) }}" class="btn-ghost btn-sm">Edit</a>
@endsection

@section('content')
<div class="animate-fade-in space-y-6">
    <div class="glass-card p-4">
        <div class="flex items-center gap-6 text-sm text-surface-400">
            <span><strong class="text-white">Template:</strong> {{ $template->name }}</span>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="bg-white rounded-xl max-w-3xl mx-auto my-6">
            <iframe srcdoc="{{ htmlspecialchars($previewHtml) }}" class="w-full border-0 rounded-xl" style="min-height: 600px;"
                    onload="this.style.height = this.contentDocument.body.scrollHeight + 'px'"></iframe>
        </div>
    </div>
</div>
@endsection
