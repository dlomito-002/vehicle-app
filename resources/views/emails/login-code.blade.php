<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Código de verificación</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #d0d5dd;border-radius:18px;overflow:hidden;">
                <tr>
                    <td style="height:5px;background:linear-gradient(90deg,#213a8f 0%,#6b46c1 28%,#e62e7c 52%,#f28b24 76%,#f4c430 100%);line-height:5px;font-size:5px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding:26px 32px 0;">
                        <img src="{{ asset('images/logo.png') }}" alt="Corporación Carrousel" width="52" height="52" style="display:block;width:52px;height:52px;object-fit:contain;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px 8px;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#e62e7c;margin-bottom:6px;">Acceso seguro</div>
                        <h1 style="margin:0 0 8px;font-size:22px;line-height:1.25;color:#15255d;">Fleet Desk</h1>
                        <p style="margin:0 0 20px;color:#667085;font-size:14px;line-height:1.6;">Usa este código temporal para iniciar sesión. No necesitas contraseña.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d0d5dd;border-radius:14px;background:#f8fafc;">
                            <tr>
                                <td align="center" style="padding:20px 16px;">
                                    <div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#667085;margin-bottom:8px;">Tu código</div>
                                    <div style="font-size:36px;line-height:1;font-weight:800;letter-spacing:9px;color:#213a8f;">{{ $code }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px 30px;">
                        <p style="margin:0;color:#667085;font-size:13px;line-height:1.55;">
                            Vence en <strong style="color:#1f2937;">{{ \App\Models\LoginVerificationCode::TTL_MINUTES }} minutos</strong> y solo puede usarse una vez.
                            Si no solicitaste este código, puedes ignorar este correo.
                        </p>
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
