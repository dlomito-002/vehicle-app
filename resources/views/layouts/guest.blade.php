<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#173d75">
    <title>@yield('title', 'Acceso')  &middot; Control de Vehículos Carrousel</title>

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans">
<div class="brand-strip"></div>
<main class="auth-body">
    <div class="auth-layout">
        <section class="auth-brand" aria-label="Control de Vehículos Carrousel">
            <div class="auth-brand-content">
                <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel"
                     style="display:block;width:150px;max-height:84px;object-fit:contain;background:#fff;border-radius:14px;padding:8px;margin-bottom:28px;">
                <span class="auth-product-pill">Control de Vehículos</span>
                <h1>Recepción y devolución de vehículos, con evidencia en cada paso.</h1>
                <p>Registra el estado del vehículo al salir y al volver, compara ambos momentos y mantén el mantenimiento al día.</p>
                <div class="auth-points">
                    <span>Trazabilidad</span>
                    <span>Fotografías</span>
                    <span>Mantenimiento</span>
                </div>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-mobile-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel" style="width:70px;height:46px;object-fit:contain;padding:4px;background:#fff;border-radius:9px;">
                    <strong>Control de Vehículos</strong>
                </div>

                @yield('content')
            </div>
        </section>
    </div>
</main>

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
