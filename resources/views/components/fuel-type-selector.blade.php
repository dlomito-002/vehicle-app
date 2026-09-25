@php
    use App\Enums\FuelType;
    $selected = old('fuel_type');
@endphp

<fieldset>
    <p class="section-label">Tipo de combustible</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach (FuelType::cases() as $case)
            <label class="choice-chip {{ $selected === $case->value ? 'is-selected' : '' }}">
                <input type="radio" name="fuel_type" value="{{ $case->value }}"
                       @checked($selected === $case->value) required>
                {{ $case->label() }}
            </label>
        @endforeach
    </div>
    @error('fuel_type')
        <p class="field-error">{{ $message }}</p>
    @enderror
</fieldset>
