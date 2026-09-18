@extends('layouts.app')

@section('title', 'Reporte comparativo')

@section('content')
    @php use App\Enums\ConditionStatus; @endphp

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ $reception->vehicle->displayName() }}</h1>
            <p class="text-sm text-slate-500">
                Recepción {{ $reception->reception_date->format('d/m/Y') }} → Devolución {{ $delivery->return_date->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('comparisons.pdf', $reception) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90 shrink-0">
            Descargar PDF
        </a>
    </div>

    @if ($comparison['new_anomaly'])
        <div class="mb-6 rounded-md border-l-4 border-brand-orange bg-brand-orange/10 px-4 py-3 text-sm text-slate-800">
            Se reportó una nueva anomalía en la devolución que no estaba presente en la recepción.
        </div>
    @endif

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <p class="text-sm text-slate-500 mb-1">Kilometraje</p>
            <p class="text-2xl font-data text-slate-900">{{ number_format($comparison['mileage']['delta']) }} km</p>
            <p class="text-xs text-slate-400 mt-1">
                {{ number_format($comparison['mileage']['initial']) }} → {{ number_format($comparison['mileage']['final']) }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <p class="text-sm text-slate-500 mb-1">Nivel de combustible</p>
            <p class="text-sm text-slate-900">
                {{ $comparison['fuel_level']['reception']->label() }} → {{ $comparison['fuel_level']['delivery']->label() }}
            </p>
            @if ($comparison['fuel_level']['decreased'])
                <x-status-badge status="anomaly" class="mt-2">Menor que en la recepción</x-status-badge>
            @elseif ($comparison['fuel_level']['changed'])
                <x-status-badge status="pending" class="mt-2">Cambió</x-status-badge>
            @else
                <x-status-badge status="ok" class="mt-2">Sin cambios</x-status-badge>
            @endif
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <p class="text-sm text-slate-500 mb-1">Anomalía reportada</p>
            <p class="text-sm text-slate-900">
                Recepción: {{ $reception->has_anomaly ? 'Sí' : 'No' }} · Devolución: {{ $delivery->has_anomaly ? 'Sí' : 'No' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <p class="text-sm text-slate-500 mb-1">Lavado (carwash)</p>
            <p class="text-sm text-slate-900">
                Recepción: {{ $reception->washed ? 'Sí' : 'No' }} · Devolución: {{ $delivery->washed ? 'Sí' : 'No' }}
            </p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Comparación del estado</h2>
        <table class="responsive-table w-full text-sm">
            <thead class="text-slate-500 text-left">
                <tr>
                    <th class="py-2 font-medium">Elemento</th>
                    <th class="py-2 font-medium">Recepción</th>
                    <th class="py-2 font-medium">Devolución</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($comparison['conditions'] as $field => $diff)
                    <tr>
                        <td data-label="Elemento" class="py-2.5 text-slate-700">{{ ConditionStatus::fieldLabels()[$field] }}</td>
                        <td data-label="Recepción" class="py-2.5">
                            <x-status-badge :status="$diff['reception']->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $diff['reception']->label($field) }}
                            </x-status-badge>
                        </td>
                        <td data-label="Devolución" class="py-2.5">
                            <span class="inline-flex flex-wrap items-center justify-end gap-1">
                                <x-status-badge :status="$diff['delivery']->value === 'ok' ? 'ok' : 'anomaly'">
                                    {{ $diff['delivery']->label($field) }}
                                </x-status-badge>
                                @if ($diff['worsened'])
                                    <span class="text-xs text-brand-orange">nuevo</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Comparación de equipo (26 ítems)</h2>
        <p class="text-xs text-slate-500 mb-3">Chequeo general de equipo, elemento por elemento.</p>
        <table class="responsive-table w-full text-sm">
            <thead class="text-slate-500 text-left">
                <tr>
                    <th class="py-2 font-medium">Elemento</th>
                    <th class="py-2 font-medium">Recepción</th>
                    <th class="py-2 font-medium">Devolución</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($comparison['equipment'] as $diff)
                    <tr>
                        <td data-label="Elemento" class="py-2.5 text-slate-700">{{ $diff['label'] }}</td>
                        <td data-label="Recepción" class="py-2.5">
                            <div class="flex flex-col items-end gap-1">
                                @if ($diff['reception_present'] === null)
                                    <span class="text-xs text-slate-400">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['reception_present'] ? 'ok' : 'anomaly'">
                                        {{ $diff['reception_present'] ? 'Sí' : 'No' }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['reception_photo'])
                                    <a href="{{ $diff['reception_photo'] }}" target="_blank">
                                        <img src="{{ $diff['reception_photo'] }}" class="w-16 h-16 object-cover rounded border border-slate-200">
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td data-label="Devolución" class="py-2.5">
                            <div class="flex flex-col items-end gap-1">
                                <span class="inline-flex flex-wrap items-center justify-end gap-1">
                                    @if ($diff['delivery_present'] === null)
                                        <span class="text-xs text-slate-400">No registrado</span>
                                    @else
                                        <x-status-badge :status="$diff['delivery_present'] ? 'ok' : 'anomaly'">
                                            {{ $diff['delivery_present'] ? 'Sí' : 'No' }}
                                        </x-status-badge>
                                    @endif
                                    @if ($diff['worsened'])
                                        <span class="text-xs text-brand-orange">faltante</span>
                                    @elseif ($diff['changed'])
                                        <span class="text-xs text-brand-amber">cambió</span>
                                    @endif
                                </span>
                                @if ($diff['delivery_photo'])
                                    <a href="{{ $diff['delivery_photo'] }}" target="_blank">
                                        <img src="{{ $diff['delivery_photo'] }}" class="w-16 h-16 object-cover rounded border border-slate-200">
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Comparación de estado por componente (12 ítems)</h2>
        <p class="text-xs text-slate-500 mb-3">Detalle del estado general del vehículo, componente por componente.</p>
        <table class="responsive-table w-full text-sm">
            <thead class="text-slate-500 text-left">
                <tr>
                    <th class="py-2 font-medium">Componente</th>
                    <th class="py-2 font-medium">Recepción</th>
                    <th class="py-2 font-medium">Devolución</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($comparison['condition_items'] as $diff)
                    <tr>
                        <td data-label="Componente" class="py-2.5 text-slate-700">{{ $diff['label'] }}</td>
                        <td data-label="Recepción" class="py-2.5">
                            <div class="flex flex-col items-end gap-1">
                                @if ($diff['reception'] === null)
                                    <span class="text-xs text-slate-400">No registrado</span>
                                @else
                                    <x-status-badge :status="$diff['reception']->value === 'ok' ? 'ok' : 'anomaly'">
                                        {{ $diff['reception']->label($diff['item']) }}
                                    </x-status-badge>
                                @endif
                                @if ($diff['reception_photo'])
                                    <a href="{{ $diff['reception_photo'] }}" target="_blank">
                                        <img src="{{ $diff['reception_photo'] }}" class="w-16 h-16 object-cover rounded border border-slate-200">
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td data-label="Devolución" class="py-2.5">
                            <div class="flex flex-col items-end gap-1">
                                <span class="inline-flex flex-wrap items-center justify-end gap-1">
                                    @if ($diff['delivery'] === null)
                                        <span class="text-xs text-slate-400">No registrado</span>
                                    @else
                                        <x-status-badge :status="$diff['delivery']->value === 'ok' ? 'ok' : 'anomaly'">
                                            {{ $diff['delivery']->label($diff['item']) }}
                                        </x-status-badge>
                                    @endif
                                    @if ($diff['worsened'])
                                        <span class="text-xs text-brand-orange">nuevo</span>
                                    @elseif ($diff['changed'])
                                        <span class="text-xs text-brand-amber">cambió</span>
                                    @endif
                                </span>
                                @if ($diff['delivery_photo'])
                                    <a href="{{ $diff['delivery_photo'] }}" target="_blank">
                                        <img src="{{ $diff['delivery_photo'] }}" class="w-16 h-16 object-cover rounded border border-slate-200">
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Comparación de documentación</h2>
        <table class="responsive-table w-full text-sm">
            <thead class="text-slate-500 text-left">
                <tr>
                    <th class="py-2 font-medium">Documento</th>
                    <th class="py-2 font-medium">Recepción</th>
                    <th class="py-2 font-medium">Devolución</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($comparison['documentation'] as $doc)
                    <tr>
                        <td data-label="Documento" class="py-2.5 text-slate-700">{{ \App\Enums\DocumentType::from($doc['document_type'])->label() }}</td>
                        <td data-label="Recepción" class="py-2.5">{{ $doc['reception'] === null ? '—' : ($doc['reception'] ? 'Sí' : 'No') }}</td>
                        <td data-label="Devolución" class="py-2.5">
                            <span class="inline-flex flex-wrap items-center justify-end gap-1 text-right">
                                <span>{{ $doc['delivery'] === null ? 'No se comprobó en la devolución' : ($doc['delivery'] ? 'Sí' : 'No') }}</span>
                                @if ($doc['changed'])
                                    <span class="text-xs text-brand-orange">cambió</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Evidencia fotográfica</h2>
        <div class="space-y-6">
            @forelse ($comparison['photos'] as $group)
                <div>
                    <p class="text-sm font-medium text-slate-700 mb-2 capitalize">
                        {{ \App\Enums\PhotoPosition::from($group['position'])->label() }}
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Recepción</p>
                            @forelse ($group['reception_photos'] as $photo)
                                <a href="{{ $photo->url() }}" target="_blank">
                                    <img src="{{ $photo->url() }}" class="w-full h-32 object-cover rounded-md border border-slate-200">
                                </a>
                            @empty
                                <div class="w-full h-32 rounded-md border border-dashed border-slate-200 flex items-center justify-center text-xs text-slate-400">
                                    Sin fotografía
                                </div>
                            @endforelse
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Devolución</p>
                            @forelse ($group['delivery_photos'] as $photo)
                                <a href="{{ $photo->url() }}" target="_blank">
                                    <img src="{{ $photo->url() }}" class="w-full h-32 object-cover rounded-md border border-slate-200">
                                </a>
                            @empty
                                <div class="w-full h-32 rounded-md border border-dashed border-slate-200 flex items-center justify-center text-xs text-slate-400">
                                    Sin fotografía
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No se cargaron fotografías para ninguna de las dos etapas.</p>
            @endforelse
        </div>
    </div>
@endsection
