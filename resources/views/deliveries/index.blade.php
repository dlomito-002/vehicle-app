@extends('layouts.app')

@section('title', 'Devoluciones')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Devoluciones de vehículos</h1>
            <p class="page-subtitle">Historial de devoluciones registradas.</p>
        </div>
        <a href="{{ route('deliveries.select-vehicle') }}" class="btn btn-primary">Registrar devolución</a>
    </div>

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Fecha de devolución</th>
                        <th>Devuelto por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td data-label="Vehículo">{{ $delivery->vehicle->displayName() }}</td>
                            <td data-label="Fecha de devolución">{{ $delivery->return_date->format('d/m/Y') }} · {{ $delivery->return_time }}</td>
                            <td data-label="Devuelto por">{{ $delivery->returned_by_name }}</td>
                            <td data-label="" class="data-table-actions">
                                <a href="{{ route('deliveries.show', $delivery) }}">Ver</a>
                                <a href="{{ route('comparisons.show', $delivery->reception) }}">Comparar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="data-table-empty">Todavía no hay devoluciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
