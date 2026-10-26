<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#173d75">
    <title>@yield('title', 'Acceso') &middot; Control de Vehículos Carrousel</title>

    <script>
        try {
            const stored = localStorage.getItem('carrousel-theme') || 'system';
            const resolved = stored === 'system'
                ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : stored;
            document.documentElement.dataset.theme = resolved;
        } catch (e) {}
    </script>

    {{-- Ecosistema Carrousel §9: pantalla de acceso autocontenida, igual en todas las apps. --}}
    <link rel="stylesheet" href="{{ \App\Support\Ecosystem::asset('assets/css/ecosystem.css') }}">
</head>
<body class="auth-body-v2">
<div class="brand-strip"></div>
<main class="auth-layout-v2">
    <section class="auth-brand-v2" aria-label="Control de Vehículos Carrousel">
        <div class="auth-brand-content">
            <img class="auth-brand-logo" src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel">
            <span class="auth-product-pill">Control de Vehículos</span>
            <h1>Recepción y devolución de vehículos, con evidencia en cada paso.</h1>
            <p>Registra el estado del vehículo al salir y al volver, compara ambos momentos y mantén el mantenimiento al día.</p>
            <div class="auth-points-v2">
                <span>Trazabilidad</span>
                <span>Fotografías</span>
                <span>Mantenimiento</span>
            </div>
        </div>
    </section>

    <section class="auth-panel-v2">
        <div class="auth-card-v2">
            <div class="auth-mobile-brand-v2">
                <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel">
                <strong>Control de Vehículos</strong>
            </div>

            @yield('content')
        </div>
    </section>
</main>
<script src="{{ \App\Support\Ecosystem::asset('assets/js/ecosystem.js') }}"></script>
<script>
    document.querySelectorAll('form[data-single-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('[type=submit]').forEach(function (btn) { btn.disabled = true; });
        });
    });
</script>
</body>
</html>
