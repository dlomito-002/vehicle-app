@php
    use App\Enums\FuelType;
    $selected = old('fuel_type');
@endphp

<fieldset>
    <legend class="text-sm font-medium text-slate-700 mb-2">Tipo de combustible</legend>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        @foreach (FuelType::cases() as $case)
            <label class="flex items-center justify-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer transition-colors
                          {{ $selected === $case->value ? 'border-brand-cyan bg-brand-cyan/5' : 'border-slate-200 hover:bg-slate-50' }}">
                <input type="radio" name="fuel_type" value="{{ $case->value }}"
                       @checked($selected === $case->value) required
                       class="text-brand-cyan focus:ring-brand-cyan">
                {{ $case->label() }}
            </label>
        @endforeach
    </div>
    @error('fuel_type')
        <p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>
    @enderror
</fieldset>
