<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de problema</title>
</head>
<body style="font-family: sans-serif; color: #1e293b;">
    <h2 style="margin-bottom: 0;">Fleet Desk — Reporte de problema</h2>
    <p style="color: #64748b; margin-top: 4px;">Enviado el {{ now()->format('d/m/Y H:i') }}</p>

    <table cellpadding="4" style="margin: 16px 0;">
        <tr>
            <td style="color: #64748b;">Reportado por</td>
            <td><strong>{{ $reporter->name }}</strong> ({{ $reporter->email }})</td>
        </tr>
    </table>

    <p style="color: #64748b; margin-bottom: 4px;">Descripción del problema:</p>
    <p style="white-space: pre-line; border-left: 3px solid #0DB3D9; padding-left: 12px;">{{ $reportMessage }}</p>
</body>
</html>
