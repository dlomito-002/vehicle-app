@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <span class="auth-eyebrow">Acceso seguro</span>
    <h2>Ingresa al sistema</h2>
    <p class="auth-lead">Te enviaremos un código temporal a tu correo. Sin contraseñas.</p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium mb-1" style="color:var(--ink)">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   placeholder="nombre@carrousel.com.gt"
                   class="w-full rounded-md text-sm" style="min-height:48px;padding:11px 13px;">
        </div>

        <label class="flex items-center gap-2 text-sm" style="color:var(--muted)">
            <input type="checkbox" name="remember">
            Recordarme
        </label>

        <button type="submit" class="btn btn-primary w-full">Continuar</button>
    </form>

    <div class="auth-helper">
        <span>i</span>
        <div><strong>¿Aún no tienes acceso?</strong> Contacta a un administrador para que te dé de alta en el sistema.</div>
    </div>
    <p class="auth-security">El código es temporal y de un solo uso. No lo compartas.</p>

    <div class="mt-4 flex items-center justify-end">
        <button class="theme-toggle" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
    </div>
@endsection
