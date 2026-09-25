@extends('layouts.app')

@section('title', 'Recepciones')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Recepciones de vehículos</h1>
            <p class="page-subtitle">Historial de recepciones registradas, abiertas y cerradas.</p>
        </div>
        <a href="{{ route('receptions.create') }}" class="btn btn-primary">Nueva recepción</a>
    </div>

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Fecha</th>
                        <th>Recibido por</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receptions as $reception)
                        <tr>
                            <td data-label="Vehículo">{{ $reception->vehicle->displayName() }}</td>
                            <td data-label="Fecha">{{ $reception->reception_date->format('d/m/Y') }} · {{ $reception->reception_time }}</td>
                            <td data-label="Recibido por">{{ $reception->received_by_name }}</td>
                            <td data-label="Estado">
                                <x-status-badge :status="$reception->status->value === 'open' ? 'pending' : 'ok'">
                                    {{ $reception->status->label() }}
                                </x-status-badge>
                            </td>
                            <td data-label="" class="data-table-actions">
                                <a href="{{ route('receptions.show', $reception) }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="data-table-empty">Todavía no hay recepciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $receptions->links() }}</div>
@endsection
