<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comparación {{ $reception->vehicle->displayName() }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1.5px solid #334155; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .summary td { width: 33.33%; vertical-align: top; padding: 8px; border: 1px solid #e2e8f0; }
        .summary .label { font-size: 8px; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
        .summary .value { font-size: 13px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.data th { text-align: left; font-size: 8px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 4px 6px; }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }
        .badge-ok { background: #f0f5d0; color: #55620c; }
        .badge-bad { background: #fde3c8; color: #a34a04; }
        .flag { color: #a34a04; font-size: 8px; margin-left: 3px; }
        .thumb { width: 40px; height: 40px; object-fit: cover; border: 1px solid #cbd5e1; margin-top: 2px; }
        .photo-grid { width: 100%; border-collapse: collapse; }
        .photo-grid td { width: 16.6%; text-align: center; padding: 4px; vertical-align: top; }
        .photo-grid img { width: 100%; height: 70px; object-fit: cover; border: 1px solid #cbd5e1; }
        .photo-caption { font-size: 7.5px; color: #64748b; margin-top: 2px; }
        .no-photo { width: 100%; height: 70px; border: 1px dashed #cbd5e1; }
        .section-note { font-size: 8px; color: #64748b; margin-bottom: 4px; }
        .anomaly-box { background: #fde3c8; border: 1px solid #f2b705; padding: 6px 8px; margin-bottom: 6px; font-size: 9.5px; }
    </style>
</head>
<body>
    @php
        use App\Support\PdfImageEncoder;
    @endphp

    <h1>{{ $reception->vehicle->displayName() }}</h1>
    <p class="muted">
        Recepción {{ $reception->reception_date->format('d/m/Y') }} ({{ $reception->received_by_name }})
        &nbsp;→&nbsp;
        Devolución {{ $delivery->return_date->format('d/m/Y') }} ({{ $delivery->returned_by_name }})
    </p>

    @if ($comparison['new_anomaly'])
        <div class="anomaly-box">
            Se reportó una nueva anomalía en la devolución que no estaba presente en la recepción.
            @if ($delivery->anomaly_description)
                <br>{{ $delivery->anomaly_description }}
            @endif
        </div>
    @endif

    <table class="summary">
        <tr>
            <td>
                <p class="label">Kilometraje recorrido</p>
                <p class="value">{{ number_format($comparison['mileage']['delta']) }} km</p>
                <p class="muted">{{ number_format($comparison['mileage']['initial']) }} &rarr; {{ number_format($comparison['mileage']['final']) }}</p>
            </td>
            <td>
                <p class="label">Nivel de combustible</p>
                <p class="value" style="font-size:10.5px;">
                    {{ $comparison['fuel_level']['reception']->label() }} &rarr; {{ $comparison['fuel_level']['delivery']->label() }}
                </p>
                @if ($comparison['fuel_level']['decreased'])
                    <span class="badge badge-bad">Menor que en recepción</span>
                @elseif ($comparison['fuel_level']['changed'])
                    <span class="badge badge-ok">Cambió</span>
                @else
                    <span class="badge badge-ok">Sin cambios</span>
                @endif
            </td>
            <td>
                <p class="label">Anomalía reportada</p>
                <p class="value" style="font-size:10.5px;">
                    Recepción: {{ $reception->has_anomaly ? 'Sí' : 'No' }} &middot; Devolución: {{ $delivery->has_anomaly ? 'Sí' : 'No' }}
                </p>
            </td>
            <td>
                <p class="label">Lavado (carwash)</p>
                <p class="value" style="font-size:10.5px;">
                    Recepción: {{ $reception->washed ? 'Sí' : 'No' }} &middot; Devolución: {{ $delivery->washed ? 'Sí' : 'No' }}
                </p>
            </td>
        </tr>
    </table>

    <h2>Inspección general (5 campos)</h2>
    <table class="data">
        <thead><tr><th style="width:40%">Elemento</th><th style="width:30%">Recepción</th><th style="width:30%">Devolución</th></tr></thead>
        <tbody>
            @foreach ($comparison['conditions'] as $field => $diff)
                <tr>
                    <td>{{ \App\Enums\ConditionStatus::fieldLabels()[$field] }}</td>
                    <td><span class="badge {{ $diff['reception']->value === 'ok' ? 'badge-ok' : 'badge-bad' }}">{{ $diff['reception']->label($field) }}</span></td>
                    <td>
                        <span class="badge {{ $diff['delivery']->value === 'ok' ? 'badge-ok' : 'badge-bad' }}">{{ $diff['delivery']->label($field) }}</span>
                        @if ($diff['worsened'])<span class="flag">nuevo</span>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Chequeo general de equipo (26 ítems)</h2>
    <table class="data">
        <thead><tr><th style="width:40%">Elemento</th><th style="width:30%">Recepción</th><th style="width:30%">Devolución</th></tr></thead>
        <tbody>
            @foreach ($comparison['equipment'] as $diff)
                <tr>
                    <td>{{ $diff['label'] }}</td>
                    <td>
                        @if ($diff['reception_present'] === null)
                            <span class="muted">No registrado</span>
                        @else
                            <span class="badge {{ $diff['reception_present'] ? 'badge-ok' : 'badge-bad' }}">{{ $diff['reception_present'] ? 'Sí' : 'No' }}</span>
                        @endif
                        @php $img = PdfImageEncoder::dataUri($diff['reception_photo_disk'], $diff['reception_photo_path']); @endphp
                        @if ($img)<br><img src="{{ $img }}" class="thumb">@endif
                    </td>
                    <td>
                        @if ($diff['delivery_present'] === null)
                            <span class="muted">No registrado</span>
                        @else
                            <span class="badge {{ $diff['delivery_present'] ? 'badge-ok' : 'badge-bad' }}">{{ $diff['delivery_present'] ? 'Sí' : 'No' }}</span>
                        @endif
                        @if ($diff['worsened'])<span class="flag">faltante</span>@elseif ($diff['changed'])<span class="flag">cambió</span>@endif
                        @php $img = PdfImageEncoder::dataUri($diff['delivery_photo_disk'], $diff['delivery_photo_path']); @endphp
                        @if ($img)<br><img src="{{ $img }}" class="thumb">@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Estado por componente (12 ítems)</h2>
    <table class="data">
        <thead><tr><th style="width:40%">Componente</th><th style="width:30%">Recepción</th><th style="width:30%">Devolución</th></tr></thead>
        <tbody>
            @foreach ($comparison['condition_items'] as $diff)
                <tr>
                    <td>{{ $diff['label'] }}</td>
                    <td>
                        @if ($diff['reception'] === null)
                            <span class="muted">No registrado</span>
                        @else
                            <span class="badge {{ $diff['reception']->value === 'ok' ? 'badge-ok' : 'badge-bad' }}">{{ $diff['reception']->label($diff['item']) }}</span>
                        @endif
                        @php $img = PdfImageEncoder::dataUri($diff['reception_photo_disk'], $diff['reception_photo_path']); @endphp
                        @if ($img)<br><img src="{{ $img }}" class="thumb">@endif
                    </td>
                    <td>
                        @if ($diff['delivery'] === null)
                            <span class="muted">No registrado</span>
                        @else
                            <span class="badge {{ $diff['delivery']->value === 'ok' ? 'badge-ok' : 'badge-bad' }}">{{ $diff['delivery']->label($diff['item']) }}</span>
                        @endif
                        @if ($diff['worsened'])<span class="flag">nuevo</span>@elseif ($diff['changed'])<span class="flag">cambió</span>@endif
                        @php $img = PdfImageEncoder::dataUri($diff['delivery_photo_disk'], $diff['delivery_photo_path']); @endphp
                        @if ($img)<br><img src="{{ $img }}" class="thumb">@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Documentación</h2>
    <table class="data">
        <thead><tr><th style="width:40%">Documento</th><th style="width:30%">Recepción</th><th style="width:30%">Devolución</th></tr></thead>
        <tbody>
            @foreach ($comparison['documentation'] as $doc)
                <tr>
                    <td>{{ \App\Enums\DocumentType::from($doc['document_type'])->label() }}</td>
                    <td>{{ $doc['reception'] === null ? '—' : ($doc['reception'] ? 'Sí' : 'No') }}</td>
                    <td>
                        {{ $doc['delivery'] === null ? 'No se comprobó' : ($doc['delivery'] ? 'Sí' : 'No') }}
                        @if ($doc['changed'])<span class="flag">cambió</span>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Evidencia fotográfica</h2>
    @forelse ($comparison['photos'] as $group)
        <p class="section-note" style="margin-top:8px;font-weight:bold;color:#334155;">
            {{ \App\Enums\PhotoPosition::from($group['position'])->label() }}
        </p>
        <table class="photo-grid">
            <tr>
                @forelse ($group['reception_photos'] as $photo)
                    <td>
                        @php $img = PdfImageEncoder::fromModel($photo); @endphp
                        @if ($img)<img src="{{ $img }}">@else<div class="no-photo"></div>@endif
                        <p class="photo-caption">Recepción</p>
                    </td>
                @empty
                    <td><div class="no-photo"></div><p class="photo-caption">Recepción — sin foto</p></td>
                @endforelse
                @forelse ($group['delivery_photos'] as $photo)
                    <td>
                        @php $img = PdfImageEncoder::fromModel($photo); @endphp
                        @if ($img)<img src="{{ $img }}">@else<div class="no-photo"></div>@endif
                        <p class="photo-caption">Devolución</p>
                    </td>
                @empty
                    <td><div class="no-photo"></div><p class="photo-caption">Devolución — sin foto</p></td>
                @endforelse
            </tr>
        </table>
    @empty
        <p class="muted">No se cargaron fotografías para ninguna de las dos etapas.</p>
    @endforelse
</body>
</html>
