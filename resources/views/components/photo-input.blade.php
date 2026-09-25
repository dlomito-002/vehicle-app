@props(['name', 'label' => 'Foto', 'multiple' => false])

{{-- One real <input type="file"> per photo field, so the submitted field name
     (and therefore the reception/delivery item the photo belongs to) never
     changes. "Tomar foto" just sets capture="environment" on that same input
     before opening it (devices without a camera fall back to the file picker);
     "Elegir archivo" removes it. Picking again replaces the previous file, so a
     retake never creates a second photo. In multiple mode a camera shot is
     appended to the current selection instead of replacing it. --}}
<div class="photo-field" :class="{ 'is-attached': files.length }"
     x-data="{
        files: [], previews: [], camera: false,
        pick(camera) {
            this.camera = camera;
            camera ? $refs.input.setAttribute('capture', 'environment') : $refs.input.removeAttribute('capture');
            $refs.input.click();
        },
        changed() {
            let files = [...$refs.input.files];
            if ({{ $multiple ? 'true' : 'false' }} && this.camera && this.files.length) {
                const dt = new DataTransfer();
                [...this.files, ...files].forEach(f => dt.items.add(f));
                $refs.input.files = dt.files;
                files = [...dt.files];
            }
            this.previews.forEach(u => URL.revokeObjectURL(u));
            this.files = files;
            this.previews = files.map(f => URL.createObjectURL(f));
        },
        clear() { $refs.input.value = ''; this.changed(); },
     }">
    <input type="file" name="{{ $name }}{{ $multiple ? '[]' : '' }}" accept="image/png,image/jpeg,image/webp"
           @if ($multiple) multiple @endif x-ref="input" class="sr-only" tabindex="-1" @change="changed()">

    <div class="photo-field-preview" x-show="previews.length" x-cloak>
        <template x-for="src in previews" :key="src">
            <img :src="src" alt="Vista previa" class="photo-field-thumb">
        </template>
    </div>

    <div class="photo-field-actions">
        <button type="button" class="photo-btn" @click="pick(true)" aria-label="Tomar foto: {{ $label }}">
            <span x-text="files.length ? 'Retomar' : 'Tomar foto'">Tomar foto</span>
        </button>
        <button type="button" class="photo-btn" @click="pick(false)" aria-label="Elegir archivo: {{ $label }}">
            <span x-text="files.length ? 'Cambiar' : 'Elegir archivo'">Elegir archivo</span>
        </button>
        <button type="button" class="photo-btn" x-show="files.length" x-cloak @click="clear()" aria-label="Quitar foto: {{ $label }}">Quitar</button>
    </div>
    <span class="photo-field-name" x-show="files.length" x-cloak
          x-text="files.length > 1 ? files.length + ' fotos seleccionadas' : (files[0]?.name ?? '')"></span>
</div>
