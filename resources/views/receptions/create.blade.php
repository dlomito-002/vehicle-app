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

    <div class="page-heading">
        <div>
            <h1 class="page-title">Recepción del vehículo</h1>
            <p class="page-subtitle">Registra el estado del vehículo al recibirlo.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('receptions.store') }}" enctype="multipart/form-data"
          x-data="{ step: {{ $initialStep }} }" novalidate
          @submit.prevent="const form = $event.currentTarget; if (form.reportValidity()) { form.querySelectorAll('fieldset').forEach(fieldset => fieldset.disabled = false); form.submit(); }">
        @csrf

        @if ($errors->any())
            <div class="form-error-summary" role="alert" tabindex="-1">
                <strong>Revisa la informaci&oacute;n antes de continuar</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="step-indicator">
            <div class="step-indicator-item" :class="{ 'is-active': step === 1 }">
                <span class="step-indicator-num">1</span>
                Datos, documentos y equipo
            </div>
            <div class="step-indicator-line"></div>
            <div class="step-indicator-item" :class="{ 'is-active': step === 2 }">
                <span class="step-indicator-num">2</span>
                Inspección y evidencia
            </div>
        </div>

        <fieldset x-show="step === 1" :disabled="step !== 1" style="display:grid;gap:18px">
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">Vehículo y viaje</div>
                    <div class="card-body" style="display:grid;gap:16px">
                        <div>
                            <label for="vehicle_id" class="form-label" style="margin-top:0">Vehículo</label>
                            <select id="vehicle_id" name="vehicle_id" required style="width:100%">
                                <option value="">Selecciona un vehículo</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                        {{ $vehicle->displayName() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="field-help">
                                Solo se muestran vehículos disponibles (no en uso).
                                <a href="{{ route('calendar.index') }}">Ver calendario</a>
                            </p>
                            @error('vehicle_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="received_by_name" class="form-label" style="margin-top:0">Persona que recibe el vehículo</label>
                            <input id="received_by_name" name="received_by_name" value="{{ old('received_by_name') }}" required style="width:100%">
                            @error('received_by_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="trip_reason" class="form-label" style="margin-top:0">Motivo del viaje</label>
                            <input id="trip_reason" name="trip_reason" value="{{ old('trip_reason') }}" required style="width:100%">
                            @error('trip_reason')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Detalles de la recepción</div>
                    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px">
                        <div>
                            <label for="reception_date" class="form-label" style="margin-top:0">Fecha</label>
                            <input type="date" id="reception_date" name="reception_date" value="{{ old('reception_date') }}" required style="width:100%">
                            @error('reception_date')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reception_time" class="form-label" style="margin-top:0">Hora</label>
                            <input type="time" id="reception_time" name="reception_time" value="{{ old('reception_time') }}" required style="width:100%">
                            @error('reception_time')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div style="grid-column:1/-1">
                            <label for="initial_mileage" class="form-label" style="margin-top:0">Kilometraje inicial</label>
                            <input type="number" min="0" id="initial_mileage" name="initial_mileage" value="{{ old('initial_mileage') }}" required class="font-data" style="width:100%">
                            @error('initial_mileage')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div style="grid-column:1/-1">
                            <label for="location" class="form-label" style="margin-top:0">Ubicación de recepción</label>
                            <input id="location" name="location" value="{{ old('location') }}" required
                                   placeholder="Ej. Oficina central, aeropuerto, sitio del cliente..." style="width:100%">
                            @error('location')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">Combustible</div>
                    <div class="card-body" style="display:grid;gap:16px">
                        <x-fuel-level-selector />
                        <x-fuel-type-selector />
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <x-documentation-checklist :document-types="DocumentType::forReception()" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-equipment-checklist />
                </div>
            </div>

            <div class="form-action-bar">
                <button type="button" @click="if ($el.closest('form').reportValidity()) { step = 2; window.scrollTo({top: 0, behavior: 'smooth'}) }"
                        class="btn btn-primary">
                    Siguiente: Inspección y evidencia
                </button>
            </div>
        </fieldset>

        <fieldset x-show="step === 2" :disabled="step !== 2" style="display:grid;gap:18px">
            <div class="card">
                <div class="card-header">Inspección del vehículo</div>
                <div class="card-body" style="display:grid;gap:16px">
                    @foreach (ConditionStatus::fieldLabels() as $field => $label)
                        <x-condition-field :field="$field" :label="$label" />
                    @endforeach

                    <fieldset>
                        <p class="section-label">Vehículo lavado (carwash)</p>
                        <div style="display:flex;gap:10px">
                            <label class="choice-chip is-yes">
                                <input type="radio" name="washed" value="1" @checked(old('washed') === '1') required> Sí
                            </label>
                            <label class="choice-chip is-no">
                                <input type="radio" name="washed" value="0" @checked(old('washed') === '0')> No
                            </label>
                        </div>
                        @error('washed')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div style="padding-top:6px;border-top:1px solid var(--border)">
                        <x-condition-checklist />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-anomaly-field />
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-photo-uploader :positions="PhotoPosition::standardPositions()" />
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-signature-pad />
                </div>
            </div>

            <div class="form-action-bar is-between">
                <button type="button" @click="step = 1; window.scrollTo({top: 0, behavior: 'smooth'})" class="btn btn-outline-secondary">
                    Atrás
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar recepción
                </button>
            </div>
        </fieldset>
    </form>
@endsection
