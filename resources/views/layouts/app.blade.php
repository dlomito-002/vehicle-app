<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestión de Flota')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Public Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        brand: {
                            magenta: '#BF1F94',
                            cyan: '#0DB3D9',
                            olive: '#ACBF17',
                            amber: '#F2B705',
                            orange: '#F28705',
                        },
                    },
                },
            },
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Public Sans', system-ui, sans-serif; }
        .font-data { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
    </style>
</head>
<body class="h-full overflow-x-hidden bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-full flex flex-col">
        @auth
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="flex min-h-16 flex-wrap items-center justify-between gap-3 py-3 sm:h-16 sm:flex-nowrap sm:gap-8 sm:py-0">
                    <div class="flex min-w-0 items-center gap-4 sm:gap-8">
                        <a href="{{ route('dashboard') }}" class="font-semibold text-lg tracking-tight text-slate-900">
                            Fleet <span class="text-brand-magenta">Desk</span>
                        </a>
                        <nav class="hidden sm:flex items-center gap-1">
                            @php
                                $navLinks = [
                                    ['route' => 'dashboard', 'label' => 'Panel'],
                                    ['route' => 'calendar.index', 'label' => 'Calendario'],
                                    ['route' => 'receptions.index', 'label' => 'Recepciones'],
                                    ['route' => 'deliveries.index', 'label' => 'Devoluciones'],
                                    ['route' => 'services.index', 'label' => 'Servicios'],
                                    ['route' => 'maintenance-schedules.index', 'label' => 'Mantenimiento'],
                                ];
                                if (auth()->user()->isAdmin()) {
                                    $navLinks[] = ['route' => 'vehicles.index', 'label' => 'Vehículos'];
                                    $navLinks[] = ['route' => 'users.index', 'label' => 'Usuarios'];
                                }
                                $navLinks[] = ['route' => 'help.create', 'label' => 'Ayuda'];
                            @endphp
                            @foreach ($navLinks as $link)
                                <a href="{{ route($link['route']) }}"
                                   class="px-3 py-2 rounded-md text-sm font-medium transition-colors
                                          {{ request()->routeIs($link['route']) || request()->routeIs(str($link['route'])->before('.').'.*')
                                                ? 'bg-brand-cyan/10 text-brand-cyan'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                        <a href="{{ route('receptions.create') }}"
                           class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90 transition-colors">
                            Nueva recepción
                        </a>
                        <span class="text-sm text-slate-500 hidden md:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-900">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
                <nav class="sm:hidden flex items-center gap-1 pb-3 -mt-1 overflow-x-auto">
                    @foreach ($navLinks as $link)
                        <a href="{{ route($link['route']) }}"
                           class="px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap
                                  {{ request()->routeIs($link['route']) ? 'bg-brand-cyan/10 text-brand-cyan' : 'text-slate-600' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>
        @endauth

        <main class="flex-1">
            <div class="mx-auto max-w-6xl px-3 py-5 sm:px-6 sm:py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border-l-4 border-brand-olive bg-brand-olive/10 px-4 py-3 text-sm text-slate-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3 text-sm text-slate-800">
                        <p class="font-medium mb-1">Corrige lo siguiente:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
