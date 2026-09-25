@extends('layouts.app')

@section('title', 'Registrar devolución')

@section('content')
    @php
        use App\Enums\DocumentType;
        use App\Enums\PhotoPosition;
        use App\Enums\ConditionStatus;

        // If validation failed on a field that only lives on page 2, open the
        // form back up on page 2 so the person actually sees the error.
        $page2Prefixes = [
            'washed', 'general_condition', 'windows_mirrors_lights', 'tires_condition',
            'dashboard_indicators', 'condition_items', 'condition_photos',
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
            <h1 class="page-title">Devolución del vehículo</h1>
            <p class="page-subtitle">
                Cierre de la recepción de {{ $reception->vehicle->displayName() }},
                recibido el {{ $reception->reception_date->format('d/m/Y') }} por {{ $reception->received_by_name }}.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('deliveries.store', $reception) }}" enctype="multipart/form-data" novalidate
          data-compress-images data-delivery-form x-data="{ step: {{ $initialStep }} }"
          @submit.prevent="submitFormOnce($event.currentTarget)">
        @csrf

        <div class="step-indicator">
            <div class="step-indicator-item" :class="{ 'is-active': step === 1 }">
                <span class="step-indicator-num">1</span>
                Datos, documentos y equipo
            </div>
            <div class="step-indicator-line"></div>
            <div class="step-indicator-item" :class="{ 'is-active': step === 2 }">
                <span class="step-indicator-num">2</span>
                Estado y evidencia
            </div>
        </div>

        <fieldset x-show="step === 1" :disabled="step !== 1" style="display:grid;gap:18px">
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">Personas</div>
                    <div class="card-body" style="display:grid;gap:16px">
                        <div>
                            <label for="returned_by_name" class="form-label" style="margin-top:0">Persona que devuelve el vehículo</label>
                            <input id="returned_by_name" name="returned_by_name" value="{{ old('returned_by_name') }}" required style="width:100%">
                            @error('returned_by_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="keys_received_by_name" class="form-label" style="margin-top:0">Persona que recibe las llaves</label>
                            <input id="keys_received_by_name" name="keys_received_by_name" value="{{ old('keys_received_by_name') }}" required style="width:100%">
                            @error('keys_received_by_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Detalles de la devolución</div>
                    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px">
                        <div>
                            <label for="return_date" class="form-label" style="margin-top:0">Fecha</label>
                            <input type="date" id="return_date" name="return_date" value="{{ old('return_date') }}" required style="width:100%">
                            @error('return_date')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="return_time" class="form-label" style="margin-top:0">Hora</label>
                            <input type="time" id="return_time" name="return_time" value="{{ old('return_time') }}" required style="width:100%">
                            @error('return_time')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div style="grid-column:1/-1">
                            <label for="final_mileage" class="form-label" style="margin-top:0">Kilometraje final</label>
                            <input type="number" min="{{ $reception->initial_mileage }}" id="final_mileage" name="final_mileage"
                                   value="{{ old('final_mileage') }}" required class="font-data" style="width:100%">
                            <p class="field-help">Inicial: {{ number_format($reception->initial_mileage) }}</p>
                            @error('final_mileage')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div style="grid-column:1/-1">
                            <label for="location" class="form-label" style="margin-top:0">Ubicación de devolución</label>
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
                        <x-documentation-checklist :document-types="DocumentType::forDelivery()" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-equipment-checklist />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end">
                <button type="button" @click="if ($el.closest('form').reportValidity()) { step = 2; window.scrollTo({top: 0, behavior: 'smooth'}) }"
                        class="btn btn-primary">
                    Siguiente: Estado y evidencia
                </button>
            </div>
        </fieldset>

        <fieldset x-show="step === 2" :disabled="step !== 2" style="display:grid;gap:18px">
            <div class="card">
                <div class="card-header">Estado a la devolución</div>
                <div class="card-body" style="display:grid;gap:16px">
                    @foreach (ConditionStatus::fieldLabels() as $field => $label)
                        <x-condition-field :field="$field" :label="$label" />
                    @endforeach

                    <fieldset>
                        <p class="section-label">Vehículo lavado</p>
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
                    <x-signature-pad title="Firma de quien recibe el vehículo" signer-field="keys_received_by_name" signer-field-label="Persona que recibe las llaves" />
                </div>
            </div>

            <div style="display:flex;justify-content:space-between">
                <button type="button" @click="step = 1; window.scrollTo({top: 0, behavior: 'smooth'})" class="btn btn-outline-secondary">
                    Atrás
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar devolución
                </button>
            </div>
        </fieldset>
    </form>
@endsection
