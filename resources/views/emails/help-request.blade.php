@php
    $badge = 'Ayuda';
    $mailTitle = 'Reporte de problema';
    $preheader = $reporter->name.' reportó un problema en Control de Vehículos.';
@endphp
@extends('emails.layout')

@section('content')
    <p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65;">{{ $reporter->name }} reportó un problema el {{ now()->format('d/m/Y H:i') }}.</p>
    @include('emails.partials.rows', ['rows' => ['Reportado por' => $reporter->name, 'Correo' => $reporter->email]])
    <div style="margin:20px 0 8px;color:#667085;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;">Descripción del problema</div>
    <div style="white-space:pre-line;border-left:3px solid #2748a0;padding:2px 0 2px 14px;color:#101828;font-size:14px;line-height:1.6;">{{ $reportMessage }}</div>
@endsection
