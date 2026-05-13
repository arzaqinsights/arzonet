@extends('layouts.app')

@section('title', 'WhatsApp Inbox')

@section('header-actions')
    <button x-data @click="$dispatch('open-new-chat')" 
        class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> New Chat
    </button>
@endsection

@section('content')
{{-- New Chat Modal --}}
<div x-data="{ open: false }" 
     @open-new-chat.window="open = true"
     x-show="open" x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-white rounded-sm shadow-2xl w-full max-w-md p-6 space-y-5" @click.outside="open = false">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-black text-surface-900 uppercase tracking-widest">Start New Chat</h2>
                <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mt-0.5">Outbound chats require an approved template</p>
            </div>
            <button @click="open = false" class="text-surface-300 hover:text-surface-900 transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        @if($templates->count() === 0)
        <div class="p-4 bg-amber-50 border border-amber-100 rounded-sm">
            <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">No Approved Templates</p>
            <p class="text-xs text-amber-600 mt-1">You need at least one approved WhatsApp template to initiate a conversation. Your submitted template is likely still pending Meta review.</p>
            <a href="{{ route('admin.whatsapp.templates.index') }}" class="inline-block mt-2 text-[10px] font-black text-brand uppercase tracking-widest hover:underline">
                Manage Templates →
            </a>
        </div>
        @else
        <form action="{{ route('admin.whatsapp.conversations.initiate') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">From Number *</label>
                <select name="whatsapp_account_id" required
                    class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->display_name }} ({{ $account->phone_number }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">To Phone Number *</label>
                <input type="text" name="phone_number" required placeholder="+919876543210" 
                    class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                <p class="text-[9px] text-surface-400 mt-1 font-bold">Include country code (e.g. +91 for India)</p>
            </div>
            <div>
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest mb-2">Template Message *</label>
                <select name="template_id" required
                    class="w-full border border-color rounded-sm px-4 py-2.5 text-sm focus:ring-brand focus:border-brand bg-surface-50">
                    @foreach($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }} ({{ strtoupper($template->language) }}) — {{ $template->category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bg-surface-50 border border-color rounded-sm p-3">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1.5"><i class="fa-solid fa-circle-info mr-1"></i>How it works</p>
                <p class="text-[10px] text-surface-500">Meta allows businesses to initiate conversations only via approved templates. Once the recipient replies, a 24-hour free chat window opens.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" @click="open = false" class="flex-1 text-[10px] font-black uppercase tracking-widest py-3 border border-color rounded-sm hover:bg-surface-50 transition-colors">Cancel</button>
                <button type="submit" class="flex-1 bg-brand text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-sm hover:bg-black transition-all">
                    <i class="fa-brands fa-whatsapp mr-1"></i> Send & Start Chat
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

<div class="h-[calc(100vh-140px)] flex bg-white border border-color rounded-sm overflow-hidden" x-data="{ 
    scrollToBottom() {
        const el = document.getElementById('chat-scroll');
        if(el) el.scrollTop = el.scrollHeight;
    }
}" x-init="scrollToBottom()">
    
    {{-- Left Sidebar: Conversations --}}
    <div class="w-80 flex-shrink-0 border-r border-color flex flex-col bg-surface-50/30">
        <div class="p-6 border-b border-color bg-white">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-4">Conversations</h2>
            <div class="relative group">
                <input type="text" placeholder="Search contacts..." 
                    class="w-full pl-8 pr-4 py-2.5 text-[10px] font-bold uppercase tracking-tight border-color rounded-sm focus:ring-brand focus:border-brand bg-surface-50 group-hover:bg-white transition-all">
                <i class="fa-solid fa-search absolute left-3 top-3.5 text-surface-300 text-[10px]"></i>
            </div>
        </div>
        
        <div class="flex-grow overflow-y-auto divide-y divide-color/50">
            @forelse($conversations as $conv)
            <a href="{{ route('admin.whatsapp.conversations.show', $conv) }}" 
               class="block p-5 hover:bg-white transition-all group {{ (isset($conversation) && $conversation->id == $conv->id) ? 'bg-white border-l-4 border-brand' : '' }}">
                <div class="flex justify-between items-start mb-1.5">
                    <span class="font-black text-[11px] text-surface-900 uppercase tracking-tight truncate max-w-[140px]">
                        {{ $conv->contact->name ?: $conv->contact->whatsapp_number }}
                    </span>
                    <span class="text-[8px] font-black text-surface-300 uppercase tracking-widest">
                        {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(['short' => true]) : '' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-[10px] font-medium text-surface-400 truncate pr-4">
                        {{ $conv->last_message_preview ?: 'No messages yet' }}
                    </p>
                    @if($conv->unread_count > 0)
                    <span class="bg-brand text-white text-[8px] font-black px-1.5 py-0.5 rounded-full shadow-lg shadow-brand/20">
                        {{ $conv->unread_count }}
                    </span>
                    @endif
                </div>
            </a>
            @empty
            <div class="p-10 text-center space-y-3 opacity-40">
                <i class="fa-brands fa-whatsapp text-3xl"></i>
                <p class="text-[9px] font-black uppercase tracking-widest">No Active Chats</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right Pane: Chat Thread --}}
    <div class="flex-grow flex flex-col bg-surface-50/30">
        @if(isset($conversation))
            {{-- Chat Header --}}
            <div class="px-8 py-5 bg-white border-b border-color flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-sm bg-surface-900 text-white flex items-center justify-center font-black text-sm">
                        {{ strtoupper(substr($conversation->contact->name ?: 'W', 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-[11px] font-black text-surface-900 uppercase tracking-widest">
                            {{ $conversation->contact->name ?: $conversation->contact->whatsapp_number }}
                        </h3>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Active Session</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-[8px] font-black text-surface-300 uppercase tracking-widest">Managed By</p>
                        <p class="text-[9px] font-black text-surface-900 uppercase tracking-widest">
                            {{ $conversation->agent ? $conversation->agent->name : 'Unassigned' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Messages Area --}}
            <div id="chat-scroll" class="flex-grow overflow-y-auto p-8 space-y-6">
                @foreach($messages as $msg)
                    <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] space-y-1">
                            <div class="px-5 py-3 rounded-sm text-sm {{ $msg->direction === 'outbound' ? 'bg-surface-900 text-white font-medium rounded-tr-none' : 'bg-white border border-color text-surface-700 rounded-tl-none shadow-sm' }}">
                                @if($msg->type !== 'text' && $msg->metadata)
                                    <div class="mb-2 p-2 bg-black/5 rounded-sm flex items-center gap-3">
                                        <i class="fa-solid fa-file-invoice text-brand"></i>
                                        <span class="text-[10px] font-bold uppercase truncate">{{ $msg->metadata['filename'] ?? 'Attachment' }}</span>
                                    </div>
                                @endif
                                {{ $msg->message_body }}
                            </div>
                            <div class="flex items-center gap-2 {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                                <span class="text-[8px] font-black text-surface-300 uppercase tracking-widest">
                                    {{ $msg->created_at->format('H:i') }}
                                </span>
                                @if($msg->direction === 'outbound')
                                    @php
                                        $statusIcon = match($msg->status) {
                                            'sent' => 'fa-check text-surface-200',
                                            'delivered' => 'fa-check-double text-surface-200',
                                            'read' => 'fa-check-double text-brand',
                                            'failed' => 'fa-circle-xmark text-red-500',
                                            default => 'fa-clock text-surface-200'
                                        };
                                    @endphp
                                    <i class="fa-solid {{ $statusIcon }} text-[8px]"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reply Box --}}
            <div class="p-6 bg-white border-t border-color">
                <form action="{{ route('admin.whatsapp.conversations.reply', $conversation) }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-grow relative">
                        <textarea name="message" rows="1" placeholder="Type your message..." 
                            class="w-full px-6 py-4 text-[11px] font-bold uppercase tracking-tight border-color rounded-sm focus:ring-brand focus:border-brand bg-surface-50 resize-none min-h-[56px]"
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        <div class="absolute right-4 bottom-4 flex items-center gap-3">
                            <button type="button" class="text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-paperclip"></i></button>
                            <button type="button" class="text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-face-smile"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-4 rounded-sm hover:bg-black transition-all shadow-xl shadow-brand/10 h-[56px]">
                        Send Reply
                    </button>
                </form>
            </div>
        @else
            <div class="flex-grow flex items-center justify-center flex-col text-center space-y-6 opacity-30">
                <div class="w-24 h-24 rounded-full border-2 border-dashed border-surface-300 flex items-center justify-center">
                    <i class="fa-brands fa-whatsapp text-5xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-surface-900 uppercase tracking-widest">Unified Communications</h3>
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mt-2">Select a thread from the sidebar to begin messaging.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
