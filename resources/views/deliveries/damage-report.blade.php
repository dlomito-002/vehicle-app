@extends('layouts.app')

@section('title', 'Informe de daños')

@section('content')
    @php
        use App\Enums\ConditionStatus;

        $conditionIssues = collect(ConditionStatus::fieldLabels())
            ->filter(fn ($label, $field) => $reception->{$field}->value !== 'ok');

        $equipmentMissing = $reception->equipmentChecks->filter(fn ($check) => ! $check->is_present);
        $conditionComponentIssues = $reception->conditionItems->filter(fn ($item) => $item->status->value !== 'ok');
        $anomalyPhotos = $reception->photos->where('position', 'anomaly');
    @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">Informe de daños antes de la entrega</h1>
            <p class="page-subtitle">
                {{ $reception->vehicle->displayName() }} — recibido el {{ $reception->reception_date->format('d/m/Y') }}
                por {{ $reception->received_by_name }}
            </p>
        </div>
        <a href="{{ route('deliveries.damage-report.pdf', $reception) }}" class="btn btn-outline-secondary">
            Descargar PDF
        </a>
    </div>

    <p style="margin:0 0 20px;font-size:13.5px;color:var(--muted);line-height:1.55">
        Revisa los daños y faltantes registrados en la recepción antes de continuar con la devolución.
        Esto ayuda a distinguir lo que ya existía de lo que ocurra durante el uso del vehículo.
    </p>

    @if (! $reception->has_anomaly && $conditionIssues->isEmpty() && $equipmentMissing->isEmpty() && $conditionComponentIssues->isEmpty())
        <div class="alert alert-success">El vehículo se recibió sin daños ni faltantes registrados.</div>
    @endif

    @if ($reception->has_anomaly)
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--danger)">
            <div class="card-header">Anomalía reportada en la recepción</div>
            <div class="card-body">
                <p style="margin:0 0 12px;font-size:13.5px;color:var(--ink)">{{ $reception->anomaly_description }}</p>
                @if ($anomalyPhotos->isNotEmpty())
                    <div class="photo-grid">
                        @foreach ($anomalyPhotos as $photo)
                            <a href="{{ $photo->url() }}" target="_blank"><img src="{{ $photo->url() }}"></a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($conditionIssues->isNotEmpty())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">Inspección general con incidencias</div>
            <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach ($conditionIssues as $field => $label)
                    <x-status-badge status="anomaly">{{ $label }}: {{ $reception->{$field}->label($field) }}</x-status-badge>
                @endforeach
            </div>
        </div>
    @endif

    @if ($conditionComponentIssues->isNotEmpty())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">Componentes con incidencia</div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">
                @foreach ($conditionComponentIssues as $item)
                    <div class="item-row">
                        <span style="flex:1;min-width:0">{{ $item->item->label() }}</span>
                        <div style="display:flex;align-items:center;gap:8px;flex:0 0 auto">
                            <x-status-badge status="anomaly">{{ $item->status->label($item->item->value) }}</x-status-badge>
                            @if ($item->hasPhoto())
                                <a href="{{ $item->photoUrl() }}" target="_blank" style="color:var(--brand);font-weight:700;font-size:12px">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($equipmentMissing->isNotEmpty())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">Equipo faltante</div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">
                @foreach ($equipmentMissing as $check)
                    <div class="item-row">
                        <span style="flex:1;min-width:0">{{ $check->item->label() }}</span>
                        <div style="display:flex;align-items:center;gap:8px;flex:0 0 auto">
                            <x-status-badge status="anomaly">Faltante</x-status-badge>
                            @if ($check->hasPhoto())
                                <a href="{{ $check->photoUrl() }}" target="_blank" style="color:var(--brand);font-weight:700;font-size:12px">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="display:flex;justify-content:flex-end">
        <a href="{{ route('deliveries.create', $reception) }}" class="btn btn-primary">
            Continuar con la devolución
        </a>
    </div>
@endsection
