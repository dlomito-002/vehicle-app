@php
    use App\Enums\ConditionComponent;
    use App\Enums\ConditionStatus;
@endphp

<div>
    <p class="section-label">Otros componentes del vehículo</p>
    <p class="section-hint">Las demás partes se revisan en las preguntas de arriba.</p>

    <div class="checklist-grid">
        @foreach (ConditionComponent::formItems() as $item)
            @php $key = $item->value; $selected = old("condition_items.$key"); @endphp
            <div class="checklist-item">
                <span class="checklist-item-label">{{ $item->label() }}</span>
                <div class="checklist-item-choices">
                    @foreach (ConditionStatus::cases() as $case)
                        <label class="choice-chip choice-chip-sm {{ $case->value === 'ok' ? 'is-yes' : 'is-no' }}">
                            <input type="radio" name="condition_items[{{ $key }}]" value="{{ $case->value }}"
                                   @checked($selected === $case->value) required>
                            {{ $case->label($key) }}
                        </label>
                    @endforeach
                </div>
                @error("condition_items.$key")<p class="field-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
