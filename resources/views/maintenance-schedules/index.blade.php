@extends('layouts.app')

@section('title', 'Mantenimiento programado')

@section('content')
    @php
        $statusLabels = [
            'overdue' => 'Vencido',
            'due_soon' => 'Próximo',
            'ok' => 'Al día',
            'unknown' => 'Sin kilometraje',
            'unconfigured' => 'Sin configurar',
        ];
        $statusBadge = fn (string $status) => match ($status) {
            'overdue' => 'anomaly',
            'due_soon' => 'pending',
            'ok' => 'ok',
            default => 'default',
        };
    @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">Mantenimiento programado</h1>
            <p class="page-subtitle">
                Intervalos fijos por vehículo: básico (1,000 km) y mayor (4,000 km).
                Independiente del registro de servicios manual.
                Se marca como próximo cuando faltan {{ number_format(\App\Models\VehicleMaintenanceSchedule::ALERT_WINDOW_KM) }} km o menos.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($alerts->isNotEmpty())
        <div class="alert alert-danger">
            <p>{{ $alerts->count() }} mantenimiento(s) requieren atención</p>
            <ul style="margin:0;padding-left:18px">
                @foreach ($alerts as $alert)
                    <li>
                        @php $alertRemaining = $alert->kmRemaining(); @endphp
                        {{ $alert->vehicle->displayName() }} — {{ $alert->category->label() }}
                        ({{ $statusLabels[$alert->alertStatus()] }}@if ($alertRemaining !== null),
                            {{ $alertRemaining > 0 ? 'faltan '.number_format($alertRemaining).' km' : ($alertRemaining < 0 ? 'excedido por '.number_format(abs($alertRemaining)).' km' : 'kilometraje alcanzado') }}@endif)
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Categoría</th>
                        <th>Intervalo</th>
                        <th>Último servicio</th>
                        <th>Kilometraje actual</th>
                        <th>Próximo</th>
                        <th>Avance</th>
                        <th>Estado</th>
                        @if (auth()->user()->isAdmin())
                            <th>Registrar servicio</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            // Display-only figures, derived from the model's own
                            // rules (nextDueMileage/kmRemaining/alertStatus).
                            $currentMileage = $schedule->vehicle->currentMileage();
                            $lastMileage = $schedule->lastServiceMileage();
                            $nextMileage = $schedule->nextDueMileage();
                            $remainingKm = $schedule->kmRemaining($currentMileage);
                            $rowStatus = $schedule->alertStatus($currentMileage);
                            $intervalKm = $schedule->interval_km;

                            $progress = null;
                            $warningMark = null;
                            if ($remainingKm !== null && $intervalKm > 0) {
                                $progress = max(0, min(100, ($currentMileage - $lastMileage) / $intervalKm * 100));
                                $warningMark = max(0, min(100, ($intervalKm - \App\Models\VehicleMaintenanceSchedule::ALERT_WINDOW_KM) / $intervalKm * 100));
                            }
                            $barColor = match ($rowStatus) {
                                'overdue' => 'var(--danger)',
                                'due_soon' => 'var(--warning)',
                                default => 'var(--success)',
                            };
                        @endphp
                        <tr>
                            <td data-label="Vehículo">{{ $schedule->vehicle->displayName() }}</td>
                            <td data-label="Categoría">{{ $schedule->category->label() }}</td>
                            <td data-label="Intervalo" class="font-data">
                                {{ $schedule->interval_km ? number_format($schedule->interval_km).' km' : 'No definido' }}
                            </td>
                            <td data-label="Último servicio" class="font-data">
                                {{ $schedule->lastServiceMileage() ? number_format($schedule->lastServiceMileage()).' km' : '—' }}
                            </td>
                            <td data-label="Kilometraje actual" class="font-data">
                                @if ($currentMileage !== null)
                                    <strong>{{ number_format($currentMileage) }} km</strong>
                                @else
                                    <span style="color:var(--muted)">Sin registro</span>
                                @endif
                            </td>
                            <td data-label="Próximo" class="font-data">
                                {{ $nextMileage !== null ? number_format($nextMileage).' km' : '—' }}
                            </td>
                            <td data-label="Avance" style="min-width:190px">
                                @if ($progress !== null)
                                    <div role="progressbar" aria-valuemin="{{ $lastMileage }}" aria-valuemax="{{ $nextMileage }}" aria-valuenow="{{ $currentMileage }}"
                                         aria-label="Avance hacia el próximo servicio"
                                         style="position:relative;height:8px;border-radius:999px;background:var(--border);overflow:hidden">
                                        <div style="position:absolute;inset:0 auto 0 0;width:{{ round($progress, 1) }}%;background:{{ $barColor }};border-radius:999px"></div>
                                        <div title="Inicio de la ventana de aviso" style="position:absolute;top:0;bottom:0;left:{{ round($warningMark, 1) }}%;width:2px;background:var(--card)"></div>
                                    </div>
                                    <div class="font-data" style="display:flex;justify-content:space-between;gap:8px;margin-top:4px;font-size:11.5px;color:var(--muted)">
                                        <span>{{ number_format($lastMileage) }}</span>
                                        <span>{{ number_format($nextMileage) }}</span>
                                    </div>
                                    <div style="margin-top:2px;font-size:12.5px;font-weight:700;color:{{ $barColor }}">
                                        @if ($remainingKm > 0)
                                            Faltan {{ number_format($remainingKm) }} km
                                        @elseif ($remainingKm === 0)
                                            Kilometraje de servicio alcanzado
                                        @else
                                            Excedido por {{ number_format(abs($remainingKm)) }} km
                                        @endif
                                    </div>
                                @elseif ($rowStatus === 'unknown')
                                    <span style="font-size:12.5px;color:var(--muted)">Sin kilometraje actual para calcular.</span>
                                @else
                                    <span style="font-size:12.5px;color:var(--muted)">
                                        {{ $intervalKm ? 'Registra un servicio para iniciar el conteo.' : 'Intervalo no definido.' }}
                                    </span>
                                @endif
                            </td>
                            <td data-label="Estado">
                                <x-status-badge :status="$statusBadge($rowStatus)">
                                    {{ $statusLabels[$rowStatus] }}
                                </x-status-badge>
                            </td>
                            @if (auth()->user()->isAdmin())
                                <td data-label="Registrar servicio">
                                    <form method="POST" action="{{ route('maintenance-schedules.complete', [$schedule->vehicle, $schedule->category->value]) }}"
                                          style="display:flex;flex-wrap:wrap;align-items:center;gap:6px">
                                        @csrf
                                        <input type="number" name="mileage" min="0" placeholder="Km" required class="w-24" style="font-size:12.5px;min-height:34px;padding:6px 8px">
                                        <input type="date" name="service_date" required value="{{ now()->toDateString() }}" class="w-36" style="font-size:12.5px;min-height:34px;padding:6px 8px">
                                        <button type="submit" class="btn btn-primary btn-sm">Registrar</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9" class="data-table-empty">No hay vehículos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
