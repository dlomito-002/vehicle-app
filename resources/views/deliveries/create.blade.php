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
            'dashboard_indicators', 'cleanliness', 'condition_items', 'condition_photos',
            'has_anomaly', 'anomaly_description', 'anomaly_photos',
            'position_photos', 'photos', 'documentation', 'signature_data',
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

    <h1 class="text-xl font-semibold text-slate-900 mb-1">Devolución del vehículo</h1>
    <p class="text-sm text-slate-500 mb-6">
        Cierre de la recepción de {{ $reception->vehicle->displayName() }},
        recibido el {{ $reception->reception_date->format('d/m/Y') }} por {{ $reception->received_by_name }}.
    </p>

        <form method="POST" action="{{ route('deliveries.store', $reception) }}" enctype="multipart/form-data" novalidate
                    data-compress-images data-delivery-form x-data="{ step: {{ $initialStep }} }"
                    @submit.prevent="const form = $event.currentTarget; if (form.reportValidity()) { form.querySelectorAll('fieldset').forEach(fieldset => fieldset.disabled = false); form.submit(); }"
                    class="max-w-3xl">
        @csrf

        <div class="flex items-center gap-2 mb-6">
            <div class="flex items-center gap-2 text-xs font-medium" :class="step === 1 ? 'text-brand-magenta' : 'text-slate-400'">
                <span class="flex items-center justify-center w-5 h-5 rounded-full border"
                      :class="step === 1 ? 'border-brand-magenta bg-brand-magenta/10' : 'border-slate-300'">1</span>
                Datos y equipo
            </div>
            <div class="flex-1 h-px bg-slate-200"></div>
            <div class="flex items-center gap-2 text-xs font-medium" :class="step === 2 ? 'text-brand-magenta' : 'text-slate-400'">
                <span class="flex items-center justify-center w-5 h-5 rounded-full border"
                      :class="step === 2 ? 'border-brand-magenta bg-brand-magenta/10' : 'border-slate-300'">2</span>
                Estado y evidencia
            </div>
        </div>

        <fieldset x-show="step === 1" :disabled="step !== 1" class="space-y-6">
            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-magenta">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Personas</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="returned_by_name" class="block text-sm font-medium text-slate-700 mb-1">Persona que devuelve el vehículo</label>
                        <input id="returned_by_name" name="returned_by_name" value="{{ old('returned_by_name') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('returned_by_name')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="keys_received_by_name" class="block text-sm font-medium text-slate-700 mb-1">Persona que recibe las llaves</label>
                        <input id="keys_received_by_name" name="keys_received_by_name" value="{{ old('keys_received_by_name') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('keys_received_by_name')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Detalles de la devolución</h2>
                <div class="grid sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="return_date" class="block text-sm font-medium text-slate-700 mb-1">Fecha de devolución</label>
                        <input type="date" id="return_date" name="return_date" value="{{ old('return_date') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('return_date')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="return_time" class="block text-sm font-medium text-slate-700 mb-1">Hora de devolución</label>
                        <input type="time" id="return_time" name="return_time" value="{{ old('return_time') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                        @error('return_time')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="final_mileage" class="block text-sm font-medium text-slate-700 mb-1">Kilometraje final</label>
                        <input type="number" min="{{ $reception->initial_mileage }}" id="final_mileage" name="final_mileage"
                               value="{{ old('final_mileage') }}" required
                               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data">
                        <p class="mt-1 text-xs text-slate-400">Inicial: {{ number_format($reception->initial_mileage) }}</p>
                        @error('final_mileage')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-fuel-level-selector />
                <x-fuel-type-selector />
            </section>

            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-cyan">
                <x-equipment-checklist />
            </section>

            <div class="flex justify-end">
                <button type="button" @click="if ($el.closest('form').reportValidity()) { step = 2; window.scrollTo({top: 0, behavior: 'smooth'}) }"
                        class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-cyan hover:bg-brand-cyan/90">
                    Siguiente: Estado y evidencia
                </button>
            </div>
        </fieldset>

        <fieldset x-show="step === 2" :disabled="step !== 2" class="space-y-6">
            <section class="bg-white border border-slate-200 rounded-lg p-5 border-l-4 border-l-brand-olive space-y-5">
                <h2 class="text-sm font-semibold text-slate-900">Estado a la devolución</h2>

                <fieldset>
                    <legend class="text-sm font-medium text-slate-700 mb-2">Vehículo lavado</legend>
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
                <x-documentation-checklist :document-types="DocumentType::forDelivery()" />
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
                    Guardar devolución
                </button>
            </div>
        </fieldset>
    </form>
@endsection
