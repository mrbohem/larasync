<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Laravel') }} - Larasync</title>
    
    {{-- Tailwind CSS v4 + Fonts --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- Livewire Styles --}}
    @livewireStyles
    
    {{-- Package Assets (Vite/Tailwind) --}}
    @if(config('larasync.assets_path'))
        <link href="{{ asset(config('larasync.assets_path')) }}" rel="stylesheet">
    @endif
    
    {{-- Custom Enterprise Styles --}}
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0,0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        .font-inter { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .animate-indeterminate-progress {
            animation: indeterminate-progress 2s infinite linear;
        }
        @keyframes indeterminate-progress {
            0% { transform: translateX(-100%) scaleX(0.6); }
            50% { transform: translateX(50%) scaleX(0.8); }
            100% { transform: translateX(300%) scaleX(0.6); }
        }

        /* Sidebar styles */
        .sidebar-nav-item {
            transition: all 0.15s ease;
        }
        .sidebar-nav-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .sidebar-nav-item.active {
            background: rgba(99, 102, 241, 0.15);
            border-right: 3px solid #818cf8;
        }
        .sidebar-nav-item.active .nav-icon {
            color: #a5b4fc;
        }
        .sidebar-nav-item.active .nav-label {
            color: #e0e7ff;
            font-weight: 600;
        }
    </style>
</head>
<body class="font-inter antialiased bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">

        {{-- Sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-slate-900 z-30"
               :class="{ 'lg:w-64': sidebarOpen, 'lg:w-20': !sidebarOpen }">
            
            {{-- Brand Header --}}
            <div class="flex items-center h-16 px-6 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/25">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <div x-show="sidebarOpen" x-transition>
                        <h1 class="text-base font-bold text-white tracking-tight">Larasync</h1>
                        <p class="text-xs text-slate-500 -mt-0.5">Database Sync</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 py-5 px-3 space-y-1 overflow-y-auto">
                <p class="px-3 mb-3 text-xs font-semibold text-slate-500 uppercase tracking-widest" x-show="sidebarOpen">Menu</p>
                
                <a href="{{ route('larasync.dashboard') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg group {{ request()->routeIs('larasync.dashboard') ? 'active' : '' }}">
                    <div class="nav-icon w-9 h-9 rounded-lg flex items-center justify-center {{ request()->routeIs('larasync.dashboard') ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-800 text-slate-400 group-hover:text-slate-300' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <span x-show="sidebarOpen" class="nav-label text-sm {{ request()->routeIs('larasync.dashboard') ? 'text-indigo-100 font-semibold' : 'text-slate-400 group-hover:text-slate-200' }}">
                        Sync Dashboard
                    </span>
                </a>

                <a href="{{ route('larasync.settings') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg group {{ request()->routeIs('larasync.settings') ? 'active' : '' }}">
                    <div class="nav-icon w-9 h-9 rounded-lg flex items-center justify-center {{ request()->routeIs('larasync.settings') ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-800 text-slate-400 group-hover:text-slate-300' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <span x-show="sidebarOpen" class="nav-label text-sm {{ request()->routeIs('larasync.settings') ? 'text-indigo-100 font-semibold' : 'text-slate-400 group-hover:text-slate-200' }}">
                        Settings
                    </span>
                </a>
            </nav>

            {{-- Sidebar Footer --}}
            <div class="border-t border-slate-800/80 p-4">
                <div class="flex items-center gap-3" x-show="sidebarOpen">
                    <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 truncate">v{{ config('larasync.version', '1.0') }}</p>
                    </div>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="w-full mt-3 p-2 rounded-lg hover:bg-slate-800 transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-500 transition-transform" :class="{ 'rotate-180': !sidebarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </button>
            </div>
        </aside>

        {{-- Mobile Sidebar Overlay --}}
        <div x-show="mobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden" @click="mobileSidebarOpen = false">
        </div>

        {{-- Mobile Sidebar --}}
        <aside x-show="mobileSidebarOpen"
               x-transition:enter="transition ease-in-out duration-300 transform"
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in-out duration-300 transform"
               x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 w-64 bg-slate-900 z-50 lg:hidden flex flex-col">
            
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <h1 class="text-base font-bold text-white">Larasync</h1>
                </div>
                <button @click="mobileSidebarOpen = false" class="p-2 rounded-lg hover:bg-slate-800">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <nav class="flex-1 py-5 px-3 space-y-1">
                <p class="px-3 mb-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">Menu</p>
                
                <a href="{{ route('larasync.dashboard') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg group {{ request()->routeIs('larasync.dashboard') ? 'active' : '' }}">
                    <div class="nav-icon w-9 h-9 rounded-lg flex items-center justify-center {{ request()->routeIs('larasync.dashboard') ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-800 text-slate-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <span class="nav-label text-sm {{ request()->routeIs('larasync.dashboard') ? 'text-indigo-100 font-semibold' : 'text-slate-400' }}">Sync Dashboard</span>
                </a>

                <a href="{{ route('larasync.settings') }}" 
                   class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg group {{ request()->routeIs('larasync.settings') ? 'active' : '' }}">
                    <div class="nav-icon w-9 h-9 rounded-lg flex items-center justify-center {{ request()->routeIs('larasync.settings') ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-800 text-slate-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <span class="nav-label text-sm {{ request()->routeIs('larasync.settings') ? 'text-indigo-100 font-semibold' : 'text-slate-400' }}">Settings</span>
                </a>
            </nav>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-h-screen" :class="{ 'lg:pl-64': sidebarOpen, 'lg:pl-20': !sidebarOpen }">
            {{-- Top Bar (Mobile) --}}
            <header class="lg:hidden sticky top-0 z-20 bg-white/80 backdrop-blur-lg border-b border-slate-200/80">
                <div class="flex items-center justify-between h-14 px-4">
                    <button @click="mobileSidebarOpen = true" class="p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Larasync</span>
                    </div>
                    <div class="w-9"></div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="fixed top-6 right-6 z-50 max-w-sm">
                    <div class="bg-emerald-500 text-white px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-sm border border-emerald-400 flex items-center gap-3 animate-in slide-in-from-top-2 fade-in duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="fixed top-6 right-6 z-50 max-w-sm">
                    <div class="bg-red-500 text-white px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-sm border border-red-400 flex items-center gap-3 animate-in slide-in-from-top-2 fade-in duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            {{-- Page Content --}}
            <main class="p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Livewire Scripts --}}
    @livewireScripts
    <!-- <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> -->
    
    {{-- Debug Mode --}}
    @if(config('app.debug'))
        <script>
            window.addEventListener('livewire:init', () => {
                Livewire.on('notify', (message) => {
                    // Auto-notifications from package
                    console.log('🔔 Larasync:', message);
                });
            });
        </script>
    @endif
</body>
</html>
