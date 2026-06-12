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
    <div class="max-w-4xl mx-auto space-y-6">
        <form action="{{ route('admin.email-lists.start-import', $emailList->id) }}" method="POST" id="settings-form">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 1. Subscription Topics Card --}}
                <div x-data="topicCombobox()" class="bg-white border border-color rounded-sm p-6 space-y-4 flex flex-col justify-between min-h-[350px]">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-color pb-3">
                            <h4 class="text-sm font-black text-surface-900 uppercase tracking-widest">Subscription Topics</h4>
                            <span class="px-2 py-0.5 bg-brand/10 text-brand text-[9px] font-black rounded-sm uppercase" x-text="selected.length + ' Selected'"></span>
                        </div>
                        <p class="text-[10px] text-surface-400 font-bold uppercase">Search & Choose Subscription Topics</p>
                        
                        {{-- Combobox Input --}}
                        <div class="relative" @click.away="open = false">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" x-model="search" @focus="open = true" @keydown.escape="open = false" @keydown.enter.prevent="createOrSelect()" placeholder="Search or type new topic..."
                                        class="w-full px-3 py-2 border border-color rounded-sm text-xs outline-none focus:border-brand pr-8">
                                    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <button type="button" @click="createOrSelect()" :disabled="!search.trim()"
                                    class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-sm hover:bg-black transition-all disabled:opacity-50">
                                    Add
                                </button>
                            </div>
                            
                            {{-- Dropdown Menu --}}
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 bg-white border border-color rounded-sm shadow-lg max-h-60 overflow-y-auto" style="display: none;">
                                <template x-if="filtered.length === 0">
                                    <div class="px-3 py-2.5 text-xs text-surface-400 font-bold">
                                        No topics found. Press Enter or click "Add" to create topic <span class="text-brand font-black" x-text="'&quot;' + search + '&quot;'"></span>.
                                    </div>
                                </template>
                                <template x-for="topic in filtered" :key="topic.id">
                                    <div class="flex items-center justify-between px-3 py-2 text-xs font-bold cursor-pointer transition-colors group hover:bg-surface-50">
                                        <div class="flex items-center gap-2 flex-1 py-1" @click="toggle(topic)">
                                            <input type="checkbox" :checked="isSelected(topic.id)" class="rounded-sm border-gray-300 text-brand focus:ring-brand pointer-events-none">
                                            <span class="text-surface-700" x-text="topic.name"></span>
                                        </div>
                                        <button type="button" @click.stop="remove(topic.id)"
                                            class="text-red-500 opacity-0 group-hover:opacity-100 hover:text-red-700 transition-all p-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Selected Pills Container --}}
                        <div class="flex flex-wrap gap-1.5 mt-2 max-h-36 overflow-y-auto pr-1">
                            <template x-for="topic in selected" :key="topic.id">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-brand/5 border border-brand/10 text-brand text-xs font-bold rounded-sm">
                                    <span x-text="topic.name"></span>
                                    <button type="button" @click="toggle(topic)" class="hover:text-black text-brand/60 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="topics[]" :value="topic.id">
                                </span>
                            </template>
                            <template x-if="selected.length === 0">
                                <div class="text-[10px] text-surface-400 font-bold uppercase py-2">No topics selected. Contacts will not be subscribed to any topics.</div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- 2. Tags Configuration Card --}}
                <div x-data="tagCombobox()" class="bg-white border border-color rounded-sm p-6 space-y-4 flex flex-col justify-between min-h-[350px]">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-color pb-3">
                            <h4 class="text-sm font-black text-surface-900 uppercase tracking-widest">Import Tags</h4>
                            <span class="px-2 py-0.5 bg-brand/10 text-brand text-[9px] font-black rounded-sm uppercase" x-text="selected.length + ' Selected'"></span>
                        </div>
                        <p class="text-[10px] text-surface-400 font-bold uppercase">Search & Choose Import Tags</p>
                        
                        {{-- Combobox Input --}}
                        <div class="relative" @click.away="open = false">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" x-model="search" @focus="open = true" @keydown.escape="open = false" @keydown.enter.prevent="createOrSelect()" placeholder="Search or type new tag..."
                                        class="w-full px-3 py-2 border border-color rounded-sm text-xs outline-none focus:border-brand pr-8">
                                    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <button type="button" @click="createOrSelect()" :disabled="!search.trim()"
                                    class="bg-brand text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-sm hover:bg-black transition-all disabled:opacity-50">
                                    Add
                                </button>
                            </div>
                            
                            {{-- Dropdown Menu --}}
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 bg-white border border-color rounded-sm shadow-lg max-h-60 overflow-y-auto" style="display: none;">
                                <template x-if="filtered.length === 0">
                                    <div class="px-3 py-2.5 text-xs text-surface-400 font-bold">
                                        No tags found. Press Enter or click "Add" to create tag <span class="text-brand font-black" x-text="'&quot;' + search + '&quot;'"></span>.
                                    </div>
                                </template>
                                <template x-for="tag in filtered" :key="tag">
                                    <div class="flex items-center justify-between px-3 py-2 text-xs font-bold cursor-pointer transition-colors group hover:bg-surface-50">
                                        <div class="flex items-center gap-2 flex-1 py-1" @click="toggle(tag)">
                                            <input type="checkbox" :checked="isSelected(tag)" class="rounded-sm border-gray-300 text-brand focus:ring-brand pointer-events-none">
                                            <span class="text-surface-700 truncate max-w-[150px]" x-text="tag"></span>
                                        </div>
                                        <button type="button" @click.stop="remove(tag)"
                                            class="text-red-500 opacity-0 group-hover:opacity-100 hover:text-red-700 transition-all p-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Selected Pills Container --}}
                        <div class="flex flex-wrap gap-1.5 mt-2 max-h-36 overflow-y-auto pr-1">
                            <template x-for="tag in selected" :key="tag">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-brand/5 border border-brand/10 text-brand text-xs font-bold rounded-sm">
                                    <span x-text="tag"></span>
                                    <button type="button" @click="toggle(tag)" class="hover:text-black text-brand/60 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <input type="hidden" name="tags[]" :value="tag">
                                </span>
                            </template>
                            <template x-if="selected.length === 0">
                                <div class="text-[10px] text-surface-400 font-bold uppercase py-2">No tags selected. Contacts will be imported without tags.</div>
                            </template>
                        </div>
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
        function topicCombobox() {
            return {
                open: false,
                search: '',
                available: @json($topics),
                selected: @json($topics),
                
                isSelected(id) {
                    return this.selected.some(t => t.id === id);
                },
                
                toggle(topic) {
                    if (this.isSelected(topic.id)) {
                        this.selected = this.selected.filter(t => t.id !== topic.id);
                    } else {
                        this.selected.push(topic);
                    }
                },
                
                async createOrSelect() {
                    const name = this.search.trim();
                    if (!name) return;
                    
                    const existing = this.available.find(t => t.name.toLowerCase() === name.toLowerCase());
                    if (existing) {
                        if (!this.isSelected(existing.id)) {
                            this.selected.push(existing);
                        }
                        this.search = '';
                        this.open = false;
                        return;
                    }
                    
                    try {
                        const response = await fetch("{{ route('admin.email-lists.ajax-create-topic', $emailList->id) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ name: name })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.available.push(data.topic);
                            this.selected.push(data.topic);
                            this.search = '';
                            this.open = false;
                        }
                    } catch (e) {
                        alert('Failed to create topic');
                    }
                },
                
                async remove(id) {
                    if (!confirm('Are you sure you want to delete this topic permanently from the list?')) return;
                    
                    try {
                        const response = await fetch(`{{ url('/email-lists/' . $emailList->id . '/ajax-delete-topic') }}/${id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.available = this.available.filter(t => t.id !== id);
                            this.selected = this.selected.filter(t => t.id !== id);
                        }
                    } catch (e) {
                        alert('Failed to delete topic');
                    }
                },
                
                get filtered() {
                    if (!this.search.trim()) return this.available;
                    return this.available.filter(t => t.name.toLowerCase().includes(this.search.toLowerCase()));
                }
            };
        }
        
        function tagCombobox() {
            return {
                open: false,
                search: '',
                available: @json($uniqueTags),
                selected: [],
                
                isSelected(tag) {
                    return this.selected.includes(tag);
                },
                
                toggle(tag) {
                    if (this.isSelected(tag)) {
                        this.selected = this.selected.filter(t => t !== tag);
                    } else {
                        this.selected.push(tag);
                    }
                },
                
                createOrSelect() {
                    const tag = this.search.trim();
                    if (!tag) return;
                    
                    if (!this.isSelected(tag)) {
                        this.selected.push(tag);
                    }
                    
                    if (!this.available.includes(tag)) {
                        this.available.push(tag);
                    }
                    
                    this.search = '';
                    this.open = false;
                },
                
                async remove(tag) {
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
                            this.available = this.available.filter(t => t !== tag);
                            this.selected = this.selected.filter(t => t !== tag);
                        }
                    } catch (e) {
                        alert('Failed to delete tag');
                    }
                },
                
                get filtered() {
                    if (!this.search.trim()) return this.available;
                    return this.available.filter(t => t.toLowerCase().includes(this.search.toLowerCase()));
                }
            };
        }
    </script>
@endsection
