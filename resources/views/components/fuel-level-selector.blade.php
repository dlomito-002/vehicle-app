@php
    use App\Enums\FuelLevel;
    $selected = old('fuel_level');
@endphp

<fieldset>
    <p class="section-label">Nivel de combustible</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach (FuelLevel::cases() as $case)
            <label class="choice-chip {{ $selected === $case->value ? 'is-selected' : '' }}">
                <input type="radio" name="fuel_level" value="{{ $case->value }}"
                       @checked($selected === $case->value) required>
                {{ $case->label() }}
            </label>
        @endforeach
    </div>
    @error('fuel_level')
        <p class="field-error">{{ $message }}</p>
    @enderror
</fieldset>
