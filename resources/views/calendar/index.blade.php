@extends('layouts.app')

@section('title', 'Calendario de disponibilidad')

@section('content')
    <div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 mb-1">Calendario de disponibilidad</h1>
            <p class="text-sm text-slate-500">
                Verifica qué vehículos están libres u ocupados por fecha antes de registrar una nueva recepción.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}"
               class="px-3 py-1.5 rounded-md text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-100">
                ← Mes anterior
            </a>
            <span class="px-3 py-1.5 text-sm font-medium text-slate-900">
                {{ $month->translatedFormat('F Y') }}
            </span>
            <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}"
               class="px-3 py-1.5 rounded-md text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-100">
                Mes siguiente →
            </a>
        </div>
    </div>

    <div class="flex items-center gap-4 mb-4 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-brand-olive/20 border border-brand-olive/40 inline-block"></span> Disponible</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-brand-orange/20 border border-brand-orange/40 inline-block"></span> En uso</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm ring-2 ring-brand-cyan inline-block"></span> Hoy</span>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="sticky left-0 bg-white text-left font-medium text-slate-700 px-3 py-2 whitespace-nowrap">Vehículo</th>
                    @foreach ($days as $day)
                        <th class="px-1 py-2 text-center font-medium text-slate-500 whitespace-nowrap
                                   {{ $day->isSameDay($today) ? 'text-brand-cyan' : '' }}">
                            {{ $day->format('d') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="sticky left-0 bg-white px-3 py-2 font-medium text-slate-800 whitespace-nowrap">
                            {{ $vehicle->displayName() }}
                            @unless ($vehicle->isAvailable())
                                <x-status-badge status="anomaly" class="ml-1">En uso hoy</x-status-badge>
                            @endunless
                        </td>
                        @foreach ($days as $day)
                            @php
                                $reception = $occupancy[$vehicle->id][$day->toDateString()] ?? null;
                                $isToday = $day->isSameDay($today);
                            @endphp
                            <td class="p-0.5 text-center">
                                @if ($reception)
                                    <a href="{{ route('receptions.show', $reception) }}"
                                       title="{{ $reception->received_by_name }} — {{ $reception->trip_reason }}"
                                       class="block w-6 h-6 rounded-sm bg-brand-orange/20 border border-brand-orange/40 hover:bg-brand-orange/30 mx-auto
                                              {{ $isToday ? 'ring-2 ring-brand-cyan' : '' }}"></a>
                                @else
                                    <span class="block w-6 h-6 rounded-sm bg-brand-olive/10 border border-brand-olive/20 mx-auto
                                                 {{ $isToday ? 'ring-2 ring-brand-cyan' : '' }}"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $days->count() + 1 }}" class="px-3 py-6 text-center text-slate-500">
                            No hay vehículos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Vehículos en uso ahora mismo</h2>
        @php $inUse = $vehicles->reject->isAvailable(); @endphp
        @if ($inUse->isEmpty())
            <p class="text-sm text-slate-500">Todos los vehículos están disponibles.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($inUse as $vehicle)
                    @php $openReception = $vehicle->openReceptions()->latest('reception_date')->first(); @endphp
                    <li class="py-2 flex items-center justify-between text-sm">
                        <div>
                            <span class="font-medium text-slate-800">{{ $vehicle->displayName() }}</span>
                            <span class="text-slate-500">
                                — con {{ $openReception?->received_by_name }} desde
                                {{ $openReception?->reception_date->format('d/m/Y') }}
                            </span>
                        </div>
                        @if ($openReception)
                            <a href="{{ route('receptions.show', $openReception) }}" class="text-brand-cyan hover:underline">Ver recepción</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
