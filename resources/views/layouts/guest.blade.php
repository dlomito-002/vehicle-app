<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestión de Flota')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: {
            magenta: '#BF1F94', cyan: '#0DB3D9', olive: '#ACBF17', amber: '#F2B705', orange: '#F28705',
        } } } } }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Public Sans', system-ui, sans-serif; }
        input:not([type="radio"]):not([type="checkbox"]),
        select,
        textarea {
            border: 1px solid #94a3b8 !important;
            background-color: #fff;
            border-radius: 0.375rem;
        }
        input:not([type="radio"]):not([type="checkbox"]):focus,
        select:focus,
        textarea:focus {
            border-color: #0db3d9 !important;
            box-shadow: 0 0 0 3px rgb(13 179 217 / 16%);
            outline: none;
        }
    </style>
</head>
<body class="h-full bg-slate-50">
    <div class="min-h-full flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <span class="font-semibold text-2xl tracking-tight text-slate-900">Fleet <span class="text-brand-magenta">Desk</span></span>
                <p class="text-sm text-slate-500 mt-1">Registro de recepción y devolución de vehículos</p>
            </div>
            @yield('content')
        </div>
    </div>
</body>
</html>
