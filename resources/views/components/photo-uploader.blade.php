@props(['positions'])

<div>
    <p class="section-label">Fotografías del vehículo</p>
    <p class="section-hint">Hasta 10 archivos en total, máximo 10 MB por archivo.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px">
        @foreach ($positions as $position)
            <label class="upload-tile" x-data="{ name: '' }">
                <span class="upload-tile-label">{{ $position->label() }}</span>
                <span style="font-size:11px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%" x-text="name || 'Toca para elegir una foto'"></span>
                <input type="file" name="position_photos[{{ $position->value }}]" accept="image/png,image/jpeg,image/webp"
                       class="sr-only" @change="name = $event.target.files[0]?.name ?? ''">
                @error("position_photos.{$position->value}")
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
    </div>

    @error('photos')
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
