{{-- Partial for recursive node rendering --}}
<div class="flex flex-col items-center" x-data="{ nodeId: '{{ $nodeId }}' }">
    <template x-if="nodes['{{ $nodeId }}']">
        <div class="flex flex-col items-center w-full relative group">
            {{-- Connecting Line --}}
            <div class="w-0.5 h-10 bg-brand/20 relative">
                <div class="absolute bottom-0 -left-1.5 transform translate-y-1/2">
                    <svg class="w-4.5 h-4.5 text-brand/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            {{-- The Node Card --}}
            <div class="w-full max-w-xs bg-white border border-surface-200 rounded-lg p-5 shadow-sm relative space-y-3 transition-all hover:border-brand/50" :class="{'ring-2 ring-brand': activeNode === '{{ $nodeId }}'}">
                <div class="absolute -top-3 left-4 px-3 py-0.5 text-white text-[9px] font-black uppercase tracking-widest rounded-full shadow-sm"
                     :class="{
                         'bg-amber-500': nodes['{{ $nodeId }}'].type === 'wait',
                         'bg-blue-500': nodes['{{ $nodeId }}'].type === 'send_email',
                         'bg-emerald-500': nodes['{{ $nodeId }}'].type === 'tag',
                         'bg-purple-500': nodes['{{ $nodeId }}'].type === 'if_else',
                         'bg-gray-700': !['wait', 'send_email', 'tag', 'if_else'].includes(nodes['{{ $nodeId }}'].type)
                     }">
                    <span x-text="getNodeTypeLabel(nodes['{{ $nodeId }}'].type)"></span>
                </div>
                
                <div class="flex justify-between items-start pt-2">
                    <div class="text-xs font-semibold text-surface-800" x-html="getNodeDescription(nodes['{{ $nodeId }}'])"></div>
                    <div class="flex gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button type="button" @click.stop="editNode('{{ $nodeId }}')" class="p-1 text-surface-400 hover:text-brand hover:bg-brand/5 rounded transition-colors" title="Edit Step">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" @click.stop="deleteNode('{{ $nodeId }}')" class="p-1 text-surface-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" title="Remove Step">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Branching --}}
            <template x-if="nodes['{{ $nodeId }}'].type === 'if_else'">
                <div class="flex w-full items-start justify-center gap-12 mt-2 relative">
                    <!-- True Branch -->
                    <div class="flex flex-col items-center flex-1 border-t-2 border-green-200 pt-4 relative mt-4">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-green-100 text-green-800 border border-green-200 px-2 py-0.5 text-[9px] font-black rounded uppercase">Yes</div>
                        
                        <template x-if="nodes['{{ $nodeId }}'].next_true">
                            <div class="w-full flex justify-center" x-html="renderNodeHtml(nodes['{{ $nodeId }}'].next_true)"></div>
                        </template>
                        
                        <div class="pt-6 pb-2" x-show="!nodes['{{ $nodeId }}'].next_true">
                            <button type="button" @click="openAddNodeModal('{{ $nodeId }}', 'true')" class="w-8 h-8 rounded-full bg-surface-100 border border-surface-200 text-surface-400 flex items-center justify-center hover:bg-brand hover:text-white hover:border-brand transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- False Branch -->
                    <div class="flex flex-col items-center flex-1 border-t-2 border-red-200 pt-4 relative mt-4">
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 text-[9px] font-black rounded uppercase">No</div>
                        
                        <template x-if="nodes['{{ $nodeId }}'].next_false">
                            <div class="w-full flex justify-center" x-html="renderNodeHtml(nodes['{{ $nodeId }}'].next_false)"></div>
                        </template>
                        
                        <div class="pt-6 pb-2" x-show="!nodes['{{ $nodeId }}'].next_false">
                            <button type="button" @click="openAddNodeModal('{{ $nodeId }}', 'false')" class="w-8 h-8 rounded-full bg-surface-100 border border-surface-200 text-surface-400 flex items-center justify-center hover:bg-brand hover:text-white hover:border-brand transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Linear Next --}}
            <template x-if="nodes['{{ $nodeId }}'].type !== 'if_else'">
                <div class="flex flex-col items-center w-full">
                    <template x-if="nodes['{{ $nodeId }}'].next">
                        <div class="w-full flex justify-center" x-html="renderNodeHtml(nodes['{{ $nodeId }}'].next)"></div>
                    </template>
                    
                    <div class="pt-6 pb-2" x-show="!nodes['{{ $nodeId }}'].next">
                        <button type="button" @click="openAddNodeModal('{{ $nodeId }}', 'next')" class="w-8 h-8 rounded-full bg-surface-100 border border-surface-200 text-surface-400 flex items-center justify-center hover:bg-brand hover:text-white hover:border-brand transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
