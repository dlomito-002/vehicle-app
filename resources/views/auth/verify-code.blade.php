@extends('layouts.guest')

@section('title', 'Verificar código')

@section('content')
    <span class="auth-eyebrow">Acceso seguro</span>
    <h2>Verifica tu código</h2>
    <p class="auth-lead">Ingresa el código de 6 dígitos que enviamos a <strong>{{ $email }}</strong>.</p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium mb-1" style="color:var(--ink)">Código de verificación</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="\d{6}" required autofocus
                   class="w-full rounded-md text-sm font-data text-center"
                   style="min-height:48px;font-size:24px;font-weight:800;letter-spacing:.33em;padding:11px 13px 11px calc(13px + .33em);">
        </div>

        <button type="submit" class="btn btn-primary w-full">Verificar e iniciar sesión</button>
    </form>

    <div class="auth-actions" style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:18px">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Reenviar código</button>
        </form>

        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Usar otro correo</a>

        <button class="theme-toggle" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
    </div>

    <p class="auth-security">El código vence en 10 minutos y solo puede usarse una vez.</p>
@endsection
