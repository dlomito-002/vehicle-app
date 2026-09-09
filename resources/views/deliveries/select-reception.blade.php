@extends('layouts.app')

@section('title', 'Registrar devolución: seleccionar recepción')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">{{ $vehicle->displayName() }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        Paso 2 de 2: este vehículo tiene más de una recepción abierta. Selecciona cuál cerrará esta devolución.
    </p>

    <div class="space-y-3">
        @foreach ($openReceptions as $reception)
                <a href="{{ route('deliveries.create', $reception) }}"
                    class="flex flex-wrap items-center justify-between gap-3 bg-white border border-slate-200 rounded-lg p-4 hover:border-brand-cyan transition-colors">
                     <div class="min-w-0">
                    <p class="font-medium text-slate-900">
                        Recibido el {{ $reception->reception_date->format('d/m/Y') }} a las {{ $reception->reception_time }}
                    </p>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Recibido por {{ $reception->received_by_name }} · registrado por {{ $reception->creator->name }}
                        · {{ number_format($reception->initial_mileage) }} km
                    </p>
                </div>
                <span class="text-brand-cyan text-sm font-medium">Seleccionar</span>
            </a>
        @endforeach
    </div>
@endsection
