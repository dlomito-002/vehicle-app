@php $hasAnomalyOld = old('has_anomaly'); @endphp

<div x-data="{ hasAnomaly: {{ $hasAnomalyOld === '1' ? 'true' : 'false' }} }" class="rounded-md border border-slate-200 p-4">
    <fieldset>
        <legend class="text-sm font-medium text-slate-700 mb-2">Daño o anomalía adicional</legend>
        <div class="flex gap-3">
            <label class="flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer"
                   :class="!hasAnomaly ? 'border-brand-olive bg-brand-olive/5' : 'border-slate-200'">
                <input type="radio" name="has_anomaly" value="0" x-model="hasAnomaly" x-bind:value="'0'"
                       @click="hasAnomaly = false" @checked($hasAnomalyOld !== '1') required
                       class="text-brand-olive focus:ring-brand-olive">
                No
            </label>
            <label class="flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer"
                   :class="hasAnomaly ? 'border-brand-orange bg-brand-orange/5' : 'border-slate-200'">
                <input type="radio" name="has_anomaly" value="1"
                       @click="hasAnomaly = true" @checked($hasAnomalyOld === '1')
                       class="text-brand-orange focus:ring-brand-orange">
                Sí
            </label>
        </div>
    </fieldset>

    <div x-show="hasAnomaly" x-cloak class="mt-4 space-y-4">
        <div>
            <label for="anomaly_description" class="block text-sm font-medium text-slate-700 mb-1">
                Descripción del daño, falla o anomalía
            </label>
            <textarea id="anomaly_description" name="anomaly_description" rows="3"
                      class="w-full rounded-md border-slate-300 focus:border-brand-orange focus:ring-brand-orange text-sm"
                      x-bind:required="hasAnomaly">{{ old('anomaly_description') }}</textarea>
            @error('anomaly_description')
                <p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Fotografía(s) de la anomalía</label>
            <input type="file" name="anomaly_photos[]" accept="image/png,image/jpeg,image/webp" multiple
                   class="text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-brand-orange/10 file:text-brand-orange">
            @error('anomaly_photos')
                <p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
