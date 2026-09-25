@props(['positions'])

<div>
    <p class="section-label">Fotografías del vehículo</p>
    <p class="section-hint">Hasta 10 archivos en total, máximo 10 MB por archivo.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px">
        @foreach ($positions as $position)
            <div class="upload-tile">
                <span class="upload-tile-label">{{ $position->label() }}</span>
                <x-photo-input name="position_photos[{{ $position->value }}]" :label="$position->label()" />
                @error("position_photos.{$position->value}")
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>
        @endforeach
    </div>

    @error('photos')
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
