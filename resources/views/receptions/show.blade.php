@extends('layouts.app')

@section('title', 'Detalle de recepción')

@section('content')
    @php use App\Enums\ConditionStatus; @endphp

    @php
        $equipmentPresent = $reception->equipmentChecks->where('is_present', true)->count();
        $equipmentTotal = $reception->equipmentChecks->count();
        $conditionIssues = $reception->conditionItems->filter(fn ($item) => $item->status->value !== 'ok')->count();
    @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">{{ $reception->vehicle->displayName() }}</h1>
            <p class="page-subtitle">
                Recibido el {{ $reception->reception_date->format('d/m/Y') }} a las {{ $reception->reception_time }}
                por {{ $reception->received_by_name }}
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <x-status-badge :status="$reception->status->value === 'open' ? 'pending' : 'ok'">
                {{ $reception->status->label() }}
            </x-status-badge>
            @if ($reception->delivery)
                <a href="{{ route('comparisons.show', $reception) }}" class="btn btn-outline-secondary btn-sm">
                    Ver comparación
                </a>
            @elseif ($reception->isOpen())
                <a href="{{ route('deliveries.damage-report', $reception) }}" class="btn btn-primary btn-sm">
                    Registrar devolución
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">Viaje y kilometraje</div>
            <div class="card-body">
                <dl class="kv-list">
                    <div class="kv-row"><dt>Motivo</dt><dd>{{ $reception->trip_reason }}</dd></div>
                    <div class="kv-row"><dt>Ubicación de recepción</dt><dd>{{ $reception->location }}</dd></div>
                    <div class="kv-row"><dt>Kilometraje inicial</dt><dd class="font-data">{{ number_format($reception->initial_mileage) }}</dd></div>
                    <div class="kv-row"><dt>Nivel de combustible</dt><dd>{{ $reception->fuel_level->label() }}</dd></div>
                    <div class="kv-row"><dt>Tipo de combustible</dt><dd>{{ $reception->fuel_type?->label() ?? '—' }}</dd></div>
                    <div class="kv-row"><dt>Lavado</dt><dd>{{ $reception->washed ? 'Sí' : 'No' }}</dd></div>
                    <div class="kv-row"><dt>Registrado por</dt><dd>{{ $reception->creator->name }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Inspección</div>
            <div class="card-body">
                <dl class="kv-list">
                    @foreach (ConditionStatus::fieldLabels() as $field => $label)
                        <div class="kv-row">
                            <dt>{{ $label }}</dt>
                            <dd>
                                <x-status-badge :status="$reception->{$field}->value === 'ok' ? 'ok' : 'anomaly'">
                                    {{ $reception->{$field}->label($field) }}
                                </x-status-badge>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>

    @if ($reception->has_anomaly)
        <div class="card" style="margin-top:16px;border-left:4px solid var(--danger)">
            <div class="card-header">Anomalía reportada</div>
            <div class="card-body">
                <p style="margin:0;font-size:13.5px;color:var(--ink)">{{ $reception->anomaly_description }}</p>
            </div>
        </div>
    @endif

    <div class="card" style="margin-top:16px">
        <div class="card-header">Documentación</div>
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px">
            @foreach ($reception->documentation as $doc)
                <x-status-badge :status="$doc->is_valid ? 'ok' : 'anomaly'">
                    {{ $doc->document_type->label() }}: {{ $doc->is_valid ? 'Sí' : 'No' }}
                </x-status-badge>
            @endforeach
        </div>
    </div>

    @if ($equipmentTotal > 0)
        <div class="card" style="margin-top:16px">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
                Chequeo general de equipo
                <span style="font-weight:400;font-size:12px;color:var(--muted)">{{ $equipmentPresent }} / {{ $equipmentTotal }} presentes</span>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">
                @foreach ($reception->equipmentChecks as $check)
                    <div class="item-row">
                        <span style="flex:1;min-width:0">{{ $check->item->label() }}</span>
                        <div style="display:flex;align-items:center;gap:8px;flex:0 0 auto">
                            <x-status-badge :status="$check->is_present ? 'ok' : 'anomaly'">
                                {{ $check->is_present ? 'Sí' : 'No' }}
                            </x-status-badge>
                            @if ($check->hasPhoto())
                                <a href="{{ $check->photoUrl() }}" target="_blank" style="color:var(--brand);font-weight:700;font-size:12px">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($reception->conditionItems->isNotEmpty())
        <div class="card" style="margin-top:16px">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
                Estado general del vehículo (detalle)
                @if ($conditionIssues > 0)
                    <x-status-badge status="anomaly">{{ $conditionIssues }} con incidencia</x-status-badge>
                @else
                    <x-status-badge status="ok">Sin incidencias</x-status-badge>
                @endif
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">
                @foreach ($reception->conditionItems as $item)
                    <div class="item-row">
                        <span style="flex:1;min-width:0">{{ $item->item->label() }}</span>
                        <div style="display:flex;align-items:center;gap:8px;flex:0 0 auto">
                            <x-status-badge :status="$item->status->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $item->status->label($item->item->value) }}
                            </x-status-badge>
                            @if ($item->hasPhoto())
                                <a href="{{ $item->photoUrl() }}" target="_blank" style="color:var(--brand);font-weight:700;font-size:12px">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card" style="margin-top:16px">
        <div class="card-header">Firma de quien recibió el vehículo</div>
        <div class="card-body">
            <p style="margin:0 0 10px;font-size:13.5px"><span style="color:var(--muted)">Firmado por:</span> <strong>{{ $reception->received_by_name }}</strong></p>
            @if ($reception->signaturePhoto())
                <img src="{{ $reception->signaturePhoto()->url() }}" alt="Firma de {{ $reception->received_by_name }}"
                     style="height:110px;border-radius:10px;border:1px solid var(--border);background:#fff">
            @else
                <p style="margin:0;font-size:13.5px;color:var(--muted)">No se capturó firma.</p>
            @endif
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-header">Fotografías</div>
        <div class="card-body">
            @php $galleryPhotos = $reception->photos->where('position', '!==', \App\Enums\PhotoPosition::Signature); @endphp
            @if ($galleryPhotos->isEmpty())
                <p style="margin:0;font-size:13.5px;color:var(--muted)">No se cargaron fotografías.</p>
            @else
                <div class="photo-grid">
                    @foreach ($galleryPhotos as $photo)
                        <a href="{{ $photo->url() }}" target="_blank">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->position->label() }}">
                            <p>{{ $photo->position->label() }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
