@extends('layouts.app')

@section('title', 'Registrar devolución: seleccionar vehículo')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Registrar una devolución</h1>
            <p class="page-subtitle">Paso 1 de 2: selecciona el vehículo que será devuelto.</p>
        </div>
    </div>

    @if ($vehicles->isEmpty())
        <div class="card"><div class="card-body empty-state">No hay vehículos con una recepción abierta en este momento.</div></div>
    @else
        <div class="grid grid-2">
            @foreach ($vehicles as $vehicle)
                <a href="{{ route('deliveries.select-reception', $vehicle) }}" class="card list-row" style="box-shadow:var(--shadow)">
                    <div>
                        <p class="list-row-title">{{ $vehicle->displayName() }}</p>
                        <p class="list-row-meta">{{ $vehicle->openReceptions()->count() }} recepción(es) abierta(s)</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
