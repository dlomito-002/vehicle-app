@php
    use App\Enums\FuelLevel;
    $selected = old('fuel_level');
@endphp

<fieldset>
    <legend class="text-sm font-medium text-slate-700 mb-2">Nivel de combustible</legend>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
        @foreach (FuelLevel::cases() as $case)
            <label class="flex items-center justify-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer transition-colors
                          {{ $selected === $case->value ? 'border-brand-cyan bg-brand-cyan/5' : 'border-slate-200 hover:bg-slate-50' }}">
                <input type="radio" name="fuel_level" value="{{ $case->value }}"
                       @checked($selected === $case->value) required
                       class="text-brand-cyan focus:ring-brand-cyan">
                {{ $case->label() }}
            </label>
        @endforeach
    </div>
    @error('fuel_level')
        <p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>
    @enderror
</fieldset>
