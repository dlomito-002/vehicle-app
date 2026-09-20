@extends('layouts.app')

@section('title', 'Nueva recepción')

@section('content')
    @php
        use App\Enums\DocumentType;
        use App\Enums\PhotoPosition;
        use App\Enums\ConditionStatus;

        // If validation failed on a field that only lives on page 2, open the
        // form back up on page 2 so the person actually sees the error.
        $page2Prefixes = [
            'washed', 'general_condition', 'windows_mirrors_lights', 'tires_condition',
            'dashboard_indicators', 'cleanliness', 'condition_items', 'condition_photos',
            'has_anomaly', 'anomaly_description', 'anomaly_photos',
            'position_photos', 'photos', 'signature_data', 'signature_file',
        ];
        $initialStep = 1;
        foreach ($errors->keys() as $errorKey) {
            foreach ($page2Prefixes as $prefix) {
                if ($errorKey === $prefix || str_starts_with($errorKey, $prefix.'.') || str_starts_with($errorKey, $prefix.'*')) {
                    $initialStep = 2;
                    break 2;
                }
            }
        }
    @endphp

    <h1 class="text-xl font-semibold text-slate-900 mb-1">Recepción del vehículo</h1>
    <p class="text-sm text-slate-500 mb-6">Registra el estado del vehículo al recibirlo.</p>

        <form method="POST" action="{{ route('receptions.store') }}" enctype="multipart/form-data"
                    x-data="{ step: {{ $initialStep }} }" novalidate
                    @submit.prevent="const form = $event.currentTarget; if (form.reportValidity()) { form.querySelectorAll('fieldset').forEach(fieldset => fieldset.disabled = false); form.submit(); }"
                    class="max-w-3xl">
        @csrf

        <div class="flex items-center gap-2 mb-6">
            <div class="flex items-center gap-2 text-xs font-medium" :class="step === 1 ? 'text-brand-magenta' : 'text-slate-400'">
                <span class="flex items-center justify-center w-5 h-5 rounded-full border"
                      :class="step === 1 ? 'border-brand-magenta bg-brand-magenta/10' : 'border-slate-300'">1</span>
                Datos, documentos y equipo
            </div>
            <div class="flex-1 h-px bg-slate-200"></div>
            <div class="flex items-center gap-2 text-xs font-medium" :class="step === 2 ? 'text-brand-magenta' : 'text-slate-400'">
                <span class="flex items-center justify-center w-5 h-5 rounded-full border"
                      :class="step === 2 ? 'border-brand-magenta bg-brand-magenta/10' : 'border-slate-300'">2</span>
                Inspección y evidencia
            </div>
        </div>

        <fieldset x-show="step === 1" :disabled="step !== 1" class="space-y-6">
            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-magenta">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Vehículo y viaje</h2>
                <div class="grid sm:grid-cols-2 gap-4">
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
                    <div>
                        <label for="received_by_name" class="block text-sm font-medium text-slate-700 mb-1">Persona que recibe el vehículo</label>
                        <input id="received_by_name" name="received_by_name" value="{{ old('received_by_name') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('received_by_name')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
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
                <div class="grid sm:grid-cols-3 gap-4">
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
                    <div class="sm:col-span-3">
                        <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Ubicación de recepción</label>
                        <input id="location" name="location" value="{{ old('location') }}" required
                               placeholder="Ej. Oficina central, aeropuerto, sitio del cliente..."
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('location')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan space-y-4">
                <h2 class="text-sm font-semibold text-slate-900">Combustible</h2>
                <x-fuel-level-selector />
                <x-fuel-type-selector />
            </section>

            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-magenta">
                <x-documentation-checklist :document-types="DocumentType::forReception()" />
            </section>

            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan">
                <x-equipment-checklist />
            </section>

            <div class="flex justify-end">
                <button type="button" @click="if ($el.closest('form').reportValidity()) { step = 2; window.scrollTo({top: 0, behavior: 'smooth'}) }"
                        class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-cyan hover:bg-brand-cyan/90">
                    Siguiente: Inspección y evidencia
                </button>
            </div>
        </fieldset>

        <fieldset x-show="step === 2" :disabled="step !== 2" class="space-y-6">
            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-olive space-y-5">
                <h2 class="text-sm font-semibold text-slate-900">Inspección del vehículo</h2>

                @foreach (ConditionStatus::fieldLabels() as $field => $label)
                    <x-condition-field :field="$field" :label="$label" />
                @endforeach

                <fieldset>
                    <legend class="text-sm font-medium text-slate-700 mb-2">Vehículo lavado (carwash)</legend>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer border-slate-200 hover:bg-slate-50">
                            <input type="radio" name="washed" value="1" @checked(old('washed') === '1') required class="text-brand-olive focus:ring-brand-olive"> Sí
                        </label>
                        <label class="flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer border-slate-200 hover:bg-slate-50">
                            <input type="radio" name="washed" value="0" @checked(old('washed') === '0') class="text-brand-olive focus:ring-brand-olive"> No
                        </label>
                    </div>
                    @error('washed')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                </fieldset>

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
                <x-signature-pad />
            </section>

            <div class="flex justify-between">
                <button type="button" @click="step = 1; window.scrollTo({top: 0, behavior: 'smooth'})"
                        class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-slate-700 border border-slate-300 hover:bg-slate-50">
                    Atrás
                </button>
                <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
                    Guardar recepción
                </button>
            </div>
        </fieldset>
    </form>
@endsection
