@extends('layouts.app')
@section('title', 'Signup Forms')
@section('heading', 'Signup Forms')

@section('header-actions')
    <a href="{{ route('admin.signup-forms.create') }}"
        class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
        New Form
    </a>
@endsection

@section('content')
<div class="space-y-6 animate-slide-up" x-data="{
    copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    }
}">

    @if($forms->isEmpty())
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-indigo-50 flex items-center justify-center">
                <i class="fa-solid fa-rectangle-list text-indigo-500 text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Signup Forms Yet</h3>
            <p class="text-surface-500 text-sm mb-6">Design dynamic signup forms to grow your email lists.</p>
            <a href="{{ route('admin.signup-forms.create') }}" class="btn btn-primary">Create Form</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($forms as $form)
                <div class="glass-card p-6 flex flex-col justify-between space-y-4 hover:border-brand/30 transition-all">
                    <div>
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-black text-surface-900 leading-tight">{{ $form->name }}</h3>
                                <p class="text-xs text-surface-500 mt-1">Token: <code class="bg-gray-100 px-1 py-0.5 rounded text-[10px]">{{ $form->token }}</code></p>
                            </div>
                            <span class="w-4 h-4 rounded-full" style="background-color: {{ $form->theme_color }}"></span>
                        </div>
                        <p class="text-sm text-surface-600 mt-3">{{ Str::limit($form->description, 120) }}</p>
                    </div>

                    <div class="space-y-3 bg-gray-50/50 p-4 border border-gray-100 rounded-sm">
                        <div>
                            <span class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Public Share Link</span>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly value="{{ route('public.forms.show', $form->token) }}" 
                                    class="w-full text-xs font-mono bg-white border border-gray-200 px-2 py-1.5 rounded-sm outline-none">
                                <button @click="copyToClipboard('{{ route('public.forms.show', $form->token) }}')" class="btn btn-xs btn-outline whitespace-nowrap">Copy</button>
                                <a href="{{ route('public.forms.show', $form->token) }}" target="_blank" class="btn btn-xs btn-primary whitespace-nowrap">Open</a>
                            </div>
                        </div>
                        <div>
                            <span class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Iframe Embed Code</span>
                            <div class="flex items-center gap-2">
                                @php
                                    $embedCode = '<iframe src="' . route('public.forms.show', $form->token) . '" width="100%" height="450px" frameborder="0"></iframe>';
                                @endphp
                                <input type="text" readonly value="{{ $embedCode }}" 
                                    class="w-full text-xs font-mono bg-white border border-gray-200 px-2 py-1.5 rounded-sm outline-none">
                                <button @click="copyToClipboard('{{ addslashes($embedCode) }}')" class="btn btn-xs btn-outline">Copy</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <span class="badge badge-brand">{{ count($form->custom_fields ?? []) }} custom fields</span>
                            <span class="badge badge-neutral">{{ count($form->subscribed_topics ?? []) }} topics</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.signup-forms.edit', $form) }}" class="btn btn-sm btn-ghost">Edit</a>
                            <form action="{{ route('admin.signup-forms.destroy', $form) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this signup form?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost text-red-600 cursor-pointer">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
