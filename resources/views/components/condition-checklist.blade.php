@php
    use App\Enums\ConditionComponent;
    use App\Enums\ConditionStatus;
@endphp

<div>
    <p class="text-sm font-medium text-slate-700 mb-1">Estado general del vehículo (detalle por componente)</p>
    <p class="text-xs text-slate-500 mb-3">
        Marca el estado de cada componente. Puedes adjuntar una fotografía opcional por elemento.
    </p>

    <div class="grid sm:grid-cols-2 gap-3">
        @foreach (ConditionComponent::cases() as $item)
            @php $key = $item->value; $selected = old("condition_items.$key"); @endphp
            <div class="rounded-md border border-slate-200 p-3">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <span class="text-sm text-slate-700">{{ $item->label() }}</span>
                    <div class="flex gap-3 shrink-0">
                        @foreach (ConditionStatus::cases() as $case)
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="condition_items[{{ $key }}]" value="{{ $case->value }}"
                                       @checked($selected === $case->value) required
                                       class="text-brand-cyan focus:ring-brand-cyan">
                                {{ $case->label($key) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <input type="file" name="condition_photos[{{ $key }}]" accept="image/png,image/jpeg,image/webp"
                       class="text-xs w-full file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-brand-cyan/10 file:text-brand-cyan">
                @error("condition_items.$key")<p class="mt-1 text-xs text-brand-orange">{{ $message }}</p>@enderror
                @error("condition_photos.$key")<p class="mt-1 text-xs text-brand-orange">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
