@extends('layouts.app')

@section('title', 'Nueva recepción')

@section('content')
    @php
        use App\Enums\DocumentType;
        use App\Enums\PhotoPosition;
        use App\Enums\ConditionStatus;
    @endphp

    <h1 class="text-xl font-semibold text-slate-900 mb-1">Recepción del vehículo</h1>
    <p class="text-sm text-slate-500 mb-6">Registra el estado del vehículo al recibirlo.</p>

    <form method="POST" action="{{ route('receptions.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-3xl">
        @csrf

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-magenta">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Persona y viaje</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="received_by_name" class="block text-sm font-medium text-slate-700 mb-1">Persona que recibe el vehículo</label>
                    <input id="received_by_name" name="received_by_name" value="{{ old('received_by_name') }}" required
                           class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                    @error('received_by_name')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="vehicle_id" class="block text-sm font-medium text-slate-700 mb-1">Vehículo</label>
                    <select id="vehicle_id" name="vehicle_id" required
                            class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        <option value="">Selecciona un vehículo</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                {{ $vehicle->displayName() }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">
                        Solo se muestran vehículos disponibles (no en uso).
                        <a href="{{ route('calendar.index') }}" class="text-brand-cyan hover:underline">Ver calendario</a>
                    </p>
                    @error('vehicle_id')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="trip_reason" class="block text-sm font-medium text-slate-700 mb-1">Motivo del viaje</label>
                    <input id="trip_reason" name="trip_reason" value="{{ old('trip_reason') }}" required
                           class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                    @error('trip_reason')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Detalles de la recepción</h2>
            <div class="grid sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="reception_date" class="block text-sm font-medium text-slate-700 mb-1">Fecha de recepción</label>
                    <input type="date" id="reception_date" name="reception_date" value="{{ old('reception_date') }}" required
                           class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                    @error('reception_date')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reception_time" class="block text-sm font-medium text-slate-700 mb-1">Hora de recepción</label>
                    <input type="time" id="reception_time" name="reception_time" value="{{ old('reception_time') }}" required
                           class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                    @error('reception_time')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="initial_mileage" class="block text-sm font-medium text-slate-700 mb-1">Kilometraje inicial</label>
                    <input type="number" min="0" id="initial_mileage" name="initial_mileage" value="{{ old('initial_mileage') }}" required
                           class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data">
                    @error('initial_mileage')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </div>
            </div>
            <x-fuel-level-selector />
            <x-fuel-type-selector />
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan">
            <x-equipment-checklist />
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-olive space-y-5">
            <h2 class="text-sm font-semibold text-slate-900">Inspección del vehículo</h2>
            @foreach (ConditionStatus::fieldLabels() as $field => $label)
                <x-condition-field :field="$field" :label="$label" />
            @endforeach

            <div class="pt-2 border-t border-slate-100">
                <x-condition-checklist />
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-orange">
            <x-anomaly-field />
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-amber">
            <x-photo-uploader :positions="PhotoPosition::standardPositions()" />
        </section>

        <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-magenta">
            <x-documentation-checklist :document-types="DocumentType::forReception()" />
        </section>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
                Guardar recepción
            </button>
        </div>
    </form>
@endsection
