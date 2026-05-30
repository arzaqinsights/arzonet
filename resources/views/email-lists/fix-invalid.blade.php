@extends('layouts.app')

@section('title', 'Fix Invalid Records — ' . $emailList->name)

@section('heading')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.email-lists.show', $emailList) }}"
            class="p-2 hover:bg-surface-100 rounded-sm transition-colors text-surface-400 hover:text-surface-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <span>Fix Invalid Records</span>
        <span class="text-surface-300 mx-2">/</span>
        <span class="text-surface-500 font-medium text-base">{{ $emailList->name }}</span>
    </div>
@endsection

@section('content')
    <div x-data="fixInvalidAssistant()" class="min-h-screen">
        {{-- Header Actions --}}
        <div class="mb-8 flex items-center justify-between bg-white p-6 rounded-sm border border-gray-100">
            <div>
                <h2 class="text-2xl font-black text-surface-900 tracking-tight">Manual Syntax Correction</h2>
                <p class="text-xs text-surface-400 font-bold uppercase tracking-widest mt-1">
                    Showing <span class="text-brand" x-text="records.length"></span> records with syntax errors (Typos)
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Delete All Button --}}
                <button @click="deleteAll()" :disabled="deleting || saving || records.length === 0"
                    class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-sm font-black text-xs uppercase tracking-[0.2em] transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg x-show="!deleting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <svg x-show="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span x-text="deleting ? 'Deleting...' : 'Delete All'"></span>
                </button>

                {{-- Save & Re-Validate --}}
                <button @click="saveAll()" :disabled="saving || deleting || records.length === 0"
                    class="bg-brand hover:bg-brand/90 text-white px-8 py-3 rounded-sm font-black text-xs uppercase tracking-[0.2em] transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-3">
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span x-text="saving ? 'Validating & Saving...' : 'Save & Re-Validate'"></span>
                </button>
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="bg-white border border-gray-100 rounded-sm overflow-hidden">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-surface-50 border-b border-color text-[10px] uppercase tracking-widest text-surface-400 font-black">
                            <th class="px-8 py-5 w-16">#</th>
                            <th class="px-8 py-5 min-w-[350px]">Email Address (Correction)</th>
                            <th class="px-8 py-5 min-w-[250px]">Full Name</th>
                            <th class="px-8 py-5 text-red-500">Error Reason</th>
                            <th class="px-8 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(record, index) in records" :key="record.id">
                            <tr class="hover:bg-surface-50/50 transition-colors group">
                                <td class="px-8 py-5 text-xs font-black text-surface-300" x-text="index + 1"></td>
                                <td class="px-8 py-4">
                                    <div class="relative flex items-center">
                                        <input type="text" x-model="record.email" @input="record.edited = true"
                                            class="w-full bg-gray-50 border-gray-200 rounded-sm px-4 py-2.5 text-sm font-bold text-surface-900 focus:bg-white focus:border-brand focus:ring-4 focus:ring-brand/10 transition-all outline-none"
                                            :class="{'border-red-200 bg-red-50/30': !isValidEmail(record.email)}">
                                        <div x-show="isValidEmail(record.email) && record.edited" x-cloak
                                            class="absolute right-3 text-emerald-500">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-4">
                                    <input type="text" x-model="record.name"
                                        class="w-full bg-transparent border-none focus:bg-gray-50 focus:ring-0 rounded-sm px-4 py-2.5 text-sm font-medium text-surface-700 transition-all outline-none"
                                        placeholder="Contact Name">
                                </td>
                                <td class="px-8 py-4">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-red-400 bg-red-50 px-2.5 py-1 rounded-sm"
                                        x-text="record.reason"></span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <button @click="deleteRecord(record.id, index)" :disabled="saving || deleting"
                                        class="p-2 text-surface-300 hover:text-red-500 transition-colors cursor-pointer disabled:opacity-50"
                                        title="Delete Permanently from List">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Empty State --}}
                <template x-if="records.length === 0">
                    <div class="py-24 flex flex-col items-center justify-center text-center">
                        <div
                            class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-6 border-4 border-emerald-100 animate-bounce">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-surface-900">All Invalid Records Fixed!</h3>
                        <p class="text-surface-400 font-medium max-w-sm mx-auto mt-2">There are no more invalid records to
                            address in this list. You can return to the dashboard.</p>
                        <a href="{{ route('admin.email-lists.show', $emailList) }}"
                            class="mt-8 bg-surface-900 text-white px-8 py-3 rounded-sm font-black text-xs uppercase tracking-widest transition-all hover:bg-black">Back
                            to List</a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function fixInvalidAssistant() {
            return {
                records: @json($emails),
                saving: false,
                deleting: false,

                isValidEmail(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                },

                deleteRecord(id, index) {
                    if (!confirm('PERMANENT DELETE: This will remove this contact from the list completely. Proceed?')) return;

                    const url = `{{ route('admin.email-lists.destroy-email', [$emailList->id, 'EMAIL_ID']) }}`.replace('EMAIL_ID', id);

                    fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                this.records.splice(index, 1);
                            } else {
                                alert('Could not delete record.');
                            }
                        })
                        .catch(e => {
                            console.error(e);
                            alert('Error deleting record.');
                        });
                },

                async deleteAll() {
                    if (this.records.length === 0) return;
                    if (!confirm(`PERMANENT DELETE ALL: This will delete ALL ${this.records.length} invalid records from this list. This cannot be undone. Are you sure?`)) return;

                    this.deleting = true;
                    try {
                        const res = await fetch(`{{ route('admin.email-lists.fix-invalid.delete-all', $emailList) }}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            window.location.href = `{{ route('admin.email-lists.show', $emailList) }}`;
                        } else {
                            alert(data.message || 'Could not delete records.');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('An error occurred while deleting.');
                    } finally {
                        this.deleting = false;
                    }
                },

                saveAll() {
                    this.saving = true;

                    fetch(`{{ route('admin.email-lists.save-invalid', $emailList) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                emails: this.records
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                alert(`Done! Fixed: ${data.summary.fixed} | Still Invalid: ${data.summary.invalid} | Duplicates: ${data.summary.duplicate}`);

                                if (data.summary.invalid === 0) {
                                    window.location.href = `{{ route('admin.email-lists.show', $emailList) }}`;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                alert(data.message || 'Something went wrong.');
                            }
                        })
                        .catch(e => {
                            console.error(e);
                            alert('An error occurred while saving.');
                        })
                        .finally(() => {
                            this.saving = false;
                        });
                }
            }
        }
    </script>
@endsection