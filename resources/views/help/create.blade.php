@extends('layouts.app')

@section('title', 'Ayuda')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Ayuda</h1>
    <p class="text-sm text-slate-500 mb-6">
        Reporta un problema o inconveniente con la aplicación o con la gestión de vehículos.
        Se enviará directamente al encargado de flota.
    </p>

    <form method="POST" action="{{ route('help.store') }}" class="max-w-xl bg-white border border-slate-200 rounded-lg p-5 space-y-4">
        @csrf

        <div>
            <label for="message" class="block text-sm font-medium text-slate-700 mb-1">Descripción del problema</label>
            <textarea id="message" name="message" rows="6" required
                      placeholder="Describe el problema o inconveniente que encontraste..."
                      class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">{{ old('message') }}</textarea>
            @error('message')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <p class="text-xs text-slate-400">
            Se enviará como {{ auth()->user()->name }} ({{ auth()->user()->email }}).
        </p>

        <button type="submit"
                class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Enviar reporte
        </button>
    </form>
@endsection
