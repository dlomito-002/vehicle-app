@props(['field', 'label'])

@php
    use App\Enums\ConditionStatus;
    $selected = old($field);
@endphp

<fieldset>
    <p class="section-label">{{ $label }}</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        @foreach (ConditionStatus::cases() as $case)
            <label class="choice-chip {{ $selected === $case->value ? 'is-selected' : '' }}">
                <input type="radio" name="{{ $field }}" value="{{ $case->value }}"
                       @checked($selected === $case->value) required>
                {{ $case->label($field) }}
            </label>
        @endforeach
    </div>
    @error($field)
        <p class="field-error">{{ $message }}</p>
    @enderror
</fieldset>
