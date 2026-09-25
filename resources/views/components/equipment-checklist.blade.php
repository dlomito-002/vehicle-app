@php
    use App\Enums\EquipmentItem;
@endphp

<div>
    <p class="section-label">Chequeo general de equipo</p>
    <p class="section-hint">Marca Sí/No para cada elemento. Puedes adjuntar una fotografía opcional por elemento.</p>

    <div class="checklist-grid">
        @foreach (EquipmentItem::cases() as $item)
            @php $key = $item->value; @endphp
            <div class="checklist-item">
                <span class="checklist-item-label">{{ $item->label() }}</span>
                <div class="checklist-item-controls">
                    <div class="checklist-item-choices">
                        <label class="choice-chip choice-chip-sm is-yes">
                            <input type="radio" name="equipment_checks[{{ $key }}]" value="1"
                                   @checked(old("equipment_checks.$key") === '1') required>
                            Sí
                        </label>
                        <label class="choice-chip choice-chip-sm is-no">
                            <input type="radio" name="equipment_checks[{{ $key }}]" value="0"
                                   @checked(old("equipment_checks.$key") === '0')>
                            No
                        </label>
                    </div>
                    <x-photo-input name="equipment_photos[{{ $key }}]" :label="$item->label()" />
                </div>
                @error("equipment_checks.$key")<p class="field-error">{{ $message }}</p>@enderror
                @error("equipment_photos.$key")<p class="field-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
