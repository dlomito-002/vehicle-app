@extends('layouts.app')

@section('title', 'Servicios')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Servicios y mantenimiento</h1>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('services.create') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
                Registrar servicio
            </a>
        @endif
    </div>

    @if ($alerts->isNotEmpty())
        <div class="mb-6 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3">
            <p class="text-sm font-medium text-slate-800 mb-1">{{ $alerts->count() }} servicio(s) requieren atención</p>
            <ul class="text-sm text-slate-700 list-disc list-inside space-y-0.5">
                @foreach ($alerts as $alert)
                    <li>
                        {{ $alert->vehicle->displayName() }} — {{ $alert->typeLabel() }}
                        <span class="text-xs {{ $alert->alert_status === 'overdue' ? 'text-brand-orange' : 'text-amber-700' }}">
                            ({{ $alert->alert_status === 'overdue' ? 'vencido' : 'próximo' }})
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
                    <th class="px-4 py-2 font-medium">Servicio</th>
                    <th class="px-4 py-2 font-medium">Fecha</th>
                    <th class="px-4 py-2 font-medium">Kilometraje</th>
                    <th class="px-4 py-2 font-medium">Próximo</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                    @if (auth()->user()->isAdmin())
                        <th class="px-4 py-2 font-medium"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <tr>
                        <td data-label="Vehículo" class="px-4 py-3">{{ $service->vehicle->displayName() }}</td>
                        <td data-label="Servicio" class="px-4 py-3">{{ $service->typeLabel() }}</td>
                        <td data-label="Fecha" class="px-4 py-3 font-data">{{ $service->service_date->format('d/m/Y') }}</td>
                        <td data-label="Kilometraje" class="px-4 py-3 font-data">{{ $service->mileage_at_service ? number_format($service->mileage_at_service) : '—' }}</td>
                        <td data-label="Próximo" class="px-4 py-3 text-xs text-slate-500">
                            @if ($service->next_service_date)
                                {{ $service->next_service_date->format('d/m/Y') }}
                            @endif
                            @if ($service->next_service_mileage)
                                <br>{{ number_format($service->next_service_mileage) }} km
                            @endif
                        </td>
                        <td data-label="Estado" class="px-4 py-3">
                            <x-status-badge :status="$service->alert_status === 'ok' ? 'ok' : ($service->alert_status === 'due_soon' ? 'pending' : 'anomaly')">
                                {{ ['overdue' => 'Vencido', 'due_soon' => 'Próximo', 'ok' => 'Al día'][$service->alert_status] }}
                            </x-status-badge>
                        </td>
                        @if (auth()->user()->isAdmin())
                            <td class="px-4 py-3">
                                <a href="{{ route('services.edit', $service) }}" class="text-brand-cyan hover:underline text-xs">Editar</a>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Todavía no hay servicios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
