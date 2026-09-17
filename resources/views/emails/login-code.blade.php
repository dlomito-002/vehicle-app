<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Código de verificación</title>
</head>
<body style="font-family: sans-serif; color: #1e293b;">
    <h2 style="margin-bottom: 0;">Fleet Desk</h2>
    <p style="color: #64748b; margin-top: 4px;">Tu código de verificación para iniciar sesión es:</p>

    <p style="font-size: 32px; font-weight: 700; letter-spacing: 6px; margin: 16px 0;">{{ $code }}</p>

    <p style="color: #64748b;">
        Este código vence en {{ \App\Models\LoginVerificationCode::TTL_MINUTES }} minutos y solo puede usarse una vez.
        Si no solicitaste este código, puedes ignorar este correo.
    </p>
</body>
</html>
