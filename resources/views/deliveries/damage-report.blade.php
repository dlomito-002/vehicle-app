@extends('layouts.app')

@section('title', 'Informe de daños')

@section('content')
    @php
        use App\Enums\ConditionStatus;

        $conditionIssues = collect(ConditionStatus::fieldLabels())
            ->filter(fn ($label, $field) => $reception->{$field}->value !== 'ok');

        $equipmentMissing = $reception->equipmentChecks->filter(fn ($check) => ! $check->is_present);
        $conditionComponentIssues = $reception->conditionItems->filter(fn ($item) => $item->status->value !== 'ok');
        $anomalyPhotos = $reception->photos->where('position', 'anomaly');
    @endphp

    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Informe de daños antes de la entrega</h1>
            <p class="text-sm text-slate-500">
                {{ $reception->vehicle->displayName() }} — recibido el {{ $reception->reception_date->format('d/m/Y') }}
                por {{ $reception->received_by_name }}
            </p>
        </div>
        <a href="{{ route('deliveries.damage-report.pdf', $reception) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-cyan hover:bg-brand-cyan/90 shrink-0">
            Descargar PDF
        </a>
    </div>

    <p class="text-sm text-slate-600 mb-6">
        Revisa los daños y faltantes registrados en la recepción antes de continuar con la devolución.
        Esto ayuda a distinguir lo que ya existía de lo que ocurra durante el uso del vehículo.
    </p>

    @if (! $reception->has_anomaly && $conditionIssues->isEmpty() && $equipmentMissing->isEmpty() && $conditionComponentIssues->isEmpty())
        <div class="mb-6 rounded-md border-l-4 border-brand-olive bg-brand-olive/10 px-4 py-3 text-sm text-slate-800">
            El vehículo se recibió sin daños ni faltantes registrados.
        </div>
    @endif

    @if ($reception->has_anomaly)
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6 border-l-4 border-l-brand-orange">
            <h2 class="text-sm font-semibold text-slate-900 mb-2">Anomalía reportada en la recepción</h2>
            <p class="text-sm text-slate-700 mb-3">{{ $reception->anomaly_description }}</p>
            @if ($anomalyPhotos->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($anomalyPhotos as $photo)
                        <a href="{{ $photo->url() }}" target="_blank">
                            <img src="{{ $photo->url() }}" class="w-full h-24 object-cover rounded-md border border-slate-200">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($conditionIssues->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Inspección general con incidencias</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($conditionIssues as $field => $label)
                    <x-status-badge status="anomaly">{{ $label }}: {{ $reception->{$field}->label($field) }}</x-status-badge>
                @endforeach
            </div>
        </div>
    @endif

    @if ($conditionComponentIssues->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Componentes con incidencia</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($conditionComponentIssues as $item)
                    <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-md border border-slate-200 text-sm">
                        <span class="text-slate-700">{{ $item->item->label() }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-status-badge status="anomaly">{{ $item->status->label($item->item->value) }}</x-status-badge>
                            @if ($item->hasPhoto())
                                <a href="{{ $item->photoUrl() }}" target="_blank" class="text-brand-cyan hover:underline text-xs">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($equipmentMissing->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Equipo faltante</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach ($equipmentMissing as $check)
                    <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-md border border-slate-200 text-sm">
                        <span class="text-slate-700">{{ $check->item->label() }}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-status-badge status="anomaly">Faltante</x-status-badge>
                            @if ($check->hasPhoto())
                                <a href="{{ $check->photoUrl() }}" target="_blank" class="text-brand-cyan hover:underline text-xs">Foto</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex justify-end">
        <a href="{{ route('deliveries.create', $reception) }}"
           class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Continuar con la devolución
        </a>
    </div>
@endsection
