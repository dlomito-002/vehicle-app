@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <span class="auth-eyebrow">Acceso seguro</span>
    <h2>Ingresa a Control de Vehículos</h2>
    <p class="auth-lead">Te enviaremos un código temporal de seis dígitos a tu correo.</p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form-v2" data-single-submit data-action-message="Enviando código de acceso…">
        @csrf

        <label for="email">Correo electrónico
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="nombre@carrousel.com.gt">
        </label>

        <button type="submit" class="btn btn-primary auth-primary">Enviar código</button>
    </form>

    <div class="auth-helper-v2">
        <span>i</span>
        <div><strong>¿Aún no tienes acceso?</strong> Tu cuenta debe estar habilitada por un administrador de la aplicación.</div>
    </div>
    <p class="auth-security-v2">El código OTP es temporal. No lo compartas: el personal de Carrousel nunca te lo pedirá.</p>

    <div class="auth-actions-v2">
        <div class="auth-action-group">
            <a class="btn btn-outline-secondary btn-sm" href="{{ \App\Support\Ecosystem::portalUrl() }}">← Portal</a>
        </div>
        <button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-eco-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
    </div>
@endsection
