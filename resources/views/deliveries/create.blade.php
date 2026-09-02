@extends('layouts.app')

@section('title', 'Registrar devolución')

@section('content')
    @php
        use App\Enums\DocumentType;
        use App\Enums\PhotoPosition;
        use App\Enums\ConditionStatus;
    @endphp

    <h1 class="text-xl font-semibold text-slate-900 mb-1">Devolución del vehículo</h1>
    <p class="text-sm text-slate-500 mb-6">
        Cierre de la recepción de {{ $reception->vehicle->displayName() }},
        recibido el {{ $reception->reception_date->format('d/m/Y') }} por {{ $reception->received_by_name }}.
    </p>

    <form method="POST" action="{{ route('deliveries.store', $reception) }}" enctype="multipart/form-data" class="space-y-6 max-w-3xl">
        @csrf

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
        </section>

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

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
                Guardar devolución
            </button>
        </div>
    </form>
@endsection
