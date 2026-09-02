@extends('layouts.app')

@section('title', 'Reporte comparativo')

@section('content')
    @php use App\Enums\ConditionStatus; @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900">{{ $reception->vehicle->displayName() }}</h1>
        <p class="text-sm text-slate-500">
            Recepción {{ $reception->reception_date->format('d/m/Y') }} → Devolución {{ $delivery->return_date->format('d/m/Y') }}
        </p>
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
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Comparación del estado</h2>
        <table class="w-full text-sm">
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
                        <td class="py-2.5 text-slate-700">{{ ConditionStatus::fieldLabels()[$field] }}</td>
                        <td class="py-2.5">
                            <x-status-badge :status="$diff['reception']->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $diff['reception']->label($field) }}
                            </x-status-badge>
                        </td>
                        <td class="py-2.5">
                            <x-status-badge :status="$diff['delivery']->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $diff['delivery']->label($field) }}
                            </x-status-badge>
                            @if ($diff['worsened'])
                                <span class="text-xs text-brand-orange ml-1">nuevo</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Comparación de documentación</h2>
        <table class="w-full text-sm">
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
                        <td class="py-2.5 text-slate-700">{{ \App\Enums\DocumentType::from($doc['document_type'])->label() }}</td>
                        <td class="py-2.5">{{ $doc['reception'] === null ? '—' : ($doc['reception'] ? 'Sí' : 'No') }}</td>
                        <td class="py-2.5">
                            {{ $doc['delivery'] === null ? 'No se comprobó en la devolución' : ($doc['delivery'] ? 'Sí' : 'No') }}
                            @if ($doc['changed'])
                                <span class="text-xs text-brand-orange ml-1">cambió</span>
                            @endif
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
