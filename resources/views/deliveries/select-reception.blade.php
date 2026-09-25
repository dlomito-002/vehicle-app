@extends('layouts.app')

@section('title', 'Registrar devolución: seleccionar recepción')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">{{ $vehicle->displayName() }}</h1>
            <p class="page-subtitle">Paso 2 de 2: este vehículo tiene más de una recepción abierta. Selecciona cuál cerrará esta devolución.</p>
        </div>
    </div>

    <div style="display:grid;gap:10px">
        @foreach ($openReceptions as $reception)
            <a href="{{ route('deliveries.damage-report', $reception) }}" class="card list-row" style="box-shadow:var(--shadow)">
                <div style="min-width:0">
                    <p class="list-row-title">Recibido el {{ $reception->reception_date->format('d/m/Y') }} a las {{ $reception->reception_time }}</p>
                    <p class="list-row-meta">
                        Recibido por {{ $reception->received_by_name }} · registrado por {{ $reception->creator->name }}
                        · {{ number_format($reception->initial_mileage) }} km
                    </p>
                </div>
                <span style="color:var(--brand);font-weight:700;font-size:13px;flex:0 0 auto">Seleccionar</span>
            </a>
        @endforeach
    </div>
@endsection
