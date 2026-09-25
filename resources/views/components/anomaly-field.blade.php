@php $hasAnomalyOld = old('has_anomaly'); @endphp

<div x-data="{ hasAnomaly: {{ $hasAnomalyOld === '1' ? 'true' : 'false' }} }" class="field-item">
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
            <x-photo-input name="anomaly_photos" label="Fotografía(s) de la anomalía" multiple />
            @error('anomaly_photos')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
