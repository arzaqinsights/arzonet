@extends('layouts.app')
@section('title', 'Tags Management')
@section('heading', 'Tags Management')

@section('content')
<div class="space-y-6 animate-slide-up" x-data="{
    showRenameModal: false,
    showMergeModal: false,
    showDeleteModal: false,
    selectedTag: '',
    newName: '',
    mergeTarget: '',
    openRename(tagName) {
        this.selectedTag = tagName;
        this.newName = tagName;
        this.showRenameModal = true;
    },
    openMerge(tagName) {
        this.selectedTag = tagName;
        this.mergeTarget = '';
        this.showMergeModal = true;
    },
    openDelete(tagName) {
        this.selectedTag = tagName;
        this.showDeleteModal = true;
    }
}">

    @if(empty($tags))
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-indigo-50 flex items-center justify-center">
                <i class="fa-solid fa-tags text-indigo-500 text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Tags Found</h3>
            <p class="text-surface-500 text-sm">Add tags to contacts inside your contacts table list to see them here.</p>
        </div>
    @else
        <div class="glass-card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tag Name</th>
                        <th class="text-center">Tagged Contacts</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tags as $t)
                        <tr class="group">
                            <td class="font-bold text-surface-900">
                                <span class="badge badge-brand">{{ $t['name'] }}</span>
                            </td>
                            <td class="text-center font-bold text-surface-900">
                                {{ number_format($t['contact_count']) }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openRename('{{ addslashes($t['name']) }}')" class="btn btn-sm btn-ghost">Rename</button>
                                    <button @click="openMerge('{{ addslashes($t['name']) }}')" class="btn btn-sm btn-ghost">Merge</button>
                                    <button @click="openDelete('{{ addslashes($t['name']) }}')" class="btn btn-sm btn-ghost text-red-600">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Rename Modal --}}
    <div x-show="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 animate-fade-in" x-cloak>
        <div class="bg-white border border-gray-200 rounded-sm w-full max-w-md overflow-hidden shadow-xl" @click.away="showRenameModal = false">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Rename Tag</h3>
                <button @click="showRenameModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('admin.tags.rename') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="old_name" :value="selectedTag">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Name</label>
                    <div class="px-3 py-2 bg-gray-100 rounded-sm text-sm font-bold text-gray-600" x-text="selectedTag"></div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">New Name</label>
                    <input type="text" name="new_name" x-model="newName" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showRenameModal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Rename Tag</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Merge Modal --}}
    <div x-show="showMergeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 animate-fade-in" x-cloak>
        <div class="bg-white border border-gray-200 rounded-sm w-full max-w-md overflow-hidden shadow-xl" @click.away="showMergeModal = false">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Merge Tag</h3>
                <button @click="showMergeModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('admin.tags.merge') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="source_tag" :value="selectedTag">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Source Tag</label>
                    <div class="px-3 py-2 bg-gray-100 rounded-sm text-sm font-bold text-gray-600" x-text="selectedTag"></div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Destination Tag (Merge Into)</label>
                    <select name="target_tag" x-model="mergeTarget" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-sm text-sm font-bold text-gray-900 focus:border-brand focus:ring-0 outline-none">
                        <option value="">-- Select Target Tag --</option>
                        @foreach($tags as $t)
                            <option value="{{ $t['name'] }}" x-show="selectedTag !== '{{ addslashes($t['name']) }}'">{{ $t['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showMergeModal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="!mergeTarget">Merge Tags</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 animate-fade-in" x-cloak>
        <div class="bg-white border border-gray-200 rounded-sm w-full max-w-md overflow-hidden shadow-xl" @click.away="showDeleteModal = false">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Delete Tag</h3>
                <button @click="showDeleteModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('admin.tags.delete') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="tag" :value="selectedTag">
                <p class="text-sm text-surface-600">Are you sure you want to delete the tag "<span class="font-bold" x-text="selectedTag"></span>" from all contacts in this list?</p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showDeleteModal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Tag</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
