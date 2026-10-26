{{--
    Plantilla de correo del Ecosistema Carrousel (docs/ECOSISTEMA.md › Correos): igual que Helpdesk.
    Cada correo define antes de @extends: $badge (etiqueta), $mailTitle, $preheader y, si aplica,
    $badgeBg / $badgeColor (p. ej. alerta vencida). El contenido va en @section('content').
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $mailTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#eef3f9;font-family:Arial,Helvetica,sans-serif;color:#101828;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ \Illuminate\Support\Str::limit(trim($preheader ?? ''), 120) }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#eef3f9;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #d7deea;border-radius:18px;overflow:hidden;">
                <tr><td style="height:4px;background:#2748a0;background:linear-gradient(90deg,#2748a0 0%,#773db8 35%,#eb2f7d 67%,#f6a800 100%);"></td></tr>
                <tr>
                    <td style="padding:24px 30px 10px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td><img src="{{ $message->embed(public_path('images/email-logo.png')) }}" alt="Carrousel" width="108" style="display:block;max-width:108px;height:auto;"></td>
                                <td align="right"><span style="display:inline-block;padding:6px 9px;border-radius:999px;background:{{ $badgeBg ?? '#eef4ff' }};color:{{ $badgeColor ?? '#214697' }};font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;">{{ $badge }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 30px 30px;">
                        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;color:#14213d;">{{ $mailTitle }}</h1>
                        @yield('content')
                        <div style="margin-top:26px;padding-top:16px;border-top:1px solid #e4e7ec;color:#667085;font-size:11px;line-height:1.55;">Mensaje automático de Control de Vehículos.</div>
                    </td>
                </tr>
            </table>
            <div style="max-width:620px;padding:13px 8px 0;color:#98a2b3;font-size:10px;line-height:1.5;text-align:center;">Corporación Carrousel</div>
        </td>
    </tr>
</table>
</body>
</html>
