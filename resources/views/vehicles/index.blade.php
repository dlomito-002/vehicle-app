@extends('layouts.app')

@section('title', 'Vehículos')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Vehículos</h1>
            <p class="page-subtitle">Flota registrada y su historial de recepciones/devoluciones.</p>
        </div>
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary">Agregar vehículo</a>
    </div>

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Marca / modelo</th>
                        <th>Placa</th>
                        <th>Recepciones</th>
                        <th>Devoluciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td data-label="Marca / modelo">{{ $vehicle->make }} {{ $vehicle->model }}</td>
                            <td data-label="Placa" class="font-data">{{ $vehicle->license_plate }}</td>
                            <td data-label="Recepciones">{{ $vehicle->receptions_count }}</td>
                            <td data-label="Devoluciones">{{ $vehicle->deliveries_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="data-table-empty">Todavía no hay vehículos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $vehicles->links() }}</div>
@endsection
