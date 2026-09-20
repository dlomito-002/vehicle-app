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

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Mantenimiento programado</h1>
        <p class="text-sm text-slate-500 mt-1">
            Intervalos fijos por vehículo: básico (1,000 km) y mayor (4,000 km).
            Independiente del registro de servicios manual.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-md border-l-4 border-brand-olive bg-brand-olive/10 px-4 py-3 text-sm text-slate-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($alerts->isNotEmpty())
        <div class="mb-6 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3">
            <p class="text-sm font-medium text-slate-800 mb-1">{{ $alerts->count() }} mantenimiento(s) requieren atención</p>
            <ul class="text-sm text-slate-700 list-disc list-inside space-y-0.5">
                @foreach ($alerts as $alert)
                    <li>
                        {{ $alert->vehicle->displayName() }} — {{ $alert->category->label() }}
                        <span class="text-xs {{ $alert->alertStatus() === 'overdue' ? 'text-brand-orange' : 'text-amber-700' }}">
                            ({{ $statusLabels[$alert->alertStatus()] }})
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Vehículo</th>
                    <th class="px-4 py-2 font-medium">Categoría</th>
                    <th class="px-4 py-2 font-medium">Intervalo</th>
                    <th class="px-4 py-2 font-medium">Último servicio</th>
                    <th class="px-4 py-2 font-medium">Próximo</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                    @if (auth()->user()->isAdmin())
                        <th class="px-4 py-2 font-medium">Registrar servicio</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td data-label="Vehículo" class="px-4 py-3">{{ $schedule->vehicle->displayName() }}</td>
                        <td data-label="Categoría" class="px-4 py-3">{{ $schedule->category->label() }}</td>
                        <td data-label="Intervalo" class="px-4 py-3 font-data">
                            {{ $schedule->interval_km ? number_format($schedule->interval_km).' km' : 'No definido' }}
                        </td>
                        <td data-label="Último servicio" class="px-4 py-3 font-data">
                            {{ $schedule->lastServiceMileage() ? number_format($schedule->lastServiceMileage()).' km' : '—' }}
                        </td>
                        <td data-label="Próximo" class="px-4 py-3 font-data text-xs">
                            {{ $schedule->nextDueMileage() ? number_format($schedule->nextDueMileage()).' km' : '—' }}
                        </td>
                        <td data-label="Estado" class="px-4 py-3">
                            <x-status-badge :status="$statusBadge($schedule->alertStatus())">
                                {{ $statusLabels[$schedule->alertStatus()] }}
                            </x-status-badge>
                        </td>
                        @if (auth()->user()->isAdmin())
                            <td data-label="Registrar servicio" class="px-4 py-3">
                                <form method="POST" action="{{ route('maintenance-schedules.complete', [$schedule->vehicle, $schedule->category->value]) }}"
                                      class="flex flex-wrap items-center gap-1.5">
                                    @csrf
                                    <input type="number" name="mileage" min="0" placeholder="Km" required
                                           class="w-24 rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-xs">
                                    <input type="date" name="service_date" required value="{{ now()->toDateString() }}"
                                           class="w-36 rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-xs">
                                    <button type="submit"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-md text-xs font-medium text-white bg-brand-cyan hover:bg-brand-cyan/90">
                                        Registrar
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay vehículos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
