@php $hasAnomalyOld = old('has_anomaly'); @endphp

<div x-data="{ hasAnomaly: {{ $hasAnomalyOld === '1' ? 'true' : 'false' }}, fileCount: 0 }" class="field-item">
    <fieldset>
        <p class="section-label">Daño o anomalía adicional</p>
        <div style="display:flex;gap:10px">
            <label class="choice-chip is-yes" :class="{ 'is-selected': !hasAnomaly }">
                <input type="radio" name="has_anomaly" value="0" @click="hasAnomaly = false" @checked($hasAnomalyOld !== '1') required>
                No
            </label>
            <label class="choice-chip is-no" :class="{ 'is-selected': hasAnomaly }">
                <input type="radio" name="has_anomaly" value="1" @click="hasAnomaly = true" @checked($hasAnomalyOld === '1')>
                Sí
            </label>
        </div>
    </fieldset>

    <div x-show="hasAnomaly" x-cloak style="margin-top:16px;display:grid;gap:16px">
        <div>
            <label for="anomaly_description" class="form-label" style="margin-top:0">Descripción del daño, falla o anomalía</label>
            <textarea id="anomaly_description" name="anomaly_description" rows="3" style="width:100%"
                      x-bind:required="hasAnomaly">{{ old('anomaly_description') }}</textarea>
            @error('anomaly_description')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label" style="margin-top:0">Fotografía(s) de la anomalía</label>
            <label class="file-btn">
                <span x-text="fileCount ? fileCount + ' archivo(s) seleccionado(s)' : 'Adjuntar fotografía(s)'">Adjuntar fotografía(s)</span>
                <input type="file" name="anomaly_photos[]" accept="image/png,image/jpeg,image/webp" multiple
                       class="sr-only" @change="fileCount = $event.target.files.length">
            </label>
            @error('anomaly_photos')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
