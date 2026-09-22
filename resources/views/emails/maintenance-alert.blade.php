@php
    $isOverdue = $schedule->alertStatus() === 'overdue';
    $statusLabel = $isOverdue ? 'Vencido' : 'Próximo (dentro de 200 km)';
    $statusColor = $isOverdue ? '#b42318' : '#9a6700';
    $statusBg = $isOverdue ? '#fef3f2' : '#fffaeb';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Alerta de mantenimiento</title>
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
                                <td><img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel" width="48" height="48" style="display:block;width:48px;height:48px;object-fit:contain;"></td>
                                <td align="right">
                                    <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:{{ $statusBg }};color:{{ $statusColor }};font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 4px;">
                        <h1 style="margin:0 0 6px;font-size:22px;line-height:1.25;color:#15255d;">Alerta de mantenimiento</h1>
                        <p style="margin:0;color:#667085;font-size:13px;">{{ now()->format('d/m/Y H:i') }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 30px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d0d5dd;border-radius:12px;background:#f8fafc;">
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#667085;font-size:12px;width:44%;">Vehículo</td>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#1f2937;font-size:13px;font-weight:700;">{{ $schedule->vehicle->displayName() }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#667085;font-size:12px;">Categoría</td>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#1f2937;font-size:13px;">{{ $schedule->category->label() }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#667085;font-size:12px;">Kilometraje actual</td>
                                <td style="padding:12px 16px;border-bottom:1px solid #e4e7ec;color:#1f2937;font-size:13px;">{{ $schedule->vehicle->currentMileage() ? number_format($schedule->vehicle->currentMileage()) : '—' }} km</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;color:#667085;font-size:12px;">Próximo servicio</td>
                                <td style="padding:12px 16px;color:#1f2937;font-size:13px;font-weight:700;">{{ $schedule->nextDueMileage() ? number_format($schedule->nextDueMileage()) : '—' }} km</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px;background:#f8fafc;border-top:1px solid #e4e7ec;color:#98a2b3;font-size:11px;text-align:center;">
                        Fleet Desk · Corporación Carrousel
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
