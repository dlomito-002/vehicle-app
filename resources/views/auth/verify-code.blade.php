@extends('layouts.guest')

@section('title', 'Verificar código')

@section('content')
    <span class="auth-eyebrow">Verificación</span>
    <h2>Escribe tu código</h2>
    <p class="auth-lead">Si <strong>{{ $email }}</strong> tiene acceso, recibirás el código en unos segundos.</p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.verify') }}" class="auth-form-v2" data-single-submit data-action-message="Validando código…">
        @csrf

        <label for="code">Código de 6 dígitos
            <input id="code" class="form-control otp-input-v2" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="\d{6}" required autofocus placeholder="000000">
        </label>

        <button type="submit" class="btn btn-primary auth-primary">Ingresar</button>
    </form>

    <div class="auth-helper-v2"><span>i</span><div>Si expiró o no llegó, solicita uno nuevo. El código vence en 10 minutos.</div></div>
    <p class="auth-security-v2">El código OTP es temporal. No lo compartas: el personal de Carrousel nunca te lo pedirá.</p>

    <div class="auth-actions-v2">
        <div class="auth-action-group">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">Cambiar correo</a>
            <form method="POST" action="{{ route('login') }}" data-single-submit data-action-message="Reenviando código…">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="btn btn-outline-primary btn-sm">Reenviar código</button>
            </form>
        </div>
        <button class="btn btn-outline-secondary btn-sm theme-btn" type="button" data-eco-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
    </div>
@endsection
