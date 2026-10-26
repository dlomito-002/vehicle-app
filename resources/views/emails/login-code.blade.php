@php
    $badge = 'Acceso';
    $mailTitle = 'Tu código de acceso';
    $preheader = 'Código temporal para ingresar a Control de Vehículos.';
@endphp
@extends('emails.layout')

@section('content')
    <p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65;">Usa este código temporal para ingresar. No necesitas contraseña.</p>
    <div style="margin:22px 0;padding:20px 16px;border:1px solid #d0d5dd;border-radius:14px;background:#f8fafc;text-align:center;">
        <div style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#667085;margin-bottom:8px;">Tu código</div>
        <div style="font-size:38px;line-height:1;font-weight:850;letter-spacing:9px;color:#173b8f;">{{ $code }}</div>
    </div>
    <p style="margin:0;color:#667085;font-size:13px;line-height:1.55;">Vence en <strong style="color:#101828;">{{ \App\Models\LoginVerificationCode::TTL_MINUTES }} minutos</strong>. Si no solicitaste este acceso, puedes ignorar este mensaje.</p>
@endsection
