<div x-show="showProfileSlideover" class="fixed inset-0 z-[100] flex justify-end" x-cloak>
    <!-- Backdrop -->
    <div x-show="showProfileSlideover" x-transition.opacity class="fixed inset-0 bg-surface-900/60 backdrop-blur-sm" @click="closeProfile()"></div>

    <!-- Slide-over panel -->
    <div x-show="showProfileSlideover" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full" 
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300" 
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full" 
         class="w-full max-w-md bg-white shadow-2xl h-full flex flex-col relative z-10 border-l border-surface-200">
         
        <!-- Header -->
        <div class="p-6 border-b border-surface-100 flex items-center justify-between bg-surface-50">
            <div>
                <h2 class="text-lg font-black text-surface-900 tracking-tight" x-text="profileContact ? (profileContact.name || profileContact.email) : 'Loading...'"></h2>
                <p class="text-xs font-bold text-surface-500 uppercase tracking-widest mt-1" x-text="profileContact ? (profileContact.name ? profileContact.email : '') : ''"></p>
            </div>
            <button @click="closeProfile()" class="text-surface-400 hover:text-surface-900 transition-colors p-2 rounded-full hover:bg-surface-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <template x-if="profileLoading">
            <div class="flex-1 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </template>

        <template x-if="!profileLoading && profileContact">
            <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Tabs -->
                <div class="flex border-b border-surface-100 px-6 pt-4 space-x-6">
                    <button @click="profileActiveTab = 'details'" :class="{'border-brand text-brand': profileActiveTab === 'details', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'details'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors">Details</button>
                    <button @click="profileActiveTab = 'activity'" :class="{'border-brand text-brand': profileActiveTab === 'activity', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'activity'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors">Activity</button>
                    <button @click="profileActiveTab = 'notes'" :class="{'border-brand text-brand': profileActiveTab === 'notes', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'notes'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors">Notes</button>
                    <button @click="profileActiveTab = 'tasks'" :class="{'border-brand text-brand': profileActiveTab === 'tasks', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'tasks'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors">Tasks</button>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto p-6 bg-surface-50">
                    
                    <!-- DETAILS TAB -->
                    <div x-show="profileActiveTab === 'details'" class="space-y-6">
                        
                        <!-- Engagement Metrics -->
                        <div class="bg-white rounded-sm border border-surface-100 p-4 shadow-sm">
                            <h3 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-4">Engagement & Health</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Email Lead Score</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-lg font-black text-primary-600" x-text="(profileContact.email_lead_score || 1) + '/10'"></span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">WA Lead Score</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-lg font-black text-emerald-600" x-text="(profileContact.whatsapp_lead_score || 1) + '/10'"></span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Last Active</p>
                                    <p class="text-xs font-bold text-surface-900 mt-1" x-text="profileContact.last_active_at ? new Date(profileContact.last_active_at).toLocaleDateString() : 'Never'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Last Engaged</p>
                                    <p class="text-xs font-bold text-surface-900 mt-1" x-text="profileContact.last_engaged_at ? new Date(profileContact.last_engaged_at).toLocaleDateString() : 'Never'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- General Info -->
                        <div class="bg-white rounded-sm border border-surface-100 p-4 shadow-sm space-y-4">
                            <h3 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Contact Information</h3>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">Email Address</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.email"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">WhatsApp Number</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.whatsapp_number || '—'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">Added By</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.user ? profileContact.user.name : 'System'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIVITY TAB -->
                    <div x-show="profileActiveTab === 'activity'" class="space-y-4 relative">
                        <template x-if="profileContact.activities && profileContact.activities.length === 0">
                            <div class="text-center py-8 text-surface-400 text-xs font-bold">No recent activity.</div>
                        </template>
                        <div class="absolute left-[15px] top-2 bottom-2 w-px bg-surface-200"></div>
                        <template x-for="activity in profileContact.activities" :key="activity.id">
                            <div class="flex gap-4 relative z-10">
                                <div class="w-8 h-8 rounded-full bg-brand/10 border-2 border-white flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div class="pt-1.5 pb-4">
                                    <p class="text-xs font-bold text-surface-900" x-text="activity.type"></p>
                                    <p class="text-[10px] text-surface-500 mt-0.5" x-text="new Date(activity.created_at).toLocaleString()"></p>
                                    <template x-if="activity.meta && activity.meta.description">
                                        <p class="text-xs text-surface-600 mt-2 bg-white p-2 rounded-sm border border-surface-100" x-text="activity.meta.description"></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- NOTES TAB -->
                    <div x-show="profileActiveTab === 'notes'" class="space-y-4">
                        <div class="bg-white border border-surface-200 rounded-sm p-3 shadow-sm">
                            <textarea x-model="newNoteContent" rows="3" placeholder="Write a note..." class="w-full text-xs font-semibold text-surface-900 border-none focus:ring-0 p-0 resize-none"></textarea>
                            <div class="flex justify-end mt-2 pt-2 border-t border-surface-100">
                                <button @click="addNote()" :disabled="addingNote || !newNoteContent.trim()" class="bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-sm disabled:opacity-50 transition-colors">
                                    <span x-show="!addingNote">Save Note</span>
                                    <span x-show="addingNote">Saving...</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <template x-if="profileContact.notes && profileContact.notes.length === 0">
                                <div class="text-center py-8 text-surface-400 text-xs font-bold">No notes yet.</div>
                            </template>
                            <template x-for="note in profileContact.notes" :key="note.id">
                                <div class="bg-white border border-surface-100 rounded-sm p-3 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[10px] font-bold text-surface-500" x-text="new Date(note.created_at).toLocaleString()"></span>
                                        <span class="text-[10px] font-bold bg-surface-100 px-2 py-0.5 rounded-sm" x-text="note.user ? note.user.name : 'User'"></span>
                                    </div>
                                    <p class="text-xs text-surface-700 whitespace-pre-wrap" x-text="note.content"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- TASKS TAB -->
                    <div x-show="profileActiveTab === 'tasks'" class="space-y-4">
                        <div class="bg-white border border-surface-200 rounded-sm p-4 shadow-sm space-y-3">
                            <input type="text" x-model="newTask.title" placeholder="Task Title" class="w-full text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            <textarea x-model="newTask.description" rows="2" placeholder="Description (optional)" class="w-full text-xs text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                            <input type="date" x-model="newTask.due_date" class="w-full text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            
                            <div class="flex justify-end pt-2">
                                <button @click="addTask()" :disabled="addingTask || !newTask.title.trim()" class="bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-sm disabled:opacity-50 transition-colors">
                                    <span x-show="!addingTask">Add Task</span>
                                    <span x-show="addingTask">Adding...</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <template x-if="profileContact.tasks && profileContact.tasks.length === 0">
                                <div class="text-center py-8 text-surface-400 text-xs font-bold">No tasks found.</div>
                            </template>
                            <template x-for="task in profileContact.tasks" :key="task.id">
                                <div class="bg-white border border-surface-100 rounded-sm p-3 shadow-sm flex items-start gap-3" :class="{'opacity-50': task.is_completed}">
                                    <input type="checkbox" :checked="task.is_completed" class="rounded-sm border-surface-300 text-brand focus:ring-brand mt-1" disabled>
                                    <div>
                                        <p class="text-xs font-bold text-surface-900" :class="{'line-through': task.is_completed}" x-text="task.title"></p>
                                        <template x-if="task.description">
                                            <p class="text-[10px] text-surface-600 mt-1" x-text="task.description"></p>
                                        </template>
                                        <template x-if="task.due_date">
                                            <p class="text-[10px] font-bold text-red-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Due: <span x-text="new Date(task.due_date).toLocaleDateString()"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </div>
</div>
