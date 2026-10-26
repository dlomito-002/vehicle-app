@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
    <div class="page-heading">
        <div>
            <span class="page-kicker">Administración</span>
            <h1 class="page-title">Auditoría</h1>
            <p class="page-subtitle">Quién realizó cada acción, cuándo y desde dónde.</p>
        </div>
        <a href="{{ route('admin.mail.index') }}" class="btn btn-outline-secondary">Correo y notificaciones</a>
    </div>

    <div class="audit-stats">
        <section class="card"><span>Hoy</span><strong>{{ $stats['today'] }}</strong></section>
        <section class="card"><span>Últimos 7 días</span><strong>{{ $stats['week'] }}</strong></section>
        <section class="card"><span>Ingresos (7 días)</span><strong>{{ $stats['logins'] }}</strong></section>
        <section class="card {{ $stats['failures'] > 0 ? 'is-alert' : '' }}"><span>Códigos incorrectos (7 días)</span><strong>{{ $stats['failures'] }}</strong></section>
    </div>

    <section class="card audit-filter-card">
        <form method="GET" class="audit-filter-grid">
            <div>
                <label class="form-label" for="a-q">Buscar</label>
                <input id="a-q" name="q" value="{{ $filters['q'] }}" placeholder="Correo, registro, IP…">
            </div>
            <div>
                <label class="form-label" for="a-action">Acción</label>
                <select id="a-action" name="action">
                    <option value="">Todas</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ \App\Support\Audit::label($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="a-from">Desde</label>
                <input id="a-from" type="date" name="from" value="{{ $filters['from'] }}">
            </div>
            <div>
                <label class="form-label" for="a-to">Hasta</label>
                <input id="a-to" type="date" name="to" value="{{ $filters['to'] }}">
            </div>
            <div class="audit-filter-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </section>

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Registro</th>
                        <th>Persona</th>
                        <th>IP</th>
                        <th class="data-table-actions">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td data-label="Fecha" class="data-table-nowrap"><strong>{{ $log->created_at?->format('d/m/Y') }}</strong> <span class="muted">{{ $log->created_at?->format('H:i:s') }}</span></td>
                            <td data-label="Acción"><strong>{{ \App\Support\Audit::label($log->action) }}</strong></td>
                            <td data-label="Registro">{{ $log->subject_type }}{{ $log->subject_id !== '' ? ' · '.$log->subject_id : '' }}</td>
                            <td data-label="Persona">{{ $log->actor ?: 'Sistema / público' }}</td>
                            <td data-label="IP" class="muted">{{ $log->ip ?: '—' }}</td>
                            <td data-label="Detalle" class="data-table-actions">
                                @if ($log->details)
                                    <details class="audit-details">
                                        <summary>Ver detalle</summary>
                                        <pre>{{ json_encode($log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="data-table-empty">No hay eventos que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($logs->hasPages())
        <nav class="audit-pagination">
            <span>Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}</span>
            <div>
                @if (! $logs->onFirstPage())
                    <a class="btn btn-outline-secondary btn-sm" href="{{ $logs->previousPageUrl() }}">← Anterior</a>
                @endif
                @if ($logs->hasMorePages())
                    <a class="btn btn-outline-secondary btn-sm" href="{{ $logs->nextPageUrl() }}">Siguiente →</a>
                @endif
            </div>
        </nav>
    @endif
@endsection
