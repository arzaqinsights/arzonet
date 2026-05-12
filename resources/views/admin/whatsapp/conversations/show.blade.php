@extends('admin.whatsapp.conversations.index')

@section('conversation_content')
<!-- Header -->
<div class="p-4 bg-white border-b border-gray-200 flex justify-between items-center shadow-sm z-10">
    <div class="flex items-center">
        <div class="h-10 w-10 rounded-full bg-brand/10 flex items-center justify-center text-brand font-bold">
            {{ substr($conversation->contact->name ?: $conversation->contact->whatsapp_number, 0, 1) }}
        </div>
        <div class="ml-3">
            <h3 class="font-bold text-gray-900 leading-none">{{ $conversation->contact->name ?: $conversation->contact->whatsapp_number }}</h3>
            <p class="text-xs text-green-500 mt-1 flex items-center">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1.5"></span>
                Active on {{ $conversation->whatsappAccount->display_name }}
            </p>
        </div>
    </div>
    <div class="flex space-x-2">
        <button class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"><i class="fa-solid fa-phone"></i></button>
        <button class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"><i class="fa-solid fa-ellipsis-vertical"></i></button>
    </div>
</div>

<!-- Messages -->
<div class="flex-grow overflow-y-auto p-6 space-y-4 flex flex-col">
    @foreach($messages as $message)
    <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
        <div class="max-w-[70%] rounded-2xl p-3 shadow-sm {{ $message->direction === 'outbound' ? 'bg-brand text-white rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-tl-none' }}">
            <p class="text-sm">{{ $message->message_body }}</p>
            <div class="mt-1 flex items-center justify-end space-x-1">
                <span class="text-[10px] {{ $message->direction === 'outbound' ? 'text-white/70' : 'text-gray-400' }}">
                    {{ $message->created_at->format('H:i') }}
                </span>
                @if($message->direction === 'outbound')
                <i class="fa-solid fa-check-double text-[10px] {{ $message->status === 'read' ? 'text-blue-200' : 'text-white/50' }}"></i>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Reply Box -->
<div class="p-4 bg-white border-t border-gray-200">
    <form action="{{ route('admin.whatsapp.conversations.reply', $conversation) }}" method="POST" class="flex items-end space-x-3">
        @csrf
        <div class="flex-grow bg-gray-50 rounded-2xl border border-gray-200 px-4 py-2 focus-within:ring-1 focus-within:ring-brand focus-within:border-brand transition-all">
            <textarea name="message" rows="1" required placeholder="Type a message..." 
                class="w-full bg-transparent border-none focus:ring-0 text-sm py-1 resize-none"
                oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
        </div>
        <button type="submit" class="h-10 w-10 rounded-full bg-brand text-white flex items-center justify-center hover:bg-[#e05638] shadow-md transition-all flex-shrink-0">
            <i class="fa-solid fa-paper-plane text-sm"></i>
        </button>
    </form>
    <p class="text-[10px] text-gray-400 mt-2 text-center">Standard session window rules apply. Use templates for initiated messages.</p>
</div>
@endsection
