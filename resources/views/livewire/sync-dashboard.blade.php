<div class="max-w-7xl mx-auto space-y-5 p-4">

    {{-- DB Status Cards --}}
    <div class="grid lg:grid-cols-2 gap-4">
        {{-- DB1 Status Card --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2-2 2 2 0 00-2 2v5a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-800">{{ $db1_label }} Database (DB1){{ $labels_match && $db1_host ? " · {$db1_host}" : '' }}</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="testDb('db1')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $db1_connected ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                        {{ $db1_connected ? '✓ Connected' : 'Test' }}
                    </button>
                </div>
            </div>

            @if($db1_configured)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                            <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Driver</span>
                            <span class="font-mono font-medium text-slate-900 text-sm">{{ $db1_driver }}</span>
                        </div>
                        <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                            <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Database</span>
                            <span class="font-mono font-medium text-slate-900 truncate block text-sm">{{ $db1_database }}</span>
                        </div>
                        @if($db1_driver !== 'sqlite')
                            <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                                <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Host</span>
                                <span class="font-mono font-medium text-slate-900 text-sm">{{ $db1_host }}</span>
                            </div>
                            <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                                <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">User</span>
                                <span class="font-mono font-medium text-slate-900 text-sm">{{ $db1_username }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-2 {{ $db1_connected ? 'bg-emerald-500' : 'bg-red-500' }} rounded-full transition-all duration-300" 
                                style="width: {{ $db1_connected ? '100' : '0' }}%"></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $db1_connected ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                            <span class="text-xs font-medium {{ $db1_connected ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $db1_connected ? 'Connected' : 'Disconnected' }}
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-14 h-14 bg-slate-50 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2-2 2 2 0 00-2 2v5a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-900 mb-1">Configure DB1 Database</h4>
                    <p class="text-xs text-slate-600 mb-4">Go to <a href="{{ route('larasync.settings') }}" class="text-indigo-600 font-medium hover:underline">Settings</a> to configure</p>
                    <a href="{{ route('larasync.settings') }}"
                        class="px-5 py-2.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors inline-block">
                        Setup DB1
                    </a>
                </div>
            @endif
        </div>

        {{-- DB2 Status Card --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-800">{{ $db2_label }} Database (DB2){{ $labels_match && $db2_host ? " · {$db2_host}" : '' }}</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="testDb('db2')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $db2_connected ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                        {{ $db2_connected ? '✓ Connected' : 'Test' }}
                    </button>
                </div>
            </div>

            @if($db2_configured)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                            <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Driver</span>
                            <span class="font-mono font-medium text-slate-900 text-sm">{{ $db2_driver }}</span>
                        </div>
                        <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                            <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Database</span>
                            <span class="font-mono font-medium text-slate-900 truncate block text-sm">{{ $db2_database }}</span>
                        </div>
                        @if($db2_driver !== 'sqlite')
                            <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                                <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">Host</span>
                                <span class="font-mono font-medium text-slate-900 text-sm">{{ $db2_host }}</span>
                            </div>
                            <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-100">
                                <span class="text-xs text-slate-500 uppercase tracking-wide block mb-1">User</span>
                                <span class="font-mono font-medium text-slate-900 text-sm">{{ $db2_username }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-2 {{ $db2_connected ? 'bg-emerald-500' : 'bg-red-500' }} rounded-full transition-all duration-300" 
                                style="width: {{ $db2_connected ? '100' : '0' }}%"></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $db2_connected ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                            <span class="text-xs font-medium {{ $db2_connected ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $db2_connected ? 'Connected' : 'Disconnected' }}
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-14 h-14 bg-slate-50 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                            </path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-900 mb-1">Configure DB2 Database</h4>
                    <p class="text-xs text-slate-600 mb-4">Go to <a href="{{ route('larasync.settings') }}" class="text-emerald-600 font-medium hover:underline">Settings</a> to configure</p>
                    <a href="{{ route('larasync.settings') }}"
                        class="px-5 py-2.5 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors inline-block">
                        Setup DB2
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Sync Button (Only if both connected) --}}
    @if($db1_connected && $db2_connected)
        @if(!$show_direction_selector && $db1_connected && $db2_connected && !$syncing)
            <div class="text-center py-8">
                <button wire:click="compare"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-semibold rounded-xl shadow-sm transition-all inline-flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    Start Synchronization
                </button>
                <p class="text-sm text-slate-500 mt-3">Detect out-of-date tables and choose sync direction</p>
            </div>
        @endif

        {{-- Direction Selector Modal --}}
        @if($show_direction_selector)
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl p-8 max-w-2xl w-full shadow-xl">
                    <div class="text-center mb-8">
                        <div class="w-14 h-14 bg-indigo-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-slate-900 mb-2">Choose Sync Direction</h2>
                        <p class="text-sm text-slate-600">
                            Select <span class="font-semibold text-emerald-600">SOURCE</span> and <span class="font-semibold text-orange-600">TARGET</span> database
                        </p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <button wire:click="startSync('db1_to_db2')" wire:loading.attr="disabled"
                            class="relative p-6 bg-indigo-50 hover:bg-indigo-100 border-2 border-indigo-200 hover:border-indigo-300 rounded-xl text-left transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2-2 2 2 0 00-2 2v5a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-slate-900 mb-1">{{ $db1_display }} → {{ $db2_display }}</div>
                                    <div class="text-xs text-slate-600 leading-relaxed">
                                        {{ $db1_driver }} / {{ $db1_database }}
                                        <span class="mx-1">→</span>
                                        {{ $db2_driver }} / {{ $db2_database }}
                                    </div>
                                </div>
                            </div>
                            <div class="absolute top-3 right-3 bg-emerald-500 text-white px-2 py-1 rounded-md text-xs font-semibold">
                                Recommended
                            </div>
                            <div wire:loading.flex class="hidden absolute inset-0 bg-indigo-50/80 rounded-xl items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="text-sm font-medium text-indigo-700">Processing...</span>
                            </div>
                        </button>

                        <button wire:click="startSync('db2_to_db1')" wire:loading.attr="disabled"
                            class="relative p-6 bg-orange-50 hover:bg-orange-100 border-2 border-orange-200 hover:border-orange-300 rounded-xl text-left transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 002 2v4a2 2 0 004 2h8a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                                        </path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-slate-900 mb-1">{{ $db2_display }} → {{ $db1_display }}</div>
                                    <div class="text-xs text-slate-600 leading-relaxed">
                                        {{ $db2_driver }} / {{ $db2_database }}
                                        <span class="mx-1">→</span>
                                        {{ $db1_driver }} / {{ $db1_database }}
                                    </div>
                                </div>
                            </div>
                            <div wire:loading.flex class="hidden absolute inset-0 bg-orange-50/80 rounded-xl items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="text-sm font-medium text-orange-700">Processing...</span>
                            </div>
                        </button>
                    </div>

                    <button wire:click="$set('show_direction_selector', false)" wire:loading.attr="disabled"
                        class="w-full px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                </div>
            </div>
        @endif

        {{-- Comparison Results --}}
        @if(!empty($comparison) && !$syncing)
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200/80">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Table Comparison Results</h2>
                            <p class="text-sm text-slate-600">
                                <span class="font-medium">{{ $sync_direction === 'db1_to_db2' ? "$db1_display → $db2_display" : "$db2_display → $db1_display" }}</span>
                                · {{ count($comparison) }} tables
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2.5">
                        <button wire:click="syncAllTables" @if($sync_in_progress || $single_sync_table) disabled @endif
                            class="px-4 py-2.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors inline-flex items-center gap-2 {{ ($sync_in_progress || $single_sync_table) ? 'opacity-50 cursor-not-allowed' : '' }}">
                            @if($sync_in_progress)
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Syncing...
                            @else
                                Sync All
                            @endif
                        </button>
                        <button wire:click="clear" class="px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                            Re-compare
                        </button>
                        @if($logs)
                            <button onclick="toggleLogs()" class="px-4 py-2.5 text-sm font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg transition-colors inline-flex items-center gap-2 border border-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                View Logs ({{ count($logs) }})
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="grid lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-emerald-50/50 border border-emerald-200/50 rounded-xl p-4">
                        <div class="text-3xl font-bold text-emerald-700">{{ count(array_filter($comparison, fn($t) => $t['diff'] > 0)) }}</div>
                        <div class="text-xs font-medium text-emerald-600 uppercase tracking-wide mt-1">Needs Sync</div>
                    </div>
                    <div class="bg-orange-50/50 border border-orange-200/50 rounded-xl p-4">
                        <div class="text-3xl font-bold text-orange-700">{{ count(array_filter($comparison, fn($t) => $t['diff'] < 0)) }}</div>
                        <div class="text-xs font-medium text-orange-600 uppercase tracking-wide mt-1">Needs Update</div>
                    </div>
                    <div class="bg-slate-50/50 border border-slate-200/50 rounded-xl p-4">
                        <div class="text-3xl font-bold text-slate-700">{{ count(array_filter($comparison, fn($t) => $t['diff'] === 0 && empty($t['missing_in_target']))) }}</div>
                        <div class="text-xs font-medium text-slate-600 uppercase tracking-wide mt-1">Match</div>
                    </div>
                    @php $missingCount = count(array_filter($comparison, fn($t) => !empty($t['missing_in_target']))); @endphp
                    <div class="bg-{{ $missingCount > 0 ? 'amber' : 'blue' }}-50/50 border border-{{ $missingCount > 0 ? 'amber' : 'blue' }}-200/50 rounded-xl p-4">
                        @if($missingCount > 0)
                            <div class="text-3xl font-bold text-amber-700">{{ $missingCount }}</div>
                            <div class="text-xs font-medium text-amber-600 uppercase tracking-wide mt-1">Missing</div>
                        @else
                            <div class="text-3xl font-bold text-blue-700">{{ array_sum(array_map(fn($t) => abs($t['diff']), $comparison)) }}</div>
                            <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mt-1">Total Diff</div>
                        @endif
                    </div>
                </div>

                {{-- Sync Progress --}}
                @if($sync_in_progress)
                    <div class="mb-5 bg-indigo-50/50 border border-indigo-200/50 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">Syncing Tables</h3>
                                    <p class="text-xs text-slate-600">{{ $sync_completed_count }} / {{ $sync_total_count }} completed</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-xl font-bold text-indigo-600">
                                    {{ $sync_total_count > 0 ? round(($sync_completed_count / $sync_total_count) * 100) : 0 }}%
                                </div>
                                <button wire:click="stopSync"
                                    x-data="{ stopping: false }"
                                    x-on:click="stopping = true"
                                    x-bind:disabled="stopping"
                                    class="px-3.5 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors inline-flex items-center gap-1.5 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <template x-if="!stopping">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                            </svg>
                                            Stop
                                        </span>
                                    </template>
                                    <template x-if="stopping">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Stopping...
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>

                        <div class="w-full h-2 bg-white rounded-full overflow-hidden mb-4">
                            <div class="h-2 bg-indigo-600 rounded-full transition-all duration-500"
                                 style="width: {{ $sync_total_count > 0 ? round(($sync_completed_count / $sync_total_count) * 100) : 0 }}%"></div>
                        </div>

                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                            @foreach($tables_to_sync as $tbl)
                                <div class="flex items-center gap-2 px-3 py-2 rounded-lg border text-xs
                                    {{ in_array($tbl, $synced_tables) ? 'bg-emerald-50 border-emerald-200' : 
                                       ($current_syncing_table === $tbl ? 'bg-amber-50 border-amber-300' : 'bg-white border-slate-200') }}">
                                    @if(in_array($tbl, $synced_tables))
                                        <div class="w-5 h-5 bg-emerald-500 rounded flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    @elseif($current_syncing_table === $tbl)
                                        <div class="w-5 h-5 bg-amber-500 rounded flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-5 h-5 bg-slate-300 rounded flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($current_syncing_table === $tbl && $single_sync_table === $tbl && $single_sync_total > 0)
                                        <div class="flex-1 min-w-0">
                                            <span class="font-mono font-medium text-slate-800 truncate block">{{ $tbl }}</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div class="flex-1 h-1 bg-amber-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-amber-500 rounded-full transition-all duration-300" 
                                                         style="width: {{ $single_sync_total > 0 ? round(($single_sync_offset / $single_sync_total) * 100) : 0 }}%"></div>
                                                </div>
                                                <span class="text-xs text-amber-600 font-mono whitespace-nowrap">
                                                    {{ number_format($single_sync_offset) }}/{{ number_format($single_sync_total) }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="font-mono font-medium text-slate-800 truncate">{{ $tbl }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Table</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    {{ $sync_direction === 'db1_to_db2' ? $db1_display : $db2_display }}
                                </th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    {{ $sync_direction === 'db1_to_db2' ? $db2_display : $db1_display }}
                                </th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Diff</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 bg-white">
                            @foreach($comparison as $table => $data)
                                <tr class="hover:bg-slate-50/50 transition-colors {{ $data['diff'] !== 0 ? 'border-l-2 border-indigo-500' : '' }} {{ ($current_syncing_table === $table || $single_sync_table === $table) ? 'bg-amber-50/30' : '' }}">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-medium text-sm text-slate-900 max-w-xs truncate">{{ $table }}</span>
                                            @if(!empty($data['missing_columns']) || !empty($data['type_mismatches']))
                                                <button wire:click="showSchemaWarnings('{{ $table }}')" class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-xs font-semibold hover:bg-amber-200 transition-colors" title="Schema Differences Detected">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    Schema Diff
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <div class="font-mono font-semibold text-sm text-slate-900">{{ number_format($data['rows1']) }}</div>
                                        <div class="text-xs text-slate-500">rows</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if(!empty($data['missing_in_target']))
                                            <span class="inline-flex items-center gap-1.5 bg-amber-100/80 text-amber-700 px-3 py-1.5 rounded-lg font-medium text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                Missing
                                            </span>
                                        @else
                                            <div class="font-mono font-semibold text-sm text-slate-900">{{ number_format($data['rows2']) }}</div>
                                            <div class="text-xs text-slate-500">rows</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($data['diff'] > 0)
                                            <span class="inline-flex items-center gap-1.5 bg-emerald-100/80 text-emerald-700 px-3 py-1.5 rounded-lg font-medium text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                                +{{ $data['diff'] }}
                                            </span>
                                        @elseif($data['diff'] < 0)
                                            <span class="inline-flex items-center gap-1.5 bg-orange-100/80 text-orange-700 px-3 py-1.5 rounded-lg font-medium text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                                </svg>
                                                {{ $data['diff'] }}
                                            </span>
                                        @else
                                            @if(in_array($table, $synced_tables))
                                                <span class="inline-flex px-3 py-1.5 bg-emerald-100/80 text-emerald-700 rounded-lg font-medium text-sm">✓ Synced</span>
                                            @else
                                                <span class="inline-flex px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg font-medium text-sm">Match</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($single_sync_table === $table)
                                            {{-- Chunked sync progress --}}
                                            <div class="flex flex-col items-center gap-1.5">
                                                <div class="flex items-center gap-2">
                                                    <svg class="animate-spin w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    <span class="text-xs font-semibold text-indigo-700">
                                                        {{ $single_sync_total > 0 ? round(($single_sync_offset / $single_sync_total) * 100) : 0 }}%
                                                    </span>
                                                </div>
                                                <div class="w-28 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-600 rounded-full transition-all duration-300" 
                                                         style="width: {{ $single_sync_total > 0 ? round(($single_sync_offset / $single_sync_total) * 100) : 0 }}%"></div>
                                                </div>
                                                <span class="text-xs text-slate-500 font-mono">
                                                    {{ number_format($single_sync_offset) }} / {{ number_format($single_sync_total) }}
                                                </span>
                                                @if(!$sync_in_progress)
                                                    <button wire:click="stopSync"
                                                        x-data="{ stopping: false }"
                                                        x-on:click="stopping = true"
                                                        x-bind:disabled="stopping"
                                                        class="mt-1 px-3 py-1.5 text-xs font-medium bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors inline-flex items-center gap-1 disabled:opacity-70 disabled:cursor-not-allowed">
                                                        <template x-if="!stopping">
                                                            <span class="inline-flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                                                </svg>
                                                                Stop
                                                            </span>
                                                        </template>
                                                        <template x-if="stopping">
                                                            <span class="inline-flex items-center gap-1">
                                                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                </svg>
                                                                Stopping...
                                                            </span>
                                                        </template>
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif(!empty($data['missing_in_target']))
                                            <button wire:click="syncSingleTable('{{ $table }}')" @if($single_sync_table) disabled @endif class="relative px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Create & Sync
                                            </button>
                                        @elseif($data['diff'] > 0)
                                            <button wire:click="syncSingleTable('{{ $table }}')" wire:target="syncSingleTable('{{ $table }}')" wire:loading.attr="disabled" @if($single_sync_table) disabled @endif class="relative px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-70 disabled:cursor-not-allowed">
                                                <span wire:target="syncSingleTable('{{ $table }}')" wire:loading.remove>Sync</span>
                                                <span wire:target="syncSingleTable('{{ $table }}')" wire:loading class="inline-flex items-center gap-1.5">
                                                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Syncing...
                                                </span>
                                            </button>
                                        @elseif($data['diff'] < 0)
                                            <button wire:click="syncSingleTable('{{ $table }}')" wire:target="syncSingleTable('{{ $table }}')" wire:loading.attr="disabled" @if($single_sync_table) disabled @endif class="relative px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-70 disabled:cursor-not-allowed">
                                                <span wire:target="syncSingleTable('{{ $table }}')" wire:loading.remove>Update</span>
                                                <span wire:target="syncSingleTable('{{ $table }}')" wire:loading class="inline-flex items-center gap-1.5">
                                                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    Updating...
                                                </span>
                                            </button>
                                        @else
                                            @if(in_array($table, $synced_tables))
                                                <span class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium inline-block">✓ Done</span>
                                            @else
                                                <button wire:click="syncSingleTable('{{ $table }}')" wire:target="syncSingleTable('{{ $table }}')" wire:loading.attr="disabled" @if($single_sync_table) disabled @endif class="relative px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-70 disabled:cursor-not-allowed">
                                                    <span wire:target="syncSingleTable('{{ $table }}')" wire:loading.remove>Force</span>
                                                    <span wire:target="syncSingleTable('{{ $table }}')" wire:loading class="inline-flex items-center gap-1.5">
                                                        <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        Forcing...
                                                    </span>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Logs Panel Modal --}}
                <div id="logs-panel" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
                    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[85vh] flex flex-col">
                        <div class="flex items-center justify-between p-5 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Sync Logs</h4>
                                    <p class="text-xs text-slate-600">{{ count($logs) }} log entries</p>
                                </div>
                            </div>
                            <button onclick="toggleLogs()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-5">
                            <div class="space-y-2.5">
                                @foreach($logs as $log)
                                    <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border-l-3 text-sm
                                            {{ str_contains($log, '✅') ? 'border-emerald-500 bg-emerald-50/50' : (str_contains($log, '❌') ? 'border-red-500 bg-red-50/50' : 'border-blue-500 bg-blue-50/50') }}">
                                        <span class="font-mono text-xs text-slate-500 flex-shrink-0 bg-white px-2.5 py-1 rounded">{{ now()->format('H:i:s') }}</span>
                                        <span class="font-mono text-slate-800 flex-1">{{ $log }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="p-5 border-t border-slate-200">
                            <button onclick="toggleLogs()" class="w-full px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Missing Tables / Schema Mismatch Modal --}}
            @if($show_missing_tables_modal)
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-xl p-8 max-w-2xl w-full shadow-xl max-h-[85vh] overflow-y-auto">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-slate-900 mb-2">Table Issues Detected</h2>
                            <p class="text-sm text-slate-600">
                                @if(!empty($missing_tables) && !empty($schema_mismatch_tables))
                                    {{ count($missing_tables) }} missing table(s) and {{ count($schema_mismatch_tables) }} table(s) with schema differences
                                @elseif(!empty($missing_tables))
                                    {{ count($missing_tables) }} table(s) exist in the source but not in the target database
                                @else
                                    {{ count($schema_mismatch_tables) }} table(s) have column differences between source and target
                                @endif
                            </p>
                        </div>

                        {{-- Missing Tables Section --}}
                        @if(!empty($missing_tables))
                            <div class="mb-4">
                                <h3 class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    Missing Tables ({{ count($missing_tables) }})
                                </h3>
                                <div class="bg-red-50/50 border border-red-200/50 rounded-xl p-4 max-h-36 overflow-y-auto">
                                    <div class="grid sm:grid-cols-2 gap-2">
                                        @foreach($missing_tables as $mt)
                                            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-red-200 text-sm">
                                                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                <span class="font-mono font-medium text-slate-800 truncate">{{ $mt }}</span>
                                                <span class="text-xs text-red-600 font-medium ml-auto">Missing</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Schema Mismatch Tables Section --}}
                        @if(!empty($schema_mismatch_tables))
                            <div class="mb-6">
                                <h3 class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    Column Mismatch ({{ count($schema_mismatch_tables) }})
                                </h3>
                                <div class="bg-amber-50/50 border border-amber-200/50 rounded-xl p-4 max-h-48 overflow-y-auto">
                                    <div class="grid sm:grid-cols-1 gap-2">
                                        @foreach($schema_mismatch_tables as $smt)
                                            @php
                                                $smtData = $comparison[$smt] ?? [];
                                                $smtMissingCols = $smtData['missing_columns'] ?? [];
                                                $smtTypeMismatches = $smtData['type_mismatches'] ?? [];
                                            @endphp
                                            <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-amber-200 text-sm">
                                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                <span class="font-mono font-medium text-slate-800 truncate">{{ $smt }}</span>
                                                <div class="flex items-center gap-1.5 ml-auto flex-shrink-0">
                                                    @if(!empty($smtMissingCols))
                                                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-1.5 py-0.5 rounded text-xs font-medium">
                                                            {{ count($smtMissingCols) }} missing col{{ count($smtMissingCols) > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                    @if(!empty($smtTypeMismatches))
                                                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-xs font-medium">
                                                            {{ count($smtTypeMismatches) }} type diff{{ count($smtTypeMismatches) > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-2.5">
                            <button wire:click="handleMissingTablesChoice('create')" wire:loading.attr="disabled"
                                class="w-full px-4 py-3 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                @if(!empty($missing_tables) && !empty($schema_mismatch_tables))
                                    Create Missing Tables, Fix Schema & Sync
                                @elseif(!empty($missing_tables))
                                    Create All Missing Tables & Sync
                                @else
                                    Fix All Schema Mismatches & Sync
                                @endif
                            </button>
                            <button wire:click="handleMissingTablesChoice('skip')" wire:loading.attr="disabled"
                                class="w-full px-4 py-2.5 text-sm font-medium bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                Skip These Tables & Sync Remaining Only
                            </button>
                            <button wire:click="handleMissingTablesChoice('cancel')" wire:loading.attr="disabled"
                                class="w-full px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Single Table Create Confirmation Modal --}}
            @if($pending_create_table)
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-xl p-8 max-w-2xl w-full shadow-xl">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-slate-900 mb-2">Create Table: {{ $pending_create_table }}</h2>
                            <p class="text-sm text-slate-600">
                                This table doesn't exist in the target database. Review the structure below and confirm creation.
                            </p>
                        </div>

                        @if(!empty($pending_create_preview))
                            <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden mb-6">
                                <div class="px-4 py-3 bg-slate-100 border-b border-slate-200">
                                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Table Structure</h4>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200">
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Column</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Type</th>
                                                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-600">Nullable</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($pending_create_preview as $col)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-4 py-2 font-mono text-xs text-slate-900">{{ $col['name'] ?? 'unknown' }}</td>
                                                    <td class="px-4 py-2 font-mono text-xs text-indigo-600">{{ $col['type_name'] ?? $col['type'] ?? 'unknown' }}</td>
                                                    <td class="px-4 py-2 text-center">
                                                        @if(!empty($col['nullable']))
                                                            <span class="text-xs text-slate-400">yes</span>
                                                        @else
                                                            <span class="text-xs text-slate-700 font-medium">no</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-3">
                            <button wire:click="cancelCreateTable" wire:loading.attr="disabled"
                                class="px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                            <button wire:click="confirmCreateAndSync('{{ $pending_create_table }}')" wire:loading.attr="disabled"
                                class="px-4 py-2.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create & Sync
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Schema Warnings Modal --}}
            @if($show_schema_modal && $schema_warnings_table)
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-xl p-8 max-w-2xl w-full shadow-xl">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-slate-900 mb-2">Schema Differences: {{ $schema_warnings_table }}</h2>
                            <p class="text-sm text-slate-600">
                                Larasync detected differences between the source and target schemas for this table.
                            </p>
                        </div>

                        @php
                            $tableData = $comparison[$schema_warnings_table] ?? null;
                            $missingCols = $tableData['missing_columns'] ?? [];
                            $typeMismatches = $tableData['type_mismatches'] ?? [];
                        @endphp

                        @if(!empty($missingCols))
                            <div class="bg-red-50/50 border border-red-200/50 rounded-xl overflow-hidden mb-6">
                                <div class="px-4 py-3 bg-red-100 border-b border-red-200 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <h4 class="text-xs font-semibold text-red-800 uppercase tracking-wide">Missing Columns ({{ count($missingCols) }})</h4>
                                </div>
                                <div class="px-4 py-3">
                                    <p class="text-xs text-red-700 mb-2">These columns exist in the source but are missing from the target table. Syncing may fail if data is inserted into them.</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($missingCols as $col)
                                            <span class="inline-flex bg-white border border-red-200 text-red-700 px-2 py-1 rounded text-xs font-mono font-medium">{{ $col }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($typeMismatches))
                            <div class="bg-amber-50/50 border border-amber-200/50 rounded-xl overflow-hidden mb-6">
                                <div class="px-4 py-3 bg-amber-100 border-b border-amber-200 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <h4 class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Type Mismatches ({{ count($typeMismatches) }})</h4>
                                </div>
                                <div class="max-h-48 overflow-y-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-amber-200 bg-amber-50/50 text-left text-xs font-semibold text-amber-800">
                                                <th class="px-4 py-2">Column</th>
                                                <th class="px-4 py-2">Source Type</th>
                                                <th class="px-4 py-2">Target Type</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-amber-100">
                                            @foreach($typeMismatches as $col => $types)
                                                <tr class="hover:bg-amber-50">
                                                    <td class="px-4 py-2 font-mono text-xs text-slate-900 font-medium">{{ $col }}</td>
                                                    <td class="px-4 py-2 font-mono text-xs text-indigo-600">{{ $types['source'] }}</td>
                                                    <td class="px-4 py-2 font-mono text-xs text-red-600">{{ $types['target'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-4 py-2 bg-amber-50/50 border-t border-amber-200 text-xs text-amber-700">
                                    Tip: Data type mismatches, such as <code class="bg-amber-100 px-1 rounded">tinyint</code> vs <code class="bg-amber-100 px-1 rounded">integer</code>, may lead to data loss or unwanted casting during sync.
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center mt-6 pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-500 max-w-sm">
                                Matching the schema will <strong class="text-red-600">drop</strong> the target table and recreate it, then automatically sync the data.
                            </p>
                            <div class="flex gap-3">
                                <button wire:click="closeSchemaWarnings" class="px-4 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">
                                    Close
                                </button>
                                <button wire:click="matchTargetSchema('{{ $schema_warnings_table }}')" wire:loading.attr="disabled" class="px-4 py-2.5 text-sm font-medium bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors inline-flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Match Target Schema
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

    @elseif($db1_configured || $db2_configured)
        <div class="text-center py-10 bg-white rounded-xl border border-slate-200/80 shadow-sm">
            <div class="w-14 h-14 bg-amber-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                    </path>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-900 mb-2">Almost Ready</h3>
            <p class="text-sm text-slate-600">
                {{ $db1_connected ? '' : 'Test DB1 ' }}{{ $db1_connected && $db2_connected ? '' : 'and ' }}{{ $db2_connected ? '' : 'DB2' }}
                connection{{ !$db1_connected && !$db2_connected ? 's' : '' }} first
            </p>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-xl border border-slate-200/80 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900 mb-3">Welcome to Larasync</h2>
            <p class="text-sm text-slate-600 mb-8 max-w-xl mx-auto">
                Configure your databases to start synchronizing data across environments
            </p>
            <div class="grid lg:grid-cols-2 gap-4 max-w-2xl mx-auto">
                <a href="{{ route('larasync.settings') }}"
                    class="p-6 bg-indigo-50 hover:bg-indigo-100 border-2 border-indigo-200 hover:border-indigo-300 rounded-xl transition-all block text-center">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h10c2.21 0 4-1.79 4-4V7"></path>
                        </svg>
                    </div>
                    <div class="text-base font-semibold text-slate-900">Setup DB1</div>
                </a>
                <a href="{{ route('larasync.settings') }}"
                    class="p-6 bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 hover:border-emerald-300 rounded-xl transition-all block text-center">
                    <div class="w-12 h-12 bg-emerald-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01">
                            </path>
                        </svg>
                    </div>
                    <div class="text-base font-semibold text-slate-900">Setup DB2</div>
                </a>
            </div>
        </div>
    @endif

    @if($show_db1_form)
        {{-- DB1 Form Here --}}
    @endif

    <script>
        function toggleLogs() {
            document.getElementById('logs-panel').classList.toggle('hidden');
        }

        document.addEventListener('livewire:init', function () {
            var syncQueue = [];
            var syncIdx = 0;
            var cancelled = false;

            function getComponent() {
                var el = document.querySelector('[wire\\:id]');
                return el ? Livewire.find(el.getAttribute('wire:id')) : null;
            }

            function runNext() {
                if (cancelled) return;
                if (syncIdx < syncQueue.length) {
                    var table = syncQueue[syncIdx];
                    syncIdx++;
                    var comp = getComponent();
                    if (comp) comp.syncNextTable(table);
                }
            }

            Livewire.on('start-sequential-sync', function (event) {
                cancelled = false;
                syncQueue = (event.tables) || (event[0] && event[0].tables) || [];
                syncIdx = 0;
                runNext();
            });

            Livewire.on('table-sync-complete', function () {
                if (!cancelled) runNext();
            });

            Livewire.on('all-tables-synced', function () {
                syncQueue = [];
                syncIdx = 0;
                cancelled = false;
            });

            Livewire.on('sync-stopped', function () {
                cancelled = true;
                syncQueue = [];
                syncIdx = 0;
            });

            // Single-table chunked sync
            Livewire.on('start-single-table-sync', function (event) {
                cancelled = false;
                var tableName = (event.tableName) || (event[0] && event[0].tableName) || '';
                var comp = getComponent();
                if (comp) comp.syncTableChunk(tableName);
            });

            Livewire.on('continue-single-table-sync', function (event) {
                if (cancelled) return;
                var tableName = (event.tableName) || (event[0] && event[0].tableName) || '';
                var comp = getComponent();
                if (comp) comp.syncTableChunk(tableName);
            });
        });
    </script>
</div>