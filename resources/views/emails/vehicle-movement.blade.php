@php
    $isReception = $movement instanceof \App\Models\VehicleReception;
    $vehicle = $movement->vehicle;
    $badge = $isReception ? 'Recepción' : 'Devolución';
    $mailTitle = $isReception ? 'Recepción de vehículo' : 'Devolución de vehículo';
    $preheader = $mailTitle.': '.$vehicle->displayName().'.';
    $rows = [
        'Vehículo' => $vehicle->displayName(),
        'Fecha' => ($isReception ? $movement->reception_date : $movement->return_date)->format('d/m/Y'),
        'Hora' => $isReception ? $movement->reception_time : $movement->return_time,
        'Persona' => $isReception ? $movement->received_by_name : $movement->returned_by_name,
        'Ubicación' => $movement->location ?: '—',
    ];
@endphp
@extends('emails.layout')

@section('content')
    <p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65;">Se registró {{ $isReception ? 'la recepción' : 'la devolución' }} de {{ $vehicle->displayName() }}.</p>
    @include('emails.partials.rows', ['rows' => $rows])
@endsection
