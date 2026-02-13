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
    </style>
</head>
<body class="font-inter antialiased bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen">
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
        <main>
            {{ $slot }}
        </main>
    </div>

    {{-- Livewire Scripts --}}
    @livewireScripts
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
