@extends('layouts.app')
@section('title', 'Deals Pipeline')
@section('heading', 'Deals Pipeline')

@section('header-actions')
    <a href="{{ route('admin.pipelines.create') }}"
        class="px-5 py-3 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
        </svg>
        New Pipeline
    </a>
@endsection

@section('content')
<div x-data="pipelineManager()">
    <div class="space-y-6 animate-slide-up">
    @if($pipelines->isEmpty())
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-brand/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Pipelines Yet</h3>
            <p class="text-surface-500 text-sm mb-6">Create your first deal pipeline to start tracking your sales funnel.</p>
            <a href="{{ route('admin.pipelines.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Pipeline
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($pipelines as $pipeline)
                <a href="{{ route('admin.pipelines.show', $pipeline) }}" 
                   class="group block relative bg-white rounded-sm border border-surface-200 hover:border-brand/30 hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col h-full">
                    
                    {{-- Subtle top gradient bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand to-brand/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="p-6 flex-grow flex flex-col">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-sm bg-brand/5 text-brand flex items-center justify-center group-hover:bg-brand group-hover:text-white transition-colors duration-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-black text-surface-900 tracking-tight group-hover:text-brand transition-colors">{{ $pipeline->name }}</h3>
                                        <span class="px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider border {{ $pipeline->is_public ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-surface-200 bg-surface-50 text-surface-600' }}">
                                            {{ $pipeline->is_public ? 'Public' : 'Private' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Total Value:</span>
                                        <span class="text-xs font-black text-brand">{{ number_format($pipeline->deals_sum_value ?? 0) }} <span class="text-[8px]">{{ $pipeline->deals->first()->currency ?? 'INR' }}</span></span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Actions menu --}}
                            <div class="relative" x-data="{ openMenu: false }" @click.away="openMenu = false">
                                <button @click.prevent="openMenu = !openMenu" class="w-8 h-8 rounded-sm hover:bg-surface-50 flex items-center justify-center text-surface-400 hover:text-surface-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01" />
                                    </svg>
                                </button>
                                <div x-show="openMenu" class="absolute right-0 mt-1 w-44 bg-white border border-surface-200 rounded-sm z-30 py-1 shadow-none" style="display: none;">
                                    <button @click.prevent="activePipeline = {{ json_encode($pipeline) }}; isPublic = {{ $pipeline->is_public ? 'true' : 'false' }}; permissions = {{ json_encode(array_merge(['add_deal' => true, 'edit_deal' => true, 'delete_deal' => true, 'move_deal' => true], $pipeline->team_permissions ?? [])) }}; monthlyTarget = '{{ $pipeline->monthly_target }}'; rottingDays = '{{ $pipeline->rotting_days }}'; openSettings = true; openMenu = false" class="w-full text-left px-4 py-2 text-xs text-surface-700 hover:bg-surface-50 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Settings
                                    </button>
                                    <button @click.prevent="activePipeline = {{ json_encode($pipeline) }}; openTransfer = true; openMenu = false" class="w-full text-left px-4 py-2 text-xs text-surface-700 hover:bg-surface-50 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        Transfer Ownership
                                    </button>
                                    <div class="border-t border-surface-100 my-1"></div>
                                    <button @click.prevent="activePipeline = {{ json_encode($pipeline) }}; openDelete = true; openMenu = false" class="w-full text-left px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Mini deals list --}}
                        <div class="mb-6 flex-grow">
                            <div class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-3 flex items-center justify-between">
                                <span>Recent Deals</span>
                                <span class="text-surface-600 font-black">{{ $pipeline->deals_count }} total</span>
                            </div>
                            <div class="space-y-2.5">
                                @forelse($pipeline->recent_deals->take(3) as $deal)
                                    <div class="flex items-center justify-between p-2.5 bg-surface-50 rounded-sm border border-surface-200">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-xs font-bold text-surface-900 truncate" title="{{ $deal->title }}">
                                                {{ $deal->title }}
                                            </span>
                                            <span class="text-[9px] text-surface-400 font-semibold truncate mt-0.5">
                                                {{ $deal->contact->name ?? $deal->contact->email ?? 'No contact' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span class="text-[10px] font-black text-brand">
                                                {{ number_format($deal->value) }} <span class="text-[8px]">{{ $deal->currency }}</span>
                                            </span>
                                            @php
                                                $statusColors = [
                                                    'open' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                    'won' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'lost' => 'bg-rose-50 text-rose-700 border-rose-200'
                                                ];
                                                $statusColor = $statusColors[$deal->status] ?? 'bg-surface-50 text-surface-700 border-surface-200';
                                            @endphp
                                            <span class="px-1.5 py-0.5 rounded-sm text-[8px] font-black border uppercase tracking-wider {{ $statusColor }}">
                                                {{ $deal->status }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-6 text-xs text-surface-400 border border-dashed border-surface-200 rounded-sm">
                                        No deals added yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="mt-auto border-t border-surface-100 pt-4 flex items-center justify-between text-xs text-surface-500 font-bold">
                            <div>
                                <span>{{ $pipeline->stages->count() }} Stages</span>
                            </div>
                            <div>
                                <span>Owner: {{ $pipeline->creator->name ?? 'System' }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

    {{-- MODALS --}}
    
    <!-- Settings Modal -->
    <div x-show="openSettings" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-surface-900/40 transition-opacity" @click="openSettings = false"></div>
        <div class="relative bg-white rounded-sm border border-surface-200 max-w-md w-full p-6 z-10">
            <h3 class="text-lg font-black text-surface-900 mb-4">Pipeline Settings</h3>
            <div class="space-y-4 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-surface-400 mb-2">Monthly Target (₹)</label>
                        <input type="number" x-model="monthlyTarget" min="0" step="0.01" class="w-full text-xs rounded-sm border-surface-200 focus:ring-0 focus:border-brand" placeholder="e.g. 50000">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-surface-400 mb-2">Rotting Threshold (Days)</label>
                        <input type="number" x-model="rottingDays" min="1" class="w-full text-xs rounded-sm border-surface-200 focus:ring-0 focus:border-brand" placeholder="e.g. 14">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-surface-400 mb-2">Visibility</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-xs font-bold text-surface-700 cursor-pointer">
                            <input type="radio" :value="true" x-model="isPublic" class="text-brand focus:ring-0"> Public
                        </label>
                        <label class="flex items-center gap-2 text-xs font-bold text-surface-700 cursor-pointer">
                            <input type="radio" :value="false" x-model="isPublic" class="text-brand focus:ring-0"> Private
                        </label>
                    </div>
                    <span class="text-[10px] text-surface-400 block mt-1">Public pipelines can be accessed by other team members based on the permissions below.</span>
                </div>
                
                <div x-show="isPublic">
                    <label class="block text-xs font-bold uppercase tracking-wider text-surface-400 mb-2">Team Permissions</label>
                    <div class="space-y-2.5">
                        <label class="flex items-center gap-2 text-xs font-semibold text-surface-700 cursor-pointer">
                            <input type="checkbox" x-model="permissions.add_deal" class="rounded-sm border-surface-200 text-brand focus:ring-0"> Add Deals
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-surface-700 cursor-pointer">
                            <input type="checkbox" x-model="permissions.edit_deal" class="rounded-sm border-surface-200 text-brand focus:ring-0"> Edit Deals
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-surface-700 cursor-pointer">
                            <input type="checkbox" x-model="permissions.delete_deal" class="rounded-sm border-surface-200 text-brand focus:ring-0"> Delete Deals
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-surface-700 cursor-pointer">
                            <input type="checkbox" x-model="permissions.move_deal" class="rounded-sm border-surface-200 text-brand focus:ring-0"> Move/Drag Deals
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="openSettings = false" class="px-4 py-2 border border-surface-200 rounded-sm text-xs font-bold text-surface-600 hover:bg-surface-50">Cancel</button>
                <button @click="updateSettings()" :disabled="isLoading" class="px-4 py-2 bg-brand text-white rounded-sm text-xs font-bold hover:bg-brand/90 disabled:opacity-50">
                    <span x-text="isLoading ? 'Saving...' : 'Save Settings'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Transfer Ownership Modal -->
    <div x-show="openTransfer" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-surface-900/40 transition-opacity" @click="openTransfer = false"></div>
        <div class="relative bg-white rounded-sm border border-surface-200 max-w-sm w-full p-6 z-10">
            <h3 class="text-lg font-black text-surface-900 mb-2">Transfer Ownership</h3>
            <p class="text-xs text-surface-500 mb-4">Choose a team member to transfer ownership of this pipeline. You will lose owner privileges.</p>
            
            <div class="mb-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-surface-400 mb-2">Select Team Member</label>
                <select x-model="newOwnerId" class="w-full rounded-sm border-surface-200 text-xs focus:ring-0">
                    <option value="">-- Choose Member --</option>
                    @foreach($teamMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->role }})</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex justify-end gap-3">
                <button @click="openTransfer = false" class="px-4 py-2 border border-surface-200 rounded-sm text-xs font-bold text-surface-600 hover:bg-surface-50">Cancel</button>
                <button @click="transferOwnership()" :disabled="isLoading" class="px-4 py-2 bg-brand text-white rounded-sm text-xs font-bold hover:bg-brand/90 disabled:opacity-50">
                    <span x-text="isLoading ? 'Transferring...' : 'Transfer'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="openDelete" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="display: none;">
        <div class="fixed inset-0 bg-surface-900/40 transition-opacity" @click="openDelete = false"></div>
        <div class="relative bg-white rounded-sm border border-rose-200 max-w-sm w-full p-6 z-10">
            <h3 class="text-lg font-black text-rose-600 mb-2">Delete Pipeline</h3>
            <p class="text-xs text-surface-500 mb-6">Are you sure you want to permanently delete this pipeline? All deals and stages inside it will be permanently deleted. This action cannot be undone.</p>
            
            <div class="flex justify-end gap-3">
                <button @click="openDelete = false" class="px-4 py-2 border border-surface-200 rounded-sm text-xs font-bold text-surface-600 hover:bg-surface-50">Cancel</button>
                <button @click="deletePipeline()" :disabled="isLoading" class="px-4 py-2 bg-rose-600 text-white rounded-sm text-xs font-bold hover:bg-rose-700 disabled:opacity-50">
                    <span x-text="isLoading ? 'Deleting...' : 'Confirm Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pipelineManager() {
    return {
        activePipeline: null,
        openSettings: false,
        openTransfer: false,
        openDelete: false,
        isPublic: true,
        permissions: {
            add_deal: false,
            edit_deal: false,
            delete_deal: false,
            move_deal: false
        },
        monthlyTarget: 0,
        rottingDays: 14,
        newOwnerId: '',
        isLoading: false,

        updateSettings() {
            this.isLoading = true;
            const url = `{{ route('admin.pipelines.index') }}/${this.activePipeline.id}/settings`;
            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    is_public: this.isPublic ? 1 : 0,
                    team_permissions: this.permissions,
                    monthly_target: this.monthlyTarget,
                    rotting_days: this.rottingDays
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Something went wrong');
                }
            })
            .catch(() => alert('Network error'))
            .finally(() => this.isLoading = false);
        },

        transferOwnership() {
            if (!this.newOwnerId) {
                alert('Please select a new owner.');
                return;
            }
            this.isLoading = true;
            const url = `{{ route('admin.pipelines.index') }}/${this.activePipeline.id}/transfer`;
            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    new_owner_id: this.newOwnerId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Something went wrong');
                }
            })
            .catch(() => alert('Network error'))
            .finally(() => this.isLoading = false);
        },

        deletePipeline() {
            this.isLoading = true;
            const url = `{{ route('admin.pipelines.index') }}/${this.activePipeline.id}`;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Something went wrong');
                }
            })
            .catch(() => alert('Network error'))
            .finally(() => this.isLoading = false);
        }
    }
}
</script>
@endpush
@endsection
