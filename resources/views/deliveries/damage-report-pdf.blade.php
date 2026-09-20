<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de daños {{ $reception->vehicle->displayName() }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1.5px solid #334155; }
        p { margin: 0; }
        .muted { color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.data th { text-align: left; font-size: 8px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 4px 6px; }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }
        .badge-bad { background: #fde3c8; color: #a34a04; }
        .thumb { width: 45px; height: 45px; object-fit: cover; border: 1px solid #cbd5e1; margin-top: 2px; }
        .photo-grid { width: 100%; border-collapse: collapse; }
        .photo-grid td { width: 25%; text-align: center; padding: 4px; vertical-align: top; }
        .photo-grid img { width: 100%; height: 80px; object-fit: cover; border: 1px solid #cbd5e1; }
        .anomaly-box { background: #fde3c8; border: 1px solid #f2b705; padding: 6px 8px; margin-bottom: 6px; font-size: 9.5px; }
        .ok-box { background: #f0f5d0; border: 1px solid #acbf17; padding: 6px 8px; margin-bottom: 6px; font-size: 9.5px; }
    </style>
</head>
<body>
    @php
        use App\Support\PdfImageEncoder;
        use App\Enums\ConditionStatus;

        $conditionIssues = collect(ConditionStatus::fieldLabels())
            ->filter(fn ($label, $field) => $reception->{$field}->value !== 'ok');
        $equipmentMissing = $reception->equipmentChecks->filter(fn ($check) => ! $check->is_present);
        $conditionComponentIssues = $reception->conditionItems->filter(fn ($item) => $item->status->value !== 'ok');
        $anomalyPhotos = $reception->photos->where('position', 'anomaly');
    @endphp

    <h1>Informe de daños antes de la entrega</h1>
    <p class="muted">
        {{ $reception->vehicle->displayName() }} — recibido el {{ $reception->reception_date->format('d/m/Y') }}
        por {{ $reception->received_by_name }}
    </p>

    @if (! $reception->has_anomaly && $conditionIssues->isEmpty() && $equipmentMissing->isEmpty() && $conditionComponentIssues->isEmpty())
        <div class="ok-box">El vehículo se recibió sin daños ni faltantes registrados.</div>
    @endif

    @if ($reception->has_anomaly)
        <h2>Anomalía reportada en la recepción</h2>
        <div class="anomaly-box">{{ $reception->anomaly_description }}</div>
        @if ($anomalyPhotos->isNotEmpty())
            <table class="photo-grid">
                <tr>
                    @foreach ($anomalyPhotos as $photo)
                        @php $src = PdfImageEncoder::fromModel($photo); @endphp
                        <td>@if ($src)<img src="{{ $src }}">@endif</td>
                    @endforeach
                </tr>
            </table>
        @endif
    @endif

    @if ($conditionIssues->isNotEmpty())
        <h2>Inspección general con incidencias</h2>
        <table class="data">
            <thead><tr><th>Elemento</th><th>Estado</th></tr></thead>
            <tbody>
                @foreach ($conditionIssues as $field => $label)
                    <tr><td>{{ $label }}</td><td><span class="badge badge-bad">{{ $reception->{$field}->label($field) }}</span></td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($conditionComponentIssues->isNotEmpty())
        <h2>Componentes con incidencia</h2>
        <table class="data">
            <thead><tr><th style="width:30%">Elemento</th><th style="width:20%">Estado</th><th style="width:20%">Foto</th></tr></thead>
            <tbody>
                @foreach ($conditionComponentIssues as $item)
                    <tr>
                        <td>{{ $item->item->label() }}</td>
                        <td><span class="badge badge-bad">{{ $item->status->label($item->item->value) }}</span></td>
                        <td>
                            @php $src = PdfImageEncoder::fromModel($item, 'photo_disk', 'photo_path'); @endphp
                            @if ($src)<img class="thumb" src="{{ $src }}">@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($equipmentMissing->isNotEmpty())
        <h2>Equipo faltante</h2>
        <table class="data">
            <thead><tr><th style="width:30%">Elemento</th><th style="width:20%">Estado</th><th style="width:20%">Foto</th></tr></thead>
            <tbody>
                @foreach ($equipmentMissing as $check)
                    <tr>
                        <td>{{ $check->item->label() }}</td>
                        <td><span class="badge badge-bad">Faltante</span></td>
                        <td>
                            @php $src = PdfImageEncoder::fromModel($check, 'photo_disk', 'photo_path'); @endphp
                            @if ($src)<img class="thumb" src="{{ $src }}">@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
