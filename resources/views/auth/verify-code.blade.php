@extends('layouts.guest')

@section('title', 'Verificar código')

@section('content')
    <div class="bg-white border border-slate-200 rounded-lg p-6">
        @if ($errors->any())
            <div class="mb-4 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3 text-sm text-slate-800">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 rounded-md border-l-4 border-brand-olive bg-brand-olive/10 px-4 py-3 text-sm text-slate-800">
                {{ session('status') }}
            </div>
        @endif

        <p class="text-sm text-slate-500 mb-4">
            Ingresa el código de 6 dígitos que enviamos a <strong>{{ $email }}</strong>.
        </p>

        <form method="POST" action="{{ route('login.verify') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Código de verificación</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="6" pattern="\d{6}" required autofocus
                       class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data tracking-widest text-center text-lg">
            </div>

            <button type="submit"
                    class="w-full inline-flex justify-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90 transition-colors">
                Verificar e iniciar sesión
            </button>
        </form>

        <form method="POST" action="{{ route('login') }}" class="mt-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 hover:underline">
                Reenviar código
            </button>
        </form>

        <a href="{{ route('login') }}" class="block mt-2 text-sm text-slate-500 hover:text-slate-900 hover:underline">
            Usar otro correo
        </a>
    </div>
@endsection
