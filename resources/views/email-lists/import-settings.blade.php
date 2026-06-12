@extends('layouts.app')
@section('title', 'Import Configuration')

@section('header-actions')
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-sm bg-brand text-white flex items-center justify-center font-black">3</div>
        <div>
            <h3 class="text-lg font-black text-surface-900 tracking-tight uppercase">Import Configuration</h3>
            <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest">Assign tags and choose subscription topics</p>
        </div>
    </div>
    <div class="hidden md:flex items-center gap-2">
        <div class="h-1 w-24 bg-brand rounded-full"></div>
        <div class="h-1 w-24 bg-brand rounded-full"></div>
        <div class="h-1 w-24 bg-brand rounded-full"></div>
    </div>
@endsection

@section('content')
    <div x-data="importSettingsAssistant()" class="max-w-4xl mx-auto space-y-6">
        <form action="{{ route('admin.email-lists.start-import', $emailList->id) }}" method="POST" id="settings-form">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 1. Subscription Topics Card --}}
                <div class="bg-white border border-color rounded-sm p-6 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-color pb-3">
                            <h4 class="text-sm font-black text-surface-900 uppercase tracking-widest">Subscription Topics</h4>
                            <span class="px-2 py-0.5 bg-brand/10 text-brand text-[9px] font-black rounded-sm uppercase" x-text="topics.length + ' Available'"></span>
                        </div>
                        <p class="text-[10px] text-surface-400 font-medium uppercase mt-2">Select topics that active contacts will be subscribed to:</p>
                        
                        <div class="space-y-2 mt-4 max-h-64 overflow-y-auto pr-2">
                            <template x-for="topic in topics" :key="topic.id">
                                <div class="flex items-center justify-between p-2.5 bg-surface-50 border border-color rounded-sm group hover:border-surface-300 transition-all">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <input type="checkbox" name="topics[]" :value="topic.id" checked
                                            class="w-4 h-4 rounded-sm border-gray-300 text-brand focus:ring-brand">
                                        <span class="text-xs font-bold text-surface-700" x-text="topic.name"></span>
                                    </label>
                                    <button type="button" @click="deleteTopic(topic.id)"
                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-all p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Add Topic Inline --}}
                    <div class="border-t border-color pt-4 mt-4 space-y-3">
                        <h5 class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Add New Topic</h5>
                        <div class="flex gap-2">
                            <input type="text" x-model="newTopicName" placeholder="Enter topic name..."
                                class="flex-1 px-3 py-2 border border-color rounded-sm text-xs outline-none focus:border-brand">
                            <button type="button" @click="createTopic()" :disabled="!newTopicName.trim()"
                                class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-sm hover:bg-black transition-all disabled:opacity-50">
                                Create
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 2. Tags Configuration Card --}}
                <div class="bg-white border border-color rounded-sm p-6 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-color pb-3">
                            <h4 class="text-sm font-black text-surface-900 uppercase tracking-widest">Import Tags</h4>
                            <span class="px-2 py-0.5 bg-brand/10 text-brand text-[9px] font-black rounded-sm uppercase" x-text="tags.length + ' Existing'"></span>
                        </div>
                        <p class="text-[10px] text-surface-400 font-medium uppercase mt-2">Select existing tags or add new tags to apply to all imported contacts:</p>
                        
                        <div class="grid grid-cols-2 gap-2 mt-4 max-h-64 overflow-y-auto pr-2">
                            <template x-for="tag in tags" :key="tag">
                                <div class="flex items-center justify-between p-2 bg-surface-50 border border-color rounded-sm group hover:border-surface-300 transition-all">
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox" name="tags[]" :value="tag"
                                            class="w-3.5 h-3.5 rounded-sm border-gray-300 text-brand focus:ring-brand">
                                        <span class="text-[11px] font-bold text-surface-700 truncate max-w-[100px]" x-text="tag"></span>
                                    </label>
                                    <button type="button" @click="deleteTag(tag)"
                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-all p-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Add New Tags Input --}}
                    <div class="border-t border-color pt-4 mt-4 space-y-2">
                        <h5 class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Apply New Tags</h5>
                        <input type="text" name="new_tags" placeholder="VIP, New Leads, Comma Separated..."
                            class="w-full px-3 py-2 border border-color rounded-sm text-xs outline-none focus:border-brand">
                    </div>
                </div>
            </div>

            {{-- Footer / Action Bar --}}
            <div class="mt-6 p-6 bg-white border border-color rounded-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">
                        Check configurations carefully. This import will run as a background task.
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.email-lists.show', $emailList->id) }}"
                        class="text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-900 transition-colors">Cancel</a>
                    <button type="submit"
                        class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-10 py-3.5 rounded-sm hover:bg-black transition-all">
                        Launch Import
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function importSettingsAssistant() {
            return {
                topics: @json($topics),
                tags: @json($uniqueTags),
                newTopicName: '',
                
                async createTopic() {
                    if (!this.newTopicName.trim()) return;
                    
                    try {
                        const response = await fetch("{{ route('admin.email-lists.ajax-create-topic', $emailList->id) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ name: this.newTopicName })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.topics.push(data.topic);
                            this.newTopicName = '';
                        }
                    } catch (e) {
                        alert('Failed to create topic');
                    }
                },
                
                async deleteTopic(id) {
                    if (!confirm('Are you sure you want to delete this topic? It will be removed from this list.')) return;
                    
                    try {
                        const response = await fetch(`{{ url('/email-lists/' . $emailList->id . '/ajax-delete-topic') }}/${id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.topics = this.topics.filter(t => t.id !== id);
                        }
                    } catch (e) {
                        alert('Failed to delete topic');
                    }
                },
                
                async deleteTag(tag) {
                    if (!confirm(`Are you sure you want to delete tag "${tag}"? It will be removed from all contacts in this list.`)) return;
                    
                    try {
                        const response = await fetch("{{ route('admin.email-lists.ajax-delete-tag', $emailList->id) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ tag: tag })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.tags = this.tags.filter(t => t !== tag);
                        }
                    } catch (e) {
                        alert('Failed to delete tag');
                    }
                }
            };
        }
    </script>
@endsection
