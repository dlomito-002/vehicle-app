@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Panel</h1>
            <p class="page-subtitle">Vista general de recepciones abiertas y devoluciones recientes.</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">Recepciones abiertas</div>
            <div class="card-body" style="display:grid;gap:10px">
                @forelse ($openReceptions as $reception)
                    <a href="{{ route('receptions.show', $reception) }}" class="list-row">
                        <div style="min-width:0">
                            <p class="list-row-title">{{ $reception->vehicle->displayName() }}</p>
                            <p class="list-row-meta">{{ $reception->reception_date->format('d/m/Y') }} · {{ $reception->received_by_name }}</p>
                        </div>
                        <x-status-badge status="pending">Abierta</x-status-badge>
                    </a>
                @empty
                    <div class="empty-state">No hay recepciones abiertas en este momento.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">Cerradas recientemente</div>
            <div class="card-body" style="display:grid;gap:10px">
                @forelse ($recentlyClosed as $reception)
                    <a href="{{ route('comparisons.show', $reception) }}" class="list-row">
                        <div style="min-width:0">
                            <p class="list-row-title">{{ $reception->vehicle->displayName() }}</p>
                            <p class="list-row-meta">Devuelto el {{ $reception->delivery?->return_date?->format('d/m/Y') }}</p>
                        </div>
                        <x-status-badge status="ok">Cerrada</x-status-badge>
                    </a>
                @empty
                    <div class="empty-state">Todavía no hay recepciones cerradas.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
