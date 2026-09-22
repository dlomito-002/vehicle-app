@props(['fieldName' => 'signature_data', 'fileFieldName' => 'signature_file'])

<div
    x-data="{
        mode: 'draw',
        drawing: false,
        hasStroke: false,
        fileName: '',
        ctx: null,
        resizeObserver: null,
        pendingExisting: null,
        init() {
            const canvas = this.$refs.canvas;
            this.ctx = canvas.getContext('2d');
            this.pendingExisting = this.$refs.input.value || null;

            // This component can mount while its step/section is still
            // hidden behind x-show (display: none) — e.g. the signature
            // pad lives on step 2 of a multi-step form. A hidden element
            // reports offsetWidth/offsetHeight as 0, so sizing the canvas
            // right away would leave it with a 0x0 backing bitmap: any
            // later toDataURL() call on it returns the bare 'data:,' URI
            // instead of a real PNG, which fails signature_data's
            // starts_with validation on first use. Size it once it
            // actually has a layout box instead of assuming it's visible
            // at mount time.
            if (!this.configureCanvas()) {
                this.resizeObserver = new ResizeObserver(() => this.configureCanvas());
                this.resizeObserver.observe(canvas);
            }
        },
        configureCanvas() {
            const canvas = this.$refs.canvas;
            if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                return false;
            }

            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            this.ctx.scale(ratio, ratio);
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.strokeStyle = '#1e293b';

            if (this.pendingExisting) {
                const img = new Image();
                img.onload = () => this.ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight);
                img.src = this.pendingExisting;
                this.hasStroke = true;
            }

            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }

            return true;
        },
        point(e) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            const t = e.touches && e.touches[0] ? e.touches[0] : e;
            return { x: t.clientX - rect.left, y: t.clientY - rect.top };
        },
        start(e) {
            e.preventDefault();
            this.drawing = true;
            this.hasStroke = true;
            const p = this.point(e);
            this.ctx.beginPath();
            this.ctx.moveTo(p.x, p.y);
        },
        move(e) {
            if (!this.drawing) return;
            e.preventDefault();
            const p = this.point(e);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
        },
        end() {
            if (!this.drawing) return;
            this.drawing = false;
            this.$refs.input.value = this.$refs.canvas.toDataURL('image/png');
        },
        clear() {
            const canvas = this.$refs.canvas;
            this.ctx.clearRect(0, 0, canvas.width, canvas.height);
            this.$refs.input.value = '';
            this.hasStroke = false;
        },
        useDrawMode() {
            this.mode = 'draw';
            // A previously chosen file must not be submitted alongside a
            // drawn signature — only one of the two fields should reach
            // the server.
            this.$refs.fileInput.value = '';
            this.fileName = '';
        },
        useUploadMode() {
            this.mode = 'upload';
            // Same in reverse: drop any drawn signature so it doesn't get
            // submitted alongside the uploaded file.
            this.clear();
        },
        onFileChosen(e) {
            this.fileName = e.target.files[0]?.name ?? '';
        },
    }"
>
    <p class="section-label">Firma</p>

    <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
        <button type="button" @click="useDrawMode()" class="btn btn-sm"
                :class="mode === 'draw' ? 'btn-primary' : 'btn-outline-secondary'">
            Dibujar firma
        </button>
        <button type="button" @click="useUploadMode()" class="btn btn-sm"
                :class="mode === 'upload' ? 'btn-primary' : 'btn-outline-secondary'">
            Subir PNG
        </button>
    </div>

    <div x-show="mode === 'draw'">
        <p class="section-hint">Firma con el dedo o el mouse dentro del recuadro.</p>

        <div style="border:1px dashed var(--border);border-radius:10px;background:#fff;overflow:hidden">
            <canvas
                x-ref="canvas"
                style="width:100%;height:160px;touch-action:none;cursor:crosshair;display:block"
                @mousedown="start($event)" @mousemove="move($event)" @mouseup="end()" @mouseleave="end()"
                @touchstart="start($event)" @touchmove="move($event)" @touchend="end()"
            ></canvas>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px">
            <button type="button" @click="clear()" style="background:none;border:0;padding:0;cursor:pointer;color:var(--danger);font-size:12.5px;font-weight:700">Borrar firma</button>
            <span style="font-size:12px;color:var(--muted)" x-show="!hasStroke">Sin firmar</span>
        </div>
    </div>

    <div x-show="mode === 'upload'" x-cloak>
        <p class="section-hint">Sube la firma como un archivo PNG.</p>

        <label style="display:flex;align-items:center;justify-content:center;border:1px dashed var(--border);border-radius:10px;background:var(--card);height:160px;cursor:pointer;font-size:13px;color:var(--muted)">
            <span x-text="fileName || 'Selecciona un archivo PNG…'"></span>
            <input type="file" name="{{ $fileFieldName }}" x-ref="fileInput" accept="image/png,.png"
                   data-skip-compress class="sr-only" @change="onFileChosen($event)">
        </label>
    </div>

    <input type="hidden" name="{{ $fieldName }}" x-ref="input" value="{{ old($fieldName) }}">
    @error($fieldName)<p class="field-error">{{ $message }}</p>@enderror
    @error($fileFieldName)<p class="field-error">{{ $message }}</p>@enderror
</div>
