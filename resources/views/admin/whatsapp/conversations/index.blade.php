@extends('layouts.app')

@section('title', 'WhatsApp Inbox')

@section('content')
<div class="h-[calc(100vh-64px)] flex overflow-hidden bg-white">
    <!-- Conversation List -->
    <div class="w-80 flex-shrink-0 border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Conversations</h2>
            <div class="mt-2 relative">
                <input type="text" placeholder="Search contacts..." class="w-full pl-8 pr-4 py-1.5 text-sm border-gray-300 rounded-md focus:ring-brand focus:border-brand">
                <i class="fa-solid fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
            </div>
        </div>
        <div class="flex-grow overflow-y-auto">
            @forelse($conversations as $conversation)
            <a href="{{ route('admin.whatsapp.conversations.show', $conversation) }}" 
               class="block p-4 hover:bg-gray-50 transition-colors border-b border-gray-50 {{ request()->routeIs('admin.whatsapp.conversations.show') && request()->route('conversation')->id == $conversation->id ? 'bg-brand/5 border-l-4 border-l-brand' : '' }}">
                <div class="flex justify-between items-start mb-1">
                    <span class="font-bold text-sm text-gray-900">{{ $conversation->contact->name ?: $conversation->contact->whatsapp_number }}</span>
                    <span class="text-[10px] text-gray-400">{{ $conversation->last_message_at ? $conversation->last_message_at->format('H:i') : '' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-xs text-gray-500 truncate mr-2">{{ $conversation->whatsappAccount->display_name }}</p>
                    @if($conversation->unread_count > 0)
                    <span class="bg-brand text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $conversation->unread_count }}</span>
                    @endif
                </div>
            </a>
            @empty
            <div class="p-8 text-center text-gray-500 text-sm">
                No active conversations.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Message Area -->
    <div class="flex-grow flex flex-col bg-gray-50">
        @yield('conversation_content')
        
        @if(!isset($conversation))
        <div class="flex-grow flex items-center justify-center flex-col text-gray-400">
            <div class="h-20 w-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fa-brands fa-whatsapp text-4xl"></i>
            </div>
            <p class="text-lg font-medium">Select a conversation to start chatting</p>
            <p class="text-sm">Pick a contact from the left sidebar to view messages.</p>
        </div>
        @endif
    </div>
</div>
@endsection
