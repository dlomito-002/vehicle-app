@php
    $badge = 'Prueba';
    $mailTitle = 'El correo funciona';
    $preheader = 'Prueba del correo de Control de Vehículos.';
@endphp
@extends('emails.layout')

@section('content')
    <p style="margin:0;color:#475467;font-size:15px;line-height:1.65;">Correo de prueba de Control de Vehículos, enviado desde Administración › Correo y notificaciones el {{ now()->format('d/m/Y H:i') }} por {{ $sentBy }}. Si lo recibes, el envío está configurado correctamente.</p>
@endsection
