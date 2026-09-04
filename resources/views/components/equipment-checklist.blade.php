@php
    use App\Enums\EquipmentItem;
@endphp

<div>
    <p class="text-sm font-medium text-slate-700 mb-1">Chequeo general de equipo</p>
    <p class="text-xs text-slate-500 mb-3">
        Marca Sí/No para cada elemento. Puedes adjuntar una fotografía opcional por elemento.
    </p>

    <div class="grid sm:grid-cols-2 gap-3">
        @foreach (EquipmentItem::cases() as $item)
            @php $key = $item->value; @endphp
            <div class="rounded-md border border-slate-200 p-3">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <span class="text-sm text-slate-700">{{ $item->label() }}</span>
                    <div class="flex gap-3 shrink-0">
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="radio" name="equipment_checks[{{ $key }}]" value="1"
                                   @checked(old("equipment_checks.$key") === '1') required
                                   class="text-brand-olive focus:ring-brand-olive">
                            Sí
                        </label>
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="radio" name="equipment_checks[{{ $key }}]" value="0"
                                   @checked(old("equipment_checks.$key") === '0')
                                   class="text-brand-orange focus:ring-brand-orange">
                            No
                        </label>
                    </div>
                </div>
                <input type="file" name="equipment_photos[{{ $key }}]" accept="image/png,image/jpeg,image/webp"
                       class="text-xs w-full file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-brand-cyan/10 file:text-brand-cyan">
                @error("equipment_checks.$key")<p class="mt-1 text-xs text-brand-orange">{{ $message }}</p>@enderror
                @error("equipment_photos.$key")<p class="mt-1 text-xs text-brand-orange">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
