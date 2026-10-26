@php
    $isOverdue = $schedule->alertStatus() === 'overdue';
    $badge = $isOverdue ? 'Vencido' : 'Próximo';
    $badgeBg = $isOverdue ? '#fef3f2' : '#fffaeb';
    $badgeColor = $isOverdue ? '#b42318' : '#9a6700';
    $mailTitle = 'Alerta de mantenimiento';
    $preheader = $schedule->vehicle->displayName().': mantenimiento '.mb_strtolower($schedule->category->label()).' '.($isOverdue ? 'vencido' : 'próximo').'.';
    $current = $schedule->vehicle->currentMileage();
    $next = $schedule->nextDueMileage();
@endphp
@extends('emails.layout')

@section('content')
    <p style="margin:0 0 18px;color:#475467;font-size:15px;line-height:1.65;">
        @if ($isOverdue)
            El mantenimiento de este vehículo ya está vencido.
        @else
            El mantenimiento de este vehículo está a {{ \App\Models\VehicleMaintenanceSchedule::ALERT_WINDOW_KM }} km o menos.
        @endif
    </p>
    @include('emails.partials.rows', ['rows' => [
        'Vehículo' => $schedule->vehicle->displayName(),
        'Categoría' => $schedule->category->label(),
        'Kilometraje actual' => ($current ? number_format($current) : '—').' km',
        'Próximo servicio' => ($next ? number_format($next) : '—').' km',
        ($isOverdue ? 'Km excedidos' : 'Km restantes') => number_format(abs($schedule->kmRemaining() ?? 0)).' km',
    ]])
@endsection
