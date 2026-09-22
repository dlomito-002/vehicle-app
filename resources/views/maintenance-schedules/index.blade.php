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
                        {{ $alert->vehicle->displayName() }} — {{ $alert->category->label() }}
                        ({{ $statusLabels[$alert->alertStatus()] }})
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
                        <th>Próximo</th>
                        <th>Estado</th>
                        @if (auth()->user()->isAdmin())
                            <th>Registrar servicio</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td data-label="Vehículo">{{ $schedule->vehicle->displayName() }}</td>
                            <td data-label="Categoría">{{ $schedule->category->label() }}</td>
                            <td data-label="Intervalo" class="font-data">
                                {{ $schedule->interval_km ? number_format($schedule->interval_km).' km' : 'No definido' }}
                            </td>
                            <td data-label="Último servicio" class="font-data">
                                {{ $schedule->lastServiceMileage() ? number_format($schedule->lastServiceMileage()).' km' : '—' }}
                            </td>
                            <td data-label="Próximo" class="font-data">
                                {{ $schedule->nextDueMileage() ? number_format($schedule->nextDueMileage()).' km' : '—' }}
                            </td>
                            <td data-label="Estado">
                                <x-status-badge :status="$statusBadge($schedule->alertStatus())">
                                    {{ $statusLabels[$schedule->alertStatus()] }}
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
                        <tr><td colspan="7" class="data-table-empty">No hay vehículos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
