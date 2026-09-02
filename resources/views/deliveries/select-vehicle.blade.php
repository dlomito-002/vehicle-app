@extends('layouts.app')

@section('title', 'Registrar devolución: seleccionar vehículo')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Registrar una devolución</h1>
    <p class="text-sm text-slate-500 mb-6">Paso 1 de 2: selecciona el vehículo que será devuelto.</p>

    @if ($vehicles->isEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-6 text-sm text-slate-500">
            No hay vehículos con una recepción abierta en este momento.
        </div>
    @else
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach ($vehicles as $vehicle)
                <a href="{{ route('deliveries.select-reception', $vehicle) }}"
                   class="block bg-white border border-slate-200 rounded-lg p-4 hover:border-brand-cyan transition-colors">
                    <p class="font-medium text-slate-900">{{ $vehicle->displayName() }}</p>
                    <p class="text-sm text-slate-500 mt-0.5">
                        {{ $vehicle->openReceptions()->count() }} recepción(es) abierta(s)
                    </p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
