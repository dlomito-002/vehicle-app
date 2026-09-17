@extends('layouts.guest')

@section('title', 'Iniciar sesión')

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
            Ingresa tu correo electrónico. Te enviaremos un código de verificación para iniciar sesión.
        </p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-magenta focus:ring-brand-magenta">
                Recordarme
            </label>

            <button type="submit"
                    class="w-full inline-flex justify-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90 transition-colors">
                Enviar código
            </button>
        </form>
    </div>
@endsection
