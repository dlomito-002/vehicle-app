@extends('layouts.app')

@section('title', 'Calendario de disponibilidad')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Calendario de disponibilidad</h1>
            <p class="page-subtitle">Verifica qué vehículos están libres u ocupados por fecha antes de registrar una nueva recepción.</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}" class="btn btn-outline-secondary btn-sm">
                ← Mes anterior
            </a>
            <span style="font-size:13px;font-weight:700;color:var(--ink);padding:0 4px">
                {{ $month->translatedFormat('F Y') }}
            </span>
            <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="btn btn-outline-secondary btn-sm">
                Mes siguiente →
            </a>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;font-size:12px;color:var(--muted)">
        <span style="display:inline-flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:var(--success-bg);border:1px solid color-mix(in srgb,var(--success) 40%,var(--border) 60%)"></span> Disponible
        </span>
        <span style="display:inline-flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:var(--danger-bg);border:1px solid color-mix(in srgb,var(--danger) 40%,var(--border) 60%)"></span> En uso
        </span>
        <span style="display:inline-flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;box-shadow:0 0 0 2px var(--brand)"></span> Hoy
        </span>
    </div>

    <div class="card" style="overflow-x:auto">
        <table style="min-width:100%;font-size:11.5px;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:1px solid var(--border)">
                    <th style="position:sticky;left:0;background:var(--card);text-align:left;font-weight:700;color:var(--ink);padding:10px 12px;white-space:nowrap">Vehículo</th>
                    @foreach ($days as $day)
                        <th style="padding:10px 4px;text-align:center;font-weight:700;white-space:nowrap;color:{{ $day->isSameDay($today) ? 'var(--brand)' : 'var(--muted)' }}">
                            {{ $day->format('d') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="position:sticky;left:0;background:var(--card);padding:10px 12px;font-weight:700;color:var(--ink);white-space:nowrap">
                            {{ $vehicle->displayName() }}
                            @unless ($vehicle->isAvailable())
                                <x-status-badge status="anomaly" style="margin-left:4px">En uso hoy</x-status-badge>
                            @endunless
                        </td>
                        @foreach ($days as $day)
                            @php
                                $reception = $occupancy[$vehicle->id][$day->toDateString()] ?? null;
                                $isToday = $day->isSameDay($today);
                                $ring = $isToday ? 'box-shadow:0 0 0 2px var(--brand);' : '';
                            @endphp
                            <td style="padding:2px;text-align:center">
                                @if ($reception)
                                    <a href="{{ route('receptions.show', $reception) }}"
                                       title="{{ $reception->received_by_name }} — {{ $reception->trip_reason }}"
                                       style="display:block;width:22px;height:22px;border-radius:4px;margin:0 auto;background:var(--danger-bg);border:1px solid color-mix(in srgb,var(--danger) 40%,var(--border) 60%);{{ $ring }}"></a>
                                @else
                                    <span style="display:block;width:22px;height:22px;border-radius:4px;margin:0 auto;background:var(--success-bg);border:1px solid color-mix(in srgb,var(--success) 25%,var(--border) 75%);{{ $ring }}"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $days->count() + 1 }}" class="data-table-empty">No hay vehículos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="card-header">Vehículos en uso ahora mismo</div>
        <div class="card-body">
            @php $inUse = $vehicles->reject->isAvailable(); @endphp
            @if ($inUse->isEmpty())
                <p style="margin:0;font-size:13.5px;color:var(--muted)">Todos los vehículos están disponibles.</p>
            @else
                <div style="display:grid;gap:2px">
                    @foreach ($inUse as $vehicle)
                        @php $openReception = $vehicle->openReceptions()->latest('reception_date')->first(); @endphp
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
                            <div style="min-width:0">
                                <span style="font-weight:700;color:var(--ink)">{{ $vehicle->displayName() }}</span>
                                <span style="color:var(--muted);font-size:13px">
                                    — con {{ $openReception?->received_by_name }} desde
                                    {{ $openReception?->reception_date->format('d/m/Y') }}
                                </span>
                            </div>
                            @if ($openReception)
                                <a href="{{ route('receptions.show', $openReception) }}" style="color:var(--brand);font-weight:700;font-size:13px;flex:0 0 auto">Ver recepción</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
