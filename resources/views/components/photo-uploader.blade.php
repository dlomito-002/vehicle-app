@props(['positions'])

<div>
    <p class="text-sm font-medium text-slate-700 mb-2">Fotografías del vehículo</p>
    <p class="text-xs text-slate-500 mb-3">Hasta 10 archivos en total, máximo 10 MB por archivo.</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        @foreach ($positions as $position)
            <label class="flex flex-col gap-1.5 px-3 py-3 rounded-md border border-dashed border-slate-300 hover:border-brand-cyan cursor-pointer text-center transition-colors">
                <span class="text-sm text-slate-700">{{ $position->label() }}</span>
                <input type="file" name="position_photos[{{ $position->value }}]" accept="image/png,image/jpeg,image/webp"
                       class="text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-brand-cyan/10 file:text-brand-cyan">
                @error("position_photos.{$position->value}")
                    <span class="text-xs text-brand-orange">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
    </div>

    @error('photos')
        <p class="mt-2 text-sm text-brand-orange">{{ $message }}</p>
    @enderror
</div>
