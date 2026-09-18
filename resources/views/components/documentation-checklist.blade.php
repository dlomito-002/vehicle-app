@props(['documentTypes'])

<fieldset class="space-y-3">
    <legend class="text-sm font-medium text-slate-700 mb-1">Documentación del vehículo</legend>
    @foreach ($documentTypes as $docType)
        @php $key = $docType->value; @endphp
        <div class="flex items-center justify-between gap-3 px-3 py-2 rounded-md border border-slate-200">
            <span class="text-sm text-slate-700 flex-1 min-w-0">{{ $docType->label() }}</span>
            <div class="flex gap-4 shrink-0">
                <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="radio" name="documentation[{{ $key }}]" value="1"
                           @checked(old("documentation.$key") === '1') required
                           class="text-brand-olive focus:ring-brand-olive">
                    Sí
                </label>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="radio" name="documentation[{{ $key }}]" value="0"
                           @checked(old("documentation.$key") === '0')
                           class="text-brand-orange focus:ring-brand-orange">
                    No
                </label>
            </div>
        </div>
        @error("documentation.$key")
            <p class="text-sm text-brand-orange">{{ $message }}</p>
        @enderror
    @endforeach
</fieldset>
