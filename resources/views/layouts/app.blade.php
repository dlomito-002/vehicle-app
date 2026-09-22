<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#173d75">
    <title>@yield('title', 'Gestión de Flota')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        try {
            const stored = localStorage.getItem('carrousel-theme') || 'system';
            const resolved = stored === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : stored;
            document.documentElement.dataset.theme = resolved;
        } catch (e) {}
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
</head>
<body class="h-full overflow-x-hidden font-sans antialiased" style="color:var(--ink)">
<div class="brand-strip"></div>

@auth
    @php
        $navSections = [
            [
                'label' => 'Operación',
                'links' => [
                    ['route' => 'dashboard', 'label' => 'Panel', 'icon' => '⌂'],
                    ['route' => 'calendar.index', 'label' => 'Calendario', 'icon' => '▦'],
                    ['route' => 'receptions.index', 'label' => 'Recepciones', 'icon' => '↧'],
                    ['route' => 'deliveries.index', 'label' => 'Devoluciones', 'icon' => '↥'],
                    ['route' => 'maintenance-schedules.index', 'label' => 'Mantenimiento', 'icon' => '✚'],
                ],
            ],
        ];

        if (auth()->user()->isAdmin()) {
            $navSections[] = [
                'label' => 'Administración',
                'links' => [
                    ['route' => 'vehicles.index', 'label' => 'Vehículos', 'icon' => '🚗'],
                    ['route' => 'users.index', 'label' => 'Usuarios', 'icon' => '☺'],
                ],
            ];
        }

        $navSections[] = [
            'label' => null,
            'links' => [
                ['route' => 'help.create', 'label' => 'Ayuda', 'icon' => '?'],
            ],
        ];
    @endphp

    <div class="app-shell" x-data="{ open: false }">
        <div class="sidebar-backdrop" x-show="open" x-cloak @click="open = false"
             style="display:none;position:fixed;inset:4px 0 0;background:rgba(15,23,42,.42);z-index:1300"></div>

        <aside class="sidebar" :class="{ 'is-open': open }">
            <div class="sidebar-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel" style="width:40px;height:40px;object-fit:contain;background:#fff;border-radius:8px;padding:3px;">
                <div>
                    <strong>Control de Vehículos</strong>
                    <span style="display:block">Carrousel</span>
                </div>
            </div>

            <nav style="padding-bottom:20px">
                @foreach ($navSections as $section)
                    @if ($section['label'])
                        <div class="nav-section">{{ $section['label'] }}</div>
                    @endif
                    @foreach ($section['links'] as $link)
                        @php
                            $isActive = request()->routeIs($link['route']) || request()->routeIs(str($link['route'])->before('.').'.*');
                        @endphp
                        <a href="{{ route($link['route']) }}" class="side-link {{ $isActive ? 'active' : '' }}">
                            <span aria-hidden="true">{{ $link['icon'] }}</span>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </aside>

        <div class="main-wrap">
            <header class="topbar">
                <div style="display:flex;align-items:center;gap:12px">
                    <button class="sidebar-toggle theme-toggle" type="button" @click="open = !open" aria-label="Abrir menú">☰</button>
                    <span class="topbar-title">@yield('title', 'Gestión de Flota')</span>
                </div>

                <div style="display:flex;align-items:center;gap:10px">
                    <a href="{{ route('receptions.create') }}" class="btn btn-primary btn-sm" style="min-height:36px;padding:7px 12px;font-size:12.5px">
                        Nueva recepción
                    </a>
                    <span class="text-sm hidden md:inline" style="color:var(--muted)">{{ auth()->user()->name }}</span>
                    <button class="theme-toggle" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm" style="min-height:36px;padding:7px 12px;font-size:12.5px">Salir</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @if (session('status'))
                    <div class="mb-6 rounded-md border-l-4 border-brand-olive bg-brand-olive/10 px-4 py-3 text-sm" style="color:var(--ink)">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3 text-sm" style="color:var(--ink)">
                        <p class="font-medium mb-1">Corrige lo siguiente:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')

                <footer class="app-corporate-footer">
                    <div>© {{ date('Y') }} <strong>Carrousel Guatemala ✨🎠</strong> · Desarrollado por <strong>Diego Velasquez</strong> (<a href="https://github.com/dlomito-002" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">@dlomito-002</a>) en colaboración de <strong>Luis Fernando Zuniga</strong></div>
                </footer>
            </main>
        </div>
    </div>
@else
    <main class="min-h-full flex items-center justify-center px-4">
        <div class="w-full max-w-2xl py-10">
            @yield('content')
        </div>
    </main>
@endauth

<script>
    (function () {
        var btn = document.querySelector('[data-theme-toggle]');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var current = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('carrousel-theme', next); } catch (e) {}
        });
    })();
</script>
</body>
</html>
