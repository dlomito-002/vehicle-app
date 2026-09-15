@props(['fieldName' => 'signature_data'])

<div
    x-data="{
        drawing: false,
        hasStroke: false,
        ctx: null,
        init() {
            const canvas = this.$refs.canvas;
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            this.ctx = canvas.getContext('2d');
            this.ctx.scale(ratio, ratio);
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.strokeStyle = '#1e293b';

            const existing = this.$refs.input.value;
            if (existing) {
                const img = new Image();
                img.onload = () => this.ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight);
                img.src = existing;
                this.hasStroke = true;
            }
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
    }"
>
    <p class="text-sm font-medium text-slate-700 mb-1">Firma</p>
    <p class="text-xs text-slate-500 mb-2">Firma con el dedo o el mouse dentro del recuadro.</p>

    <div class="rounded-md border border-dashed border-slate-300 bg-white overflow-hidden">
        <canvas
            x-ref="canvas"
            class="w-full h-40 touch-none cursor-crosshair"
            @mousedown="start($event)" @mousemove="move($event)" @mouseup="end()" @mouseleave="end()"
            @touchstart="start($event)" @touchmove="move($event)" @touchend="end()"
        ></canvas>
    </div>

    <div class="flex items-center justify-between mt-2">
        <button type="button" @click="clear()" class="text-xs text-brand-orange hover:underline">Borrar firma</button>
        <span class="text-xs text-slate-400" x-show="!hasStroke">Sin firmar</span>
    </div>

    <input type="hidden" name="{{ $fieldName }}" x-ref="input" value="{{ old($fieldName) }}">
    @error($fieldName)<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
</div>
