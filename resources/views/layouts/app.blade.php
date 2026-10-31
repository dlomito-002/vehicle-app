<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#173d75">
    <title>@yield('title', 'Control de Vehiculos') · Carrousel</title>
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
    <link rel="stylesheet" href="{{ \App\Support\Ecosystem::asset('assets/css/ecosystem-ui.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" defer></script>

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
                    ['route' => 'vehicles.index', 'label' => 'Vehículos', 'icon' => '◇'],
                    ['route' => 'users.index', 'label' => 'Usuarios', 'icon' => '♟'],
                    ['route' => 'admin.audit.index', 'label' => 'Auditoría', 'icon' => '◉'],
                    ['route' => 'admin.mail.index', 'label' => 'Correo y notificaciones', 'icon' => '✉'],
                ],
            ];
        }

        $attentionCount = collect($ecoAttention ?? [])->sum('count');
        $ecoVersion = \App\Support\Ecosystem::ecosystemVersion();
        $appVersion = \App\Support\Ecosystem::appVersion();
    @endphp

    <div class="app-shell">
        <aside class="sidebar" id="app-sidebar" aria-label="Navegación principal">
            <div class="sidebar-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel" style="width:40px;height:40px;object-fit:contain;background:#fff;border-radius:8px;padding:3px;">
                <div>
                    <strong>Control de Vehículos</strong>
                    <span style="display:block">Corporación Carrousel</span>
                </div>
            </div>

            <nav>
                @foreach ($navSections as $section)
                    <div class="nav-section">{{ $section['label'] }}</div>
                    @foreach ($section['links'] as $link)
                        @php
                            $isActive = request()->routeIs($link['route']) || request()->routeIs(str($link['route'])->beforeLast('.').'.*');
                        @endphp
                        <a href="{{ route($link['route']) }}" class="side-link {{ $isActive ? 'active' : '' }}" title="{{ $link['label'] }}">
                            <span class="side-icon" aria-hidden="true">{{ $link['icon'] }}</span>
                            <span class="side-label">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="sidebar-version" title="Control de Vehículos v{{ $appVersion }}{{ $ecoVersion ? ' · Ecosistema Carrousel v'.$ecoVersion : '' }}">
                <span><strong>Control de Vehículos</strong> v{{ $appVersion }}</span>
                @if ($ecoVersion)
                    <span>Ecosistema v{{ $ecoVersion }}</span>
                @endif
            </div>
        </aside>
        <div class="sidebar-backdrop" data-sidebar-backdrop hidden></div>

        <div class="main-wrap">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="btn btn-outline-secondary sidebar-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="true" aria-label="Ocultar menú" title="Ocultar menú lateral">☰</button>
                    <a href="{{ route('dashboard') }}" class="topbar-title">
                        <span class="topbar-section">Control de Vehículos</span>
                        <strong class="topbar-page">@yield('title', 'Panel')</strong>
                    </a>
                </div>

                <div class="topbar-user">
                    <a href="{{ route('receptions.create') }}" class="btn btn-primary btn-sm topbar-primary-action" title="Nueva recepción">
                        <span aria-hidden="true">＋</span>
                        <span class="topbar-primary-label">Nueva recepción</span>
                    </a>

                    <div class="shell-notifications" data-notifications>
                        <button class="btn btn-outline-secondary btn-sm shell-icon-btn" type="button" data-notifications-toggle aria-expanded="false" aria-label="Ver notificaciones" title="Notificaciones">🔔@if ($attentionCount > 0)<span class="shell-notification-badge">{{ $attentionCount > 99 ? '99+' : $attentionCount }}</span>@endif</button>
                        <div class="shell-notification-menu" data-notifications-menu hidden>
                            <div class="shell-notification-head">
                                <div><strong>Notificaciones</strong><span>{{ $attentionCount > 0 ? $attentionCount.' pendientes' : 'Todo al día' }}</span></div>
                            </div>
                            @if (! empty($ecoAttention))
                                <div class="shell-attention-section">
                                    <div class="shell-attention-title">Pendientes <span>{{ $attentionCount }}</span></div>
                                    <div class="shell-notification-list">
                                        @foreach ($ecoAttention as $item)
                                            <a class="shell-notification-item attention" href="{{ $item['href'] }}">
                                                <span class="shell-notification-dot"></span>
                                                <span><strong>{{ $item['label'] }}</strong><small>{{ $item['detail'] }}</small></span>
                                                <span class="shell-notification-count">{{ $item['count'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="shell-notification-empty"><strong>Sin pendientes</strong>Aquí aparecerán los vehículos sin devolver y los mantenimientos por atender.</div>
                            @endif
                        </div>
                    </div>

                    <button class="btn btn-outline-secondary btn-sm theme-btn shell-icon-btn" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
                    <a class="btn btn-outline-secondary btn-sm portal-btn" href="{{ \App\Support\Ecosystem::portalUrl() }}" title="Volver al Portal">
                        <span aria-hidden="true">←</span>
                        <span class="portal-label">Portal</span>
                    </a>
                    <div class="user-summary">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span class="user-role">{{ auth()->user()->role->label() }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Salir</button>
                    </form>
                </div>
            </header>

            <main class="content">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <p>Corrige lo siguiente:</p>
                        <ul style="margin:0;padding-left:18px">
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

    @php($help = $ecoHelp ?? ['title' => 'Ayuda', 'steps' => [], 'tour' => []])
    <button class="help-fab" type="button" data-help-open aria-label="Abrir ayuda" aria-controls="eco-help" aria-expanded="false" title="Ayuda">?</button>
    <div class="help-backdrop" data-help-backdrop hidden></div>
    <aside class="help-panel" id="eco-help" data-help-panel role="dialog" aria-modal="true" aria-labelledby="eco-help-title" aria-hidden="true">
        <div class="help-panel-head">
            <div><span class="help-kicker">Ayuda de esta pantalla</span><h2 id="eco-help-title">{{ $help['title'] }}</h2></div>
            <button class="help-close" type="button" data-help-close aria-label="Cerrar ayuda">×</button>
        </div>
        <p class="help-intro">Consulta la guía rápida o inicia el recorrido guiado para ver cada parte directamente sobre la pantalla.</p>
        <ol class="help-steps">
            @foreach ($help['steps'] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
        <div class="help-actions">
            <button class="btn btn-primary" type="button" data-tour-start>Iniciar tutorial</button>
            <a class="btn btn-outline-secondary" href="{{ route('help.create') }}">Reportar un problema</a>
        </div>
        <div class="help-shortcuts"><span><kbd>Esc</kbd> Cerrar</span></div>
        <div class="help-contact">¿Aún necesitas ayuda? <strong>sistemas@carrousel.com.gt</strong></div>
    </aside>
    <script>window.ECO_TOUR = @json($help['tour'] ?? []);</script>
@else
    <main class="min-h-full flex items-center justify-center px-4">
        <div class="w-full max-w-2xl py-10">
            @yield('content')
        </div>
    </main>
@endauth

<link rel="stylesheet" href="{{ \App\Support\Ecosystem::asset('assets/css/vehiculos-ecosistema.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Ecosystem::asset('assets/css/ecosystem-ui-final.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Ecosystem::asset('assets/css/ecosystem.css') }}">
<script src="{{ \App\Support\Ecosystem::asset('assets/js/vehiculos-ecosistema.js') }}"></script>
<script src="{{ \App\Support\Ecosystem::asset('assets/js/ecosystem.js') }}"></script>
</body>
</html>
