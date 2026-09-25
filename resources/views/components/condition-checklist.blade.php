@php
    use App\Enums\ConditionComponent;
    use App\Enums\ConditionStatus;
@endphp

<div>
    <p class="section-label">Estado general del vehículo (detalle por componente)</p>
    <p class="section-hint">Marca el estado de cada componente. Puedes adjuntar una fotografía opcional por elemento.</p>

    <div class="checklist-grid">
        @foreach (ConditionComponent::cases() as $item)
            @php $key = $item->value; $selected = old("condition_items.$key"); @endphp
            <div class="checklist-item">
                <span class="checklist-item-label">{{ $item->label() }}</span>
                <div class="checklist-item-controls">
                    <div class="checklist-item-choices">
                        @foreach (ConditionStatus::cases() as $case)
                            <label class="choice-chip choice-chip-sm {{ $case->value === 'ok' ? 'is-yes' : 'is-no' }}">
                                <input type="radio" name="condition_items[{{ $key }}]" value="{{ $case->value }}"
                                       @checked($selected === $case->value) required>
                                {{ $case->label($key) }}
                            </label>
                        @endforeach
                    </div>
                    <x-photo-input name="condition_photos[{{ $key }}]" :label="$item->label()" />
                </div>
                @error("condition_items.$key")<p class="field-error">{{ $message }}</p>@enderror
                @error("condition_photos.$key")<p class="field-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
