<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Alerta de mantenimiento</title>
</head>
<body style="font-family: sans-serif; color: #1e293b;">
    <h2 style="margin-bottom: 0;">Fleet Desk — Alerta de mantenimiento</h2>
    <p style="color: #64748b; margin-top: 4px;">{{ now()->format('d/m/Y H:i') }}</p>

    <table cellpadding="4" style="margin: 16px 0;">
        <tr>
            <td style="color: #64748b;">Vehículo</td>
            <td><strong>{{ $schedule->vehicle->displayName() }}</strong></td>
        </tr>
        <tr>
            <td style="color: #64748b;">Categoría</td>
            <td>{{ $schedule->category->label() }}</td>
        </tr>
        <tr>
            <td style="color: #64748b;">Kilometraje actual</td>
            <td>{{ $schedule->vehicle->currentMileage() ? number_format($schedule->vehicle->currentMileage()) : '—' }} km</td>
        </tr>
        <tr>
            <td style="color: #64748b;">Próximo servicio</td>
            <td>{{ $schedule->nextDueMileage() ? number_format($schedule->nextDueMileage()) : '—' }} km</td>
        </tr>
        <tr>
            <td style="color: #64748b;">Estado</td>
            <td>{{ $schedule->alertStatus() === 'overdue' ? 'Vencido' : 'Próximo (dentro de 200 km)' }}</td>
        </tr>
    </table>
</body>
</html>
