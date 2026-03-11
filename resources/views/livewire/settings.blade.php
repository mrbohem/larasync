<div class="max-w-5xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Configure your database connections</p>
        </div>
        <div class="flex items-center gap-3">
            @if($settings_source === 'json')
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-medium border border-emerald-200">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                    Saved to file
                </span>
            @elseif($settings_source === 'env')
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-medium border border-amber-200">
                    <div class="w-1.5 h-1.5 bg-amber-500 rounded-full"></div>
                    From .env
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 text-slate-500 rounded-lg text-xs font-medium border border-slate-200">
                    <div class="w-1.5 h-1.5 bg-slate-400 rounded-full"></div>
                    Not configured
                </span>
            @endif
        </div>
    </div>

    {{-- Save Status --}}
    @if($save_status)
        <div class="rounded-xl p-4 flex items-center gap-3 {{ $save_status === 'success' ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            @if($save_status === 'success')
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            @else
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            @endif
            <span class="text-sm font-medium {{ $save_status === 'success' ? 'text-emerald-800' : 'text-red-800' }}">{{ $save_message }}</span>
        </div>
    @endif

    {{-- Database Configuration Cards --}}
    <div class="grid lg:grid-cols-2 gap-6">

        {{-- DB1 Configuration --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-indigo-50 to-indigo-50/30 border-b border-indigo-100/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2-2 2 2 0 00-2 2v5a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Database 1</h3>
                            <p class="text-xs text-slate-500">Primary database connection</p>
                        </div>
                    </div>
                    @if($db1_test_status === 'success')
                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold">
                            <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                            Connected
                        </div>
                    @elseif($db1_test_status === 'error')
                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                            <div class="w-1.5 h-1.5 bg-red-500 rounded-full"></div>
                            Failed
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6 space-y-4">
                {{-- Driver --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Driver</label>
                    <select wire:model.live="db1_driver" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none">
                        <option value="mysql">MySQL</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                    </select>
                </div>

                @if($db1_driver !== 'sqlite')
                    {{-- Host & Port --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Host</label>
                            <input type="text" wire:model="db1_host" placeholder="127.0.0.1" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                            @error('db1_host') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Port</label>
                            <input type="text" wire:model="db1_port" placeholder="{{ $db1_driver === 'pgsql' ? '5432' : '3306' }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                        </div>
                    </div>

                    {{-- Database Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Database Name</label>
                        <input type="text" wire:model="db1_database" placeholder="my_database" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                        @error('db1_database') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Username & Password --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Username</label>
                            <input type="text" wire:model="db1_username" placeholder="root" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                            @error('db1_username') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Password</label>
                            <div x-data="{ show: false }" class="relative">
                                <input :type="show ? 'text' : 'password'" wire:model="db1_password" placeholder="••••••••" class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Schema (PostgreSQL only) --}}
                    @if($db1_driver === 'pgsql')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Schema</label>
                            <input type="text" wire:model="db1_schema" placeholder="public" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                        </div>
                    @endif
                @else
                    {{-- SQLite Database Path --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Database File Path</label>
                        <input type="text" wire:model="db1_database" placeholder="/path/to/database.sqlite" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                        @error('db1_database') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Test Connection Message --}}
                @if($db1_test_message)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium {{ $db1_test_status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                        @if($db1_test_status === 'success')
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        @endif
                        {{ $db1_test_message }}
                    </div>
                @endif

                {{-- Test Button --}}
                <button wire:click="testConnection('db1')" wire:loading.attr="disabled" wire:target="testConnection('db1')"
                    class="w-full px-4 py-2.5 text-sm font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="testConnection('db1')" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Test Connection
                    </span>
                    <span wire:loading wire:target="testConnection('db1')" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Testing...
                    </span>
                </button>
            </div>
        </div>

        {{-- DB2 Configuration --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-50 to-emerald-50/30 border-b border-emerald-100/80">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Database 2</h3>
                            <p class="text-xs text-slate-500">Secondary database connection</p>
                        </div>
                    </div>
                    @if($db2_test_status === 'success')
                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold">
                            <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                            Connected
                        </div>
                    @elseif($db2_test_status === 'error')
                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                            <div class="w-1.5 h-1.5 bg-red-500 rounded-full"></div>
                            Failed
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6 space-y-4">
                {{-- Driver --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Driver</label>
                    <select wire:model.live="db2_driver" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none">
                        <option value="mysql">MySQL</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                    </select>
                </div>

                @if($db2_driver !== 'sqlite')
                    {{-- Host & Port --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Host</label>
                            <input type="text" wire:model="db2_host" placeholder="127.0.0.1" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                            @error('db2_host') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Port</label>
                            <input type="text" wire:model="db2_port" placeholder="{{ $db2_driver === 'pgsql' ? '5432' : '3306' }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                        </div>
                    </div>

                    {{-- Database Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Database Name</label>
                        <input type="text" wire:model="db2_database" placeholder="my_database" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                        @error('db2_database') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Username & Password --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Username</label>
                            <input type="text" wire:model="db2_username" placeholder="root" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                            @error('db2_username') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Password</label>
                            <div x-data="{ show: false }" class="relative">
                                <input :type="show ? 'text' : 'password'" wire:model="db2_password" placeholder="••••••••" class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Schema (PostgreSQL only) --}}
                    @if($db2_driver === 'pgsql')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Schema</label>
                            <input type="text" wire:model="db2_schema" placeholder="public" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                        </div>
                    @endif
                @else
                    {{-- SQLite Database Path --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Database File Path</label>
                        <input type="text" wire:model="db2_database" placeholder="/path/to/database.sqlite" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition-colors outline-none font-mono">
                        @error('db2_database') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Test Connection Message --}}
                @if($db2_test_message)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium {{ $db2_test_status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                        @if($db2_test_status === 'success')
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        @endif
                        {{ $db2_test_message }}
                    </div>
                @endif

                {{-- Test Button --}}
                <button wire:click="testConnection('db2')" wire:loading.attr="disabled" wire:target="testConnection('db2')"
                    class="w-full px-4 py-2.5 text-sm font-medium bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="testConnection('db2')" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Test Connection
                    </span>
                    <span wire:loading wire:target="testConnection('db2')" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Testing...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Ignored Tables Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-amber-50 to-amber-50/30 border-b border-amber-100/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Ignored Tables</h3>
                    <p class="text-xs text-slate-500">Tables excluded from comparison and sync</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-4">
            {{-- Current Ignored Tables --}}
            @if(count($this->ignoredTablesList) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($this->ignoredTablesList as $table)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-sm font-mono text-slate-700 group hover:border-red-300 hover:bg-red-50 transition-colors">
                            {{ $table }}
                            <button wire:click="removeIgnoredTable('{{ $table }}')" class="text-slate-400 hover:text-red-500 transition-colors" title="Remove">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-sm text-slate-400">No tables are being ignored</p>
                </div>
            @endif

            {{-- Add Table Input --}}
            <div class="flex gap-2">
                <input type="text" wire:model="new_ignored_table" wire:keydown.enter="addIgnoredTable"
                    placeholder="Enter table name..."
                    class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 transition-colors outline-none font-mono">
                <button wire:click="addIgnoredTable"
                    class="px-4 py-2.5 text-sm font-medium bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-xl transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add
                </button>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
                These tables will be completely excluded from comparison and synchronization. Common exclusions: <code class="bg-slate-100 px-1 rounded font-mono">sessions</code>, <code class="bg-slate-100 px-1 rounded font-mono">telescope_entries</code>, <code class="bg-slate-100 px-1 rounded font-mono">pulse_*</code>
            </p>
        </div>
    </div>

    {{-- Performance / Chunking --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-slate-50 to-slate-50/30 border-b border-slate-100/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Performance</h3>
                    <p class="text-xs text-slate-500">Adjust chunking and batch sizes for syncing large tables</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Chunk Size</label>
                    <input type="number" min="1" wire:model="performance_chunk_size" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                    @error('performance_chunk_size') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Batch Insert Size</label>
                    <input type="number" min="1" wire:model="performance_batch_insert_size" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                    @error('performance_batch_insert_size') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Progress Chunk Size</label>
                    <input type="number" min="1" wire:model="performance_progress_chunk_size" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors outline-none font-mono">
                    @error('performance_progress_chunk_size') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <p class="text-xs text-slate-500">Defaults: Chunk Size 1000, Batch Insert 500, Progress Chunk 5000. Reduce to lower memory / increase to improve throughput.</p>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Save Configuration</h3>
                    <p class="text-xs text-slate-500">Encrypted and stored in <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs font-mono">storage/app/larasync/</code></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($settings_source === 'json')
                    <button wire:click="resetSettings" wire:loading.attr="disabled"
                        class="px-5 py-2.5 text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors disabled:opacity-60">
                        Reset to Defaults
                    </button>
                @endif
                <button wire:click="saveSettings" wire:loading.attr="disabled" wire:target="saveSettings"
                    class="px-6 py-2.5 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors inline-flex items-center gap-2 shadow-sm shadow-indigo-500/25 disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveSettings" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Save Settings
                    </span>
                    <span wire:loading wire:target="saveSettings" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
