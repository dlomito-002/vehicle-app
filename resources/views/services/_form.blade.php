@php
    use App\Enums\ServiceType;
    $service = $service ?? null;
@endphp

<div>
    <label for="vehicle_id" class="block text-sm font-medium text-slate-700 mb-1">Vehículo</label>
    <select id="vehicle_id" name="vehicle_id" required
            class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
        <option value="">Selecciona un vehículo</option>
        @foreach ($vehicles as $vehicle)
            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $service?->vehicle_id) == $vehicle->id)>
                {{ $vehicle->displayName() }}
            </option>
        @endforeach
    </select>
    @error('vehicle_id')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
</div>

<div x-data="{ type: '{{ old('service_type', $service?->service_type?->value) }}' }">
    <label for="service_type" class="block text-sm font-medium text-slate-700 mb-1">Tipo de servicio</label>
    <select id="service_type" name="service_type" x-model="type" required
            class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
        <option value="">Selecciona un tipo</option>
        @foreach (ServiceType::cases() as $type)
            <option value="{{ $type->value }}" @selected(old('service_type', $service?->service_type?->value) === $type->value)>
                {{ $type->label() }}
            </option>
        @endforeach
    </select>
    @error('service_type')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror

    <div class="mt-3" x-show="type === 'other'" x-cloak>
        <label for="other_description" class="block text-sm font-medium text-slate-700 mb-1">Describe el servicio</label>
        <input id="other_description" name="other_description" value="{{ old('other_description', $service?->other_description) }}"
               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
        @error('other_description')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label for="service_date" class="block text-sm font-medium text-slate-700 mb-1">Fecha del servicio</label>
        <input type="date" id="service_date" name="service_date"
               value="{{ old('service_date', $service?->service_date?->toDateString()) }}" required
               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
        @error('service_date')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="mileage_at_service" class="block text-sm font-medium text-slate-700 mb-1">Kilometraje al momento del servicio</label>
        <input type="number" min="0" id="mileage_at_service" name="mileage_at_service"
               value="{{ old('mileage_at_service', $service?->mileage_at_service) }}"
               class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data">
        @error('mileage_at_service')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
    </div>
</div>

<div class="rounded-md border border-dashed border-slate-300 p-4">
    <p class="text-sm font-medium text-slate-700 mb-1">Próximo servicio (para las alertas)</p>
    <p class="text-xs text-slate-500 mb-3">Define al menos una de las dos opciones.</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="next_service_date" class="block text-sm font-medium text-slate-700 mb-1">Próxima fecha</label>
            <input type="date" id="next_service_date" name="next_service_date"
                   value="{{ old('next_service_date', $service?->next_service_date?->toDateString()) }}"
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('next_service_date')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="next_service_mileage" class="block text-sm font-medium text-slate-700 mb-1">Próximo kilometraje</label>
            <input type="number" min="0" id="next_service_mileage" name="next_service_mileage"
                   value="{{ old('next_service_mileage', $service?->next_service_mileage) }}"
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data">
            @error('next_service_mileage')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
    <textarea id="notes" name="notes" rows="3"
              class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">{{ old('notes', $service?->notes) }}</textarea>
    @error('notes')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
</div>
