@extends('layouts.app')

@section('title', 'Detalle de devolución')

@section('content')
    @php use App\Enums\ConditionStatus; @endphp

    @php
        $equipmentPresent = $delivery->equipmentChecks->where('is_present', true)->count();
        $equipmentTotal = $delivery->equipmentChecks->count();
        $conditionIssues = $delivery->conditionItems->filter(fn ($item) => $item->status->value !== 'ok')->count();
    @endphp

    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ $delivery->vehicle->displayName() }}</h1>
            <p class="text-sm text-slate-500">
                Devuelto el {{ $delivery->return_date->format('d/m/Y') }} a las {{ $delivery->return_time }}
                por {{ $delivery->returned_by_name }}
            </p>
        </div>
        <a href="{{ route('comparisons.show', $delivery->reception) }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-cyan hover:bg-brand-cyan/90">
            Ver comparación
        </a>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Detalles de la devolución</h2>
            <dl class="text-sm space-y-1.5">
                <div class="flex justify-between"><dt class="text-slate-500">Llaves recibidas por</dt><dd>{{ $delivery->keys_received_by_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Kilometraje final</dt><dd class="font-data">{{ number_format($delivery->final_mileage) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Distancia recorrida</dt><dd class="font-data">{{ number_format($delivery->mileageDelta()) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Nivel de combustible</dt><dd>{{ $delivery->fuel_level->label() }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tipo de combustible</dt><dd>{{ $delivery->fuel_type?->label() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Lavado</dt><dd>{{ $delivery->washed ? 'Sí' : 'No' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Inspección</h2>
            <dl class="text-sm space-y-1.5">
                @foreach (ConditionStatus::fieldLabels() as $field => $label)
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd>
                            <x-status-badge :status="$delivery->{$field}->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $delivery->{$field}->label($field) }}
                            </x-status-badge>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    @if ($delivery->has_anomaly)
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6 border-l-4 border-l-brand-orange">
            <h2 class="text-sm font-semibold text-slate-900 mb-2">Anomalía reportada</h2>
            <p class="text-sm text-slate-700">{{ $delivery->anomaly_description }}</p>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Documentación</h2>
        <div class="flex flex-wrap gap-2">
            @foreach ($delivery->documentation as $doc)
                <x-status-badge :status="$doc->is_valid ? 'ok' : 'anomaly'">
                    {{ $doc->document_type->label() }}: {{ $doc->is_valid ? 'Sí' : 'No' }}
                </x-status-badge>
            @endforeach
        </div>
    </div>

    @if ($equipmentTotal > 0)
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">Chequeo general de equipo</h2>
                <span class="text-xs text-slate-500">{{ $equipmentPresent }} / {{ $equipmentTotal }} presentes</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($delivery->equipmentChecks as $check)
                    <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-md border border-slate-200 text-sm">
                        <span class="text-slate-700">{{ $check->item->label() }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-status-badge :status="$check->is_present ? 'ok' : 'anomaly'">
                                {{ $check->is_present ? 'Sí' : 'No' }}
                            </x-status-badge>
                            @if ($check->hasPhoto())
                                <a href="{{ $check->photoUrl() }}" target="_blank" class="text-brand-cyan hover:underline text-xs">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($delivery->conditionItems->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">Estado general del vehículo (detalle)</h2>
                @if ($conditionIssues > 0)
                    <x-status-badge status="anomaly">{{ $conditionIssues }} con incidencia</x-status-badge>
                @else
                    <x-status-badge status="ok">Sin incidencias</x-status-badge>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($delivery->conditionItems as $item)
                    <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-md border border-slate-200 text-sm">
                        <span class="text-slate-700">{{ $item->item->label() }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-status-badge :status="$item->status->value === 'ok' ? 'ok' : 'anomaly'">
                                {{ $item->status->label($item->item->value) }}
                            </x-status-badge>
                            @if ($item->hasPhoto())
                                <a href="{{ $item->photoUrl() }}" target="_blank" class="text-brand-cyan hover:underline text-xs">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Firma</h2>
        @if ($delivery->signaturePhoto())
            <img src="{{ $delivery->signaturePhoto()->url() }}" alt="Firma de {{ $delivery->returned_by_name }}"
                 class="h-28 rounded-md border border-slate-200 bg-white">
        @else
            <p class="text-sm text-slate-500">No se capturó firma.</p>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Fotografías</h2>
        @php $galleryPhotos = $delivery->photos->where('position', '!==', \App\Enums\PhotoPosition::Signature); @endphp
        @if ($galleryPhotos->isEmpty())
            <p class="text-sm text-slate-500">No se cargaron fotografías.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($galleryPhotos as $photo)
                    <a href="{{ $photo->url() }}" target="_blank" class="block group">
                        <img src="{{ $photo->url() }}" alt="{{ $photo->position->label() }}"
                             class="w-full h-28 object-cover rounded-md border border-slate-200 group-hover:opacity-90">
                        <p class="text-xs text-slate-500 mt-1">{{ $photo->position->label() }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
