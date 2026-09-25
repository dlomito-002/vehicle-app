@props(['documentTypes'])

<fieldset>
    <p class="section-label">Documentación del vehículo</p>
    <div style="display:grid;gap:8px">
        @foreach ($documentTypes as $docType)
            @php $key = $docType->value; @endphp
            <div class="checklist-item-controls" style="padding:10px 14px;border:1px solid var(--border);border-radius:10px;background:var(--card)">
                <span class="checklist-item-label">{{ $docType->label() }}</span>
                <div class="checklist-item-choices">
                    <label class="choice-chip choice-chip-sm is-yes">
                        <input type="radio" name="documentation[{{ $key }}]" value="1"
                               @checked(old("documentation.$key") === '1') required>
                        Sí
                    </label>
                    <label class="choice-chip choice-chip-sm is-no">
                        <input type="radio" name="documentation[{{ $key }}]" value="0"
                               @checked(old("documentation.$key") === '0')>
                        No
                    </label>
                </div>
            </div>
            @error("documentation.$key")
                <p class="field-error">{{ $message }}</p>
            @enderror
        @endforeach
    </div>
</fieldset>
