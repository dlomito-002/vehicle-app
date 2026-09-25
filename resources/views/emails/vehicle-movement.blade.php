@php
    $isReception = $movement instanceof \App\Models\VehicleReception;
    $vehicle = $movement->vehicle;
    $title = $isReception ? 'Vehículo recibido' : 'Vehículo devuelto';
    $when = $isReception
        ? $movement->reception_date->format('d/m/Y').' '.$movement->reception_time
        : $movement->return_date->format('d/m/Y').' '.$movement->return_time;
    $rows = [
        'Vehículo' => $vehicle->displayName(),
        'Marca' => $vehicle->make,
        'Modelo' => $vehicle->model ?: '—',
        'Placa' => $vehicle->license_plate,
        'Fecha y hora' => $when,
    ];
    if ($isReception) {
        $rows['Recibido por'] = $movement->received_by_name;
        $rows['Motivo del viaje'] = $movement->trip_reason;
        $rows['Kilometraje inicial'] = number_format($movement->initial_mileage).' km';
    } else {
        $rows['Devuelto por'] = $movement->returned_by_name;
        $rows['Llaves recibidas por'] = $movement->keys_received_by_name;
        $rows['Kilometraje final'] = number_format($movement->final_mileage).' km';
    }
    $rows['Ubicación'] = $movement->location ?: '—';
    $rows['Nivel de combustible'] = $movement->fuel_level->label();
    $rows['Lavado (carwash)'] = $movement->washed ? 'Sí' : 'No';
    foreach (\App\Enums\ConditionStatus::fieldLabels() as $field => $label) {
        $rows[$label] = $movement->{$field}->label($field);
    }
    $rows['Anomalías'] = $movement->has_anomaly ? ($movement->anomaly_description ?: 'Sí') : 'Ninguna';
    foreach ($movement->documentation as $doc) {
        $rows['Documento: '.$doc->document_type->label()] = $doc->is_valid ? 'Sí' : 'No';
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #d0d5dd;border-radius:18px;overflow:hidden;">
                <tr>
                    <td style="height:5px;background:linear-gradient(90deg,#213a8f 0%,#6b46c1 28%,#e62e7c 52%,#f28b24 76%,#f4c430 100%);line-height:5px;font-size:5px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding:26px 32px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td><img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Corporación Carrousel" width="48" height="48" style="display:block;width:48px;height:48px;object-fit:contain;"></td>
                                <td align="right">
                                    <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:#eef4ff;color:#213a8f;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;">{{ $title }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 4px;">
                        <h1 style="margin:0 0 6px;font-size:22px;line-height:1.25;color:#15255d;">{{ $title }}</h1>
                        <p style="margin:0;color:#667085;font-size:13px;">{{ $when }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 30px;">
                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d0d5dd;border-radius:12px;background:#f8fafc;">
                            @foreach ($rows as $label => $value)
                                <tr>
                                    <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #e4e7ec;' }}color:#667085;font-size:12px;width:44%;">{{ $label }}</td>
                                    <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #e4e7ec;' }}color:#1f2937;font-size:13px;">{{ $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px;background:#f8fafc;border-top:1px solid #e4e7ec;color:#98a2b3;font-size:11px;text-align:center;">
                        Control de Vehículos · Corporación Carrousel
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
