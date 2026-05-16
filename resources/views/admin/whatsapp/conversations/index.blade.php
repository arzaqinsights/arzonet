@extends('layouts.app')

@section('title', 'Unified Inbox')

@section('header-actions')
    <button x-data @click="$dispatch('open-new-chat')" 
        class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-8 py-3 rounded-sm hover:bg-black transition-all flex items-center gap-2 shadow-lg shadow-brand/20">
        <i class="fa-solid fa-paper-plane text-[8px]"></i> New Conversation
    </button>
@endsection

@section('content')
{{-- New Chat Modal (Improved Styling) --}}
<div x-data="{ open: false }" 
     @open-new-chat.window="open = true"
     x-show="open" x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center bg-surface-900/40 backdrop-blur-md">
    <div class="bg-white rounded-sm shadow-2xl w-full max-w-md p-8 space-y-6 animate-slide-up" @click.outside="open = false">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest">Initiate Outreach</h2>
                <p class="text-[9px] text-surface-400 font-bold uppercase tracking-widest mt-1">Select a verified template to begin</p>
            </div>
            <button @click="open = false" class="w-8 h-8 flex items-center justify-center bg-surface-50 text-surface-300 hover:text-surface-900 rounded-sm transition-all"><i class="fa-solid fa-xmark"></i></button>
        </div>

        @if($templates->count() === 0)
        <div class="p-6 bg-amber-50 border border-amber-100 rounded-sm">
            <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">No Active Templates</p>
            <p class="text-[11px] text-amber-600/80 mt-1 leading-relaxed">Meta requires an approved template for initial outreach. Once they reply, you can chat freely.</p>
            <a href="{{ route('admin.whatsapp.templates.index') }}" class="inline-flex mt-4 text-[10px] font-black text-brand uppercase tracking-widest border-b-2 border-brand/20 hover:border-brand transition-all pb-0.5">
                Manage Templates →
            </a>
        </div>
        @else
        <form action="{{ route('admin.whatsapp.conversations.initiate') }}" method="POST" class="space-y-5">
            @csrf
            <div class="space-y-1.5">
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">Originating Account</label>
                <select name="whatsapp_account_id" required class="w-full bg-surface-50 border border-gray-100 rounded-sm px-4 py-3 text-xs font-bold focus:ring-0 focus:border-brand transition-all">
                    @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->display_name }} ({{ $account->phone_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">Recipient Number</label>
                <input type="text" name="phone_number" required placeholder="+91 00000 00000" 
                    class="w-full bg-surface-50 border border-gray-100 rounded-sm px-4 py-3 text-xs font-bold focus:ring-0 focus:border-brand transition-all">
            </div>
            <div class="space-y-1.5">
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">Outreach Template</label>
                <select name="template_id" required class="w-full bg-surface-50 border border-gray-100 rounded-sm px-4 py-3 text-xs font-bold focus:ring-0 focus:border-brand transition-all">
                    @foreach($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }} — {{ $template->category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm hover:bg-black transition-all shadow-xl shadow-brand/10">
                    <i class="fa-brands fa-whatsapp mr-2"></i> Start Conversation
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

{{-- Edit Contact Modal (Advanced WhatsApp Logic) --}}
@if(isset($conversation))
<div x-data="{ 
        open: false, 
        saving: false,
        form: {
            name: '{{ $conversation->contact->name }}',
            email: '{{ $conversation->contact->email }}',
            whatsapp_subscription_status: '{{ $conversation->contact->whatsapp_subscription_status ?: 'subscribed' }}',
            meta: @js($conversation->contact->meta ?? [])
        },
        save() {
            this.saving = true;
            fetch('{{ route('admin.email-lists.update-email', [$conversation->contact->email_list_id, $conversation->contact->id]) }}', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').content },
                body: JSON.stringify(this.form)
            }).then(() => {
                this.saving = false;
                this.open = false;
                window.location.reload();
            });
        }
     }" 
     @open-edit-modal.window="open = true"
     x-show="open" x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center bg-surface-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-sm shadow-2xl w-full max-w-lg overflow-hidden animate-slide-up" @click.outside="open = false">
        <div class="p-8 bg-surface-50 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest">Update Intelligence</h2>
                <p class="text-[9px] text-surface-400 font-bold uppercase tracking-widest mt-1">Refining profile for {{ $conversation->contact->whatsapp_number }}</p>
            </div>
            <button @click="open = false" class="text-surface-400 hover:text-surface-900 transition-colors"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">Full Name</label>
                    <input type="text" x-model="form.name" class="w-full bg-surface-50 border border-gray-100 rounded-sm px-4 py-3 text-xs font-bold focus:ring-0 focus:border-brand transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">Email Address</label>
                    <input type="email" x-model="form.email" class="w-full bg-surface-50 border border-gray-100 rounded-sm px-4 py-3 text-xs font-bold focus:ring-0 focus:border-brand transition-all">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-[9px] font-black text-surface-500 uppercase tracking-widest">WhatsApp Subscription</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="form.whatsapp_subscription_status = 'subscribed'" 
                            :class="form.whatsapp_subscription_status === 'subscribed' ? 'bg-emerald-500 text-white' : 'bg-surface-50 text-surface-400'"
                            class="px-4 py-3 rounded-sm text-[10px] font-black uppercase tracking-widest transition-all">
                        Subscribed
                    </button>
                    <button @click="form.whatsapp_subscription_status = 'unsubscribed'" 
                            :class="form.whatsapp_subscription_status === 'unsubscribed' ? 'bg-red-500 text-white' : 'bg-surface-50 text-surface-400'"
                            class="px-4 py-3 rounded-sm text-[10px] font-black uppercase tracking-widest transition-all">
                        Opt-out
                    </button>
                </div>
            </div>
            
            @if(!empty($conversation->contact->meta))
            <div class="pt-4 border-t border-gray-50">
                <h4 class="text-[8px] font-black text-surface-300 uppercase tracking-[0.2em] mb-4">Extended CRM Attributes</h4>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($conversation->contact->meta as $key => $val)
                        @if(!is_array($val))
                        <div class="space-y-1">
                            <label class="block text-[8px] font-black text-surface-400 uppercase tracking-widest">{{ strtoupper(str_replace('_', ' ', $key)) }}</label>
                            <input type="text" x-model="form.meta['{{ $key }}']" class="w-full bg-surface-50 border border-transparent rounded-sm px-3 py-2 text-[10px] font-bold focus:bg-white focus:border-brand transition-all">
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
            <button @click="open = false" class="flex-1 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Discard Changes</button>
            <button @click="save()" class="flex-1 bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm hover:bg-black transition-all shadow-xl shadow-surface-900/10" :disabled="saving">
                <span x-show="!saving">Commit Updates</span>
                <span x-show="saving">Processing...</span>
            </button>
        </div>
    </div>
</div>
@endif

<div class="h-[calc(100vh-140px)] flex bg-white border border-color rounded-sm overflow-hidden shadow-2xl shadow-surface-900/5">
    
    {{-- ── PANE 1: CONVERSATIONS LIST ── --}}
    <div class="w-80 lg:w-96 flex-shrink-0 border-r border-color flex flex-col bg-surface-50/20">
        {{-- Search & Filter --}}
        <div class="p-6 bg-white space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Communications</h2>
                <div class="flex gap-1">
                    <button class="w-6 h-6 flex items-center justify-center text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-sliders text-[10px]"></i></button>
                </div>
            </div>
            <div class="relative group">
                <input type="text" placeholder="Find a contact..." 
                    class="w-full pl-9 pr-4 py-3 text-[10px] font-black uppercase tracking-tight border-gray-100 rounded-sm focus:ring-0 focus:border-brand bg-surface-50 group-hover:bg-white transition-all placeholder:text-surface-300">
                <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-surface-300 text-[10px]"></i>
            </div>
        </div>
        
        {{-- List --}}
        <div class="flex-grow overflow-y-auto custom-scrollbar no-scrollbar divide-y divide-gray-50">
            @forelse($conversations as $conv)
            <a href="{{ route('admin.whatsapp.conversations.show', $conv) }}" 
               class="block p-6 hover:bg-white transition-all group relative {{ (isset($conversation) && $conversation->id == $conv->id) ? 'bg-white' : '' }}">
                @if(isset($conversation) && $conversation->id == $conv->id)
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-brand"></div>
                @endif
                
                <div class="flex gap-4">
                    <div class="w-10 h-10 flex-shrink-0 rounded-sm bg-surface-900 flex items-center justify-center font-black text-xs text-white shadow-lg shadow-surface-900/10">
                        {{ strtoupper(substr($conv->contact->name ?: 'W', 0, 1)) }}
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex justify-between items-start mb-0.5">
                            <span class="font-black text-[11px] text-surface-900 uppercase tracking-tight truncate">
                                {{ $conv->contact->name ?: $conv->contact->whatsapp_number }}
                            </span>
                            <span class="text-[8px] font-black text-surface-300 uppercase tracking-widest shrink-0">
                                {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(['short' => true]) : '' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-[10px] font-bold text-surface-400 truncate uppercase tracking-tight italic">
                                {{ $conv->last_message_preview ?: 'Beginning of thread' }}
                            </p>
                            @if($conv->unread_count > 0)
                                <span class="bg-brand text-white text-[8px] font-black px-1.5 py-0.5 rounded-full shrink-0">
                                    {{ $conv->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
            @empty
            <div class="flex flex-col items-center justify-center h-full opacity-20 py-20 grayscale">
                <i class="fa-brands fa-whatsapp text-5xl mb-4"></i>
                <p class="text-[9px] font-black uppercase tracking-widest">No Active Threads</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── PANE 2: CHAT INTERFACE ── --}}
    <div class="flex-grow flex flex-col bg-white" x-data="{ 
        scrollToBottom() {
            const el = document.getElementById('chat-scroll');
            if(el) el.scrollTop = el.scrollHeight;
        },
        infoOpen: true
    }" x-init="scrollToBottom()">
        @if(isset($conversation))
            {{-- Chat Header --}}
            <div class="px-8 py-5 bg-white border-b border-color flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-sm bg-emerald-500 text-white flex items-center justify-center font-black text-sm shadow-xl shadow-emerald-500/20">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-black text-surface-900 uppercase tracking-widest flex items-center gap-2">
                            {{ $conversation->contact->name ?: $conversation->contact->whatsapp_number }}
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        </h3>
                        <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mt-0.5">
                            {{ $conversation->contact->whatsapp_number }} • Standard Session
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button class="w-10 h-10 flex items-center justify-center text-surface-300 hover:text-surface-900 transition-colors border border-gray-50 rounded-sm hover:bg-gray-50"><i class="fa-solid fa-search text-xs"></i></button>
                    <button @click="$dispatch('open-edit-modal')" class="w-10 h-10 flex items-center justify-center text-surface-300 hover:text-brand transition-colors border border-gray-50 rounded-sm hover:bg-brand/5">
                        <i class="fa-solid fa-user-pen text-xs"></i>
                    </button>
                    <button @click="infoOpen = !infoOpen" class="w-10 h-10 flex items-center justify-center text-surface-300 hover:text-brand transition-colors border border-gray-50 rounded-sm" :class="infoOpen ? 'bg-brand/5 border-brand/10 text-brand' : ''">
                        <i class="fa-solid fa-circle-info text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Messages Area (Enhanced) --}}
            <div id="chat-scroll" class="flex-grow overflow-y-auto p-10 space-y-4 bg-[#f0f2f5] relative no-scrollbar" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-opacity: 0.05;">
                <div class="absolute inset-0 bg-white/90 pointer-events-none"></div>
                
                {{-- Date Separator --}}
                <div class="relative z-10 flex justify-center py-4">
                    <span class="px-4 py-1.5 bg-white/80 backdrop-blur-sm text-[8px] font-black text-surface-400 uppercase tracking-[0.2em] rounded-full shadow-sm border border-gray-100">Conversation Timeline</span>
                </div>

                @foreach($messages as $msg)
                    <div class="relative z-10 flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }} animate-fade-in mb-2">
                        <div class="max-w-[85%] lg:max-w-[70%] space-y-1 group">
                            <div class="relative px-4 py-2.5 shadow-sm {{ $msg->direction === 'outbound' ? 'bg-[#d9fdd3] text-surface-900 rounded-l-md rounded-br-md' : 'bg-white text-surface-800 rounded-r-md rounded-bl-md border border-gray-100' }}">
                                {{-- Bubble Tail --}}
                                @if($msg->direction === 'outbound')
                                    <div class="absolute top-0 -right-2 w-3 h-3 bg-[#d9fdd3]" style="clip-path: polygon(0 0, 0% 100%, 100% 0);"></div>
                                @else
                                    <div class="absolute top-0 -left-2 w-3 h-3 bg-white" style="clip-path: polygon(100% 0, 100% 100%, 0 0);"></div>
                                @endif

                                @if($msg->type !== 'text' && $msg->metadata)
                                    <div class="mb-2 p-2 bg-black/5 rounded-sm flex items-center gap-3 border border-black/5">
                                        <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center bg-white rounded-sm text-brand shadow-sm">
                                            <i class="fa-solid fa-file-invoice text-xs"></i>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-surface-400 mb-0.5">Attachment</p>
                                            <p class="text-[10px] font-black truncate text-surface-900 uppercase tracking-tight">{{ $msg->metadata['filename'] ?? 'document.pdf' }}</p>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="text-[13px] leading-relaxed break-words font-medium">
                                    {!! nl2br(e($msg->message_body)) !!}
                                </div>

                                <div class="flex items-center justify-end gap-1 mt-1 opacity-60">
                                    <span class="text-[9px] font-bold text-surface-400">
                                        {{ $msg->created_at->format('H:i') }}
                                    </span>
                                    @if($msg->direction === 'outbound')
                                        <i class="fa-solid {{ match($msg->status) { 'read' => 'fa-check-double text-sky-500', 'delivered' => 'fa-check-double', default => 'fa-check' } }} text-[10px]"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reply Box (Advanced) --}}
            <div class="p-8 bg-white border-t border-color shadow-[0_-20px_40px_-20px_rgba(0,0,0,0.05)]">
                <form action="{{ route('admin.whatsapp.conversations.reply', $conversation) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="relative bg-surface-50 border border-gray-100 rounded-sm focus-within:border-brand focus-within:ring-4 focus-within:ring-brand/5 transition-all">
                        <textarea name="message" rows="1" placeholder="Craft a response..." 
                            class="w-full px-8 py-5 text-[11px] font-bold uppercase tracking-tight border-none focus:ring-0 bg-transparent resize-none min-h-[64px]"
                            oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        
                        <div class="px-8 pb-4 flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <button type="button" title="Attach Document" class="text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-paperclip text-sm"></i></button>
                                <button type="button" title="Quick Responses" class="text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-bolt text-sm"></i></button>
                                <button type="button" title="Templates" class="text-surface-300 hover:text-brand transition-colors"><i class="fa-solid fa-layer-group text-sm"></i></button>
                            </div>
                            <button type="submit" class="bg-surface-900 text-white text-[9px] font-black uppercase tracking-widest px-10 py-3 rounded-sm hover:bg-black transition-all shadow-xl shadow-surface-900/20">
                                Dispatch Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="flex-grow flex items-center justify-center flex-col text-center space-y-8 opacity-40">
                <div class="relative">
                    <div class="w-32 h-32 rounded-sm border-2 border-dashed border-surface-200 flex items-center justify-center rotate-3 scale-95 group-hover:rotate-0 transition-all">
                        <i class="fa-brands fa-whatsapp text-6xl text-surface-200"></i>
                    </div>
                    <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-brand rounded-sm flex items-center justify-center text-white shadow-xl rotate-12">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-black text-surface-900 uppercase tracking-widest">Command Center</h3>
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-[0.2em] mt-3">Select a communication thread to engage</p>
                </div>
            </div>
        @endif
    </div>

    {{-- ── PANE 3: CONTACT INTELLIGENCE ── --}}
    @if(isset($conversation))
    <div x-show="infoOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         class="w-80 flex-shrink-0 border-l border-color bg-white flex flex-col overflow-y-auto no-scrollbar">
        
        <div class="p-10 text-center border-b border-color space-y-4">
            <div class="w-24 h-24 mx-auto rounded-sm bg-surface-900 text-white flex items-center justify-center text-3xl font-black shadow-2xl shadow-surface-900/20">
                {{ strtoupper(substr($conversation->contact->name ?: 'W', 0, 1)) }}
            </div>
            <div>
                <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">{{ $conversation->contact->name ?: 'Lead #'.$conversation->contact->id }}</h4>
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mt-1">{{ $conversation->contact->whatsapp_number }}</p>
            </div>
            <div class="flex justify-center gap-2">
                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[8px] font-black uppercase tracking-widest rounded-sm border border-emerald-100">Verified</span>
                <span class="px-3 py-1 bg-surface-50 text-surface-600 text-[8px] font-black uppercase tracking-widest rounded-sm border border-gray-100">WhatsApp</span>
            </div>
        </div>

        <div class="p-10 space-y-10">
            {{-- CRM Data --}}
            <div class="space-y-6">
                <h5 class="text-[9px] font-black text-surface-300 uppercase tracking-widest border-b border-gray-50 pb-4">Identity Details</h5>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[8px] font-black text-surface-400 uppercase tracking-widest mb-1">Email Address</label>
                        <p class="text-[10px] font-black text-surface-900 uppercase tracking-tight">{{ $conversation->contact->email ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="block text-[8px] font-black text-surface-400 uppercase tracking-widest mb-1">Segment</label>
                        <p class="text-[10px] font-black text-brand uppercase tracking-tight">{{ $conversation->contact->segment_name ?: 'Default' }}</p>
                    </div>
                    <div>
                        <label class="block text-[8px] font-black text-surface-400 uppercase tracking-widest mb-1">Tags</label>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @php $tags = explode(',', $conversation->contact->tags); @endphp
                            @foreach($tags as $tag)
                                @if(trim($tag))
                                    <span class="px-2 py-0.5 bg-surface-50 text-surface-500 text-[8px] font-black uppercase tracking-widest rounded-sm border border-gray-100">{{ trim($tag) }}</span>
                                @endif
                            @endforeach
                            @if(empty(array_filter($tags))) <span class="text-[10px] font-bold text-surface-300 italic">No tags</span> @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-6">
                <h5 class="text-[9px] font-black text-surface-300 uppercase tracking-widest border-b border-gray-50 pb-4">Intelligence Actions</h5>
                <div class="grid grid-cols-1 gap-2">
                    <button @click="$dispatch('open-edit-modal')" class="w-full text-left px-5 py-3 bg-brand text-white hover:bg-black transition-all text-[9px] font-black uppercase tracking-widest rounded-sm flex items-center justify-between group shadow-lg shadow-brand/10">
                        Edit Intelligence Profile
                        <i class="fa-solid fa-user-pen opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>
                    <button class="w-full text-left px-5 py-3 bg-surface-50 hover:bg-black hover:text-white transition-all text-[9px] font-black uppercase tracking-widest rounded-sm flex items-center justify-between group">
                        Add to Campaign
                        <i class="fa-solid fa-bullseye opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-auto p-10 border-t border-color">
            <p class="text-[8px] font-black text-surface-300 uppercase tracking-widest leading-relaxed">
                Last Activity recorded from {{ $conversation->contact->signup_source }} at {{ $conversation->last_message_at ? $conversation->last_message_at->format('M d, Y') : 'unknown' }}.
            </p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    @keyframes slide-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-up { animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .animate-fade-in { animation: fade-in 0.3s ease-out; }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.05);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(0,0,0,0.1);
    }
</style>
@endpush
