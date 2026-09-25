@extends('layouts.app')

@section('title', 'Reporte comparativo')

@section('content')
    @php use App\Enums\ConditionStatus; @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">{{ $reception->vehicle->displayName() }}</h1>
            <p class="page-subtitle">
                Recepción {{ $reception->reception_date->format('d/m/Y') }} → Devolución {{ $delivery->return_date->format('d/m/Y') }}
            </p>
            <p class="page-subtitle">
                Recibió y firmó: {{ $reception->received_by_name }} · Devolvió y firmó: {{ $delivery->returned_by_name }}
            </p>
        </div>
        <a href="{{ route('comparisons.pdf', $reception) }}" class="btn btn-primary">Descargar PDF</a>
    </div>

    @if ($comparison['new_anomaly'])
        <div class="alert alert-danger">Se reportó una nueva anomalía en la devolución que no estaba presente en la recepción.</div>
    @endif

    <div class="grid grid-4">
        <div class="card">
            <div class="card-body">
                <p style="margin:0 0 4px;font-size:13px;color:var(--muted)">Kilometraje</p>
                <p class="font-data" style="margin:0;font-size:24px;font-weight:800;color:var(--ink)">{{ number_format($comparison['mileage']['delta']) }} km</p>
                <p style="margin:4px 0 0;font-size:12px;color:var(--muted)">
                    {{ number_format($comparison['mileage']['initial']) }} → {{ number_format($comparison['mileage']['final']) }}
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="margin:0 0 4px;font-size:13px;color:var(--muted)">Nivel de combustible</p>
                <p style="margin:0;font-size:13.5px;color:var(--ink)">
                    {{ $comparison['fuel_level']['reception']->label() }} → {{ $comparison['fuel_level']['delivery']->label() }}
                </p>
                @if ($comparison['fuel_level']['decreased'])
                    <x-status-badge status="anomaly" style="margin-top:8px">Menor que en la recepción</x-status-badge>
                @elseif ($comparison['fuel_level']['changed'])
                    <x-status-badge status="pending" style="margin-top:8px">Cambió</x-status-badge>
                @else
                    <x-status-badge status="ok" style="margin-top:8px">Sin cambios</x-status-badge>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="margin:0 0 4px;font-size:13px;color:var(--muted)">Anomalía reportada</p>
                <p style="margin:0;font-size:13.5px;color:var(--ink)">
                    Recepción: {{ $reception->has_anomaly ? 'Sí' : 'No' }} · Devolución: {{ $delivery->has_anomaly ? 'Sí' : 'No' }}
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p style="margin:0 0 4px;font-size:13px;color:var(--muted)">Lavado (carwash)</p>
                <p style="margin:0;font-size:13.5px;color:var(--ink)">
                    Recepción: {{ $reception->washed ? 'Sí' : 'No' }} · Devolución: {{ $delivery->washed ? 'Sí' : 'No' }}
                </p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">Comparación del estado</div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th>Recepción</th>
                        <th>Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['conditions'] as $field => $diff)
                        <tr>
                            <td data-label="Elemento">{{ ConditionStatus::fieldLabels()[$field] }}</td>
                            <td data-label="Recepción">
                                <x-status-badge :status="$diff['reception']->value === 'ok' ? 'ok' : 'anomaly'">
                                    {{ $diff['reception']->label($field) }}
                                </x-status-badge>
                            </td>
                            <td data-label="Devolución">
                                <x-status-badge :status="$diff['delivery']->value === 'ok' ? 'ok' : 'anomaly'">
                                    {{ $diff['delivery']->label($field) }}
                                </x-status-badge>
                                @if ($diff['worsened'])
                                    <span class="badge badge-danger" style="margin-left:4px">nuevo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">
            Comparación de equipo (26 ítems)
            <div style="font-weight:400;font-size:12px;color:var(--muted);margin-top:2px">Chequeo general de equipo, elemento por elemento.</div>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th>Recepción</th>
                        <th>Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['equipment'] as $diff)
                        <tr>
                            <td data-label="Elemento">{{ $diff['label'] }}</td>
                            <td data-label="Recepción">
                                @if ($diff['reception_present'] === null)
                                    <span style="font-size:12px;color:var(--muted)">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['reception_present'] ? 'ok' : 'anomaly'">
                                        {{ $diff['reception_present'] ? 'Sí' : 'No' }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['reception_photo'])
                                    <a href="{{ $diff['reception_photo'] }}" target="_blank" style="display:block;margin-top:6px">
                                        <img src="{{ $diff['reception_photo'] }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                                    </a>
                                @endif
                            </td>
                            <td data-label="Devolución">
                                @if ($diff['delivery_present'] === null)
                                    <span style="font-size:12px;color:var(--muted)">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['delivery_present'] ? 'ok' : 'anomaly'">
                                        {{ $diff['delivery_present'] ? 'Sí' : 'No' }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['worsened'])
                                    <span class="badge badge-danger" style="margin-left:4px">faltante</span>
                                @elseif ($diff['changed'])
                                    <span class="badge badge-warning" style="margin-left:4px">cambió</span>
                                @endif
                                @if ($diff['delivery_photo'])
                                    <a href="{{ $diff['delivery_photo'] }}" target="_blank" style="display:block;margin-top:6px">
                                        <img src="{{ $diff['delivery_photo'] }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">
            Comparación de estado por componente (12 ítems)
            <div style="font-weight:400;font-size:12px;color:var(--muted);margin-top:2px">Detalle del estado general del vehículo, componente por componente.</div>
        </div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Recepción</th>
                        <th>Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['condition_items'] as $diff)
                        <tr>
                            <td data-label="Componente">{{ $diff['label'] }}</td>
                            <td data-label="Recepción">
                                @if ($diff['reception'] === null)
                                    <span style="font-size:12px;color:var(--muted)">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['reception']->value === 'ok' ? 'ok' : 'anomaly'">
                                        {{ $diff['reception']->label($diff['item']) }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['reception_photo'])
                                    <a href="{{ $diff['reception_photo'] }}" target="_blank" style="display:block;margin-top:6px">
                                        <img src="{{ $diff['reception_photo'] }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                                    </a>
                                @endif
                            </td>
                            <td data-label="Devolución">
                                @if ($diff['delivery'] === null)
                                    <span style="font-size:12px;color:var(--muted)">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['delivery']->value === 'ok' ? 'ok' : 'anomaly'">
                                        {{ $diff['delivery']->label($diff['item']) }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['worsened'])
                                    <span class="badge badge-danger" style="margin-left:4px">nuevo</span>
                                @elseif ($diff['changed'])
                                    <span class="badge badge-warning" style="margin-left:4px">cambió</span>
                                @endif
                                @if ($diff['delivery_photo'])
                                    <a href="{{ $diff['delivery_photo'] }}" target="_blank" style="display:block;margin-top:6px">
                                        <img src="{{ $diff['delivery_photo'] }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">Comparación de documentación</div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Recepción</th>
                        <th>Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['documentation'] as $doc)
                        <tr>
                            <td data-label="Documento">{{ \App\Enums\DocumentType::from($doc['document_type'])->label() }}</td>
                            <td data-label="Recepción">{{ $doc['reception'] === null ? '—' : ($doc['reception'] ? 'Sí' : 'No') }}</td>
                            <td data-label="Devolución">
                                {{ $doc['delivery'] === null ? 'No se comprobó en la devolución' : ($doc['delivery'] ? 'Sí' : 'No') }}
                                @if ($doc['changed'])
                                    <span class="badge badge-danger" style="margin-left:4px">cambió</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">Evidencia fotográfica</div>
        <div class="card-body" style="display:grid;gap:20px">
            @forelse ($comparison['photos'] as $group)
                <div>
                    <p class="section-label">{{ \App\Enums\PhotoPosition::from($group['position'])->label() }}</p>
                    <div class="grid grid-2">
                        <div>
                            <p style="margin:0 0 6px;font-size:11.5px;color:var(--muted)">Recepción</p>
                            @forelse ($group['reception_photos'] as $photo)
                                <a href="{{ $photo->url() }}" target="_blank">
                                    <img src="{{ $photo->url() }}" style="width:100%;height:130px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
                                </a>
                            @empty
                                <div style="width:100%;height:130px;border-radius:10px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--muted)">
                                    Sin fotografía
                                </div>
                            @endforelse
                        </div>
                        <div>
                            <p style="margin:0 0 6px;font-size:11.5px;color:var(--muted)">Devolución</p>
                            @forelse ($group['delivery_photos'] as $photo)
                                <a href="{{ $photo->url() }}" target="_blank">
                                    <img src="{{ $photo->url() }}" style="width:100%;height:130px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
                                </a>
                            @empty
                                <div style="width:100%;height:130px;border-radius:10px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--muted)">
                                    Sin fotografía
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <p style="margin:0;font-size:13.5px;color:var(--muted)">No se cargaron fotografías para ninguna de las dos etapas.</p>
            @endforelse
        </div>
    </div>
@endsection
