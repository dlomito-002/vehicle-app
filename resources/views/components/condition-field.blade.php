@props(['field', 'label'])

@php
    use App\Enums\ConditionStatus;
    $selected = old($field);
@endphp

<fieldset>
    <legend class="text-sm font-medium text-slate-700 mb-2">{{ $label }}</legend>
    <div class="flex flex-wrap gap-3">
        @foreach (ConditionStatus::cases() as $case)
            <label class="flex items-center gap-2 px-3 py-2 rounded-md border text-sm cursor-pointer transition-colors
                          {{ $selected === $case->value ? 'border-brand-cyan bg-brand-cyan/5' : 'border-slate-200 hover:bg-slate-50' }}">
                <input type="radio" name="{{ $field }}" value="{{ $case->value }}"
                       @checked($selected === $case->value) required
                       class="text-brand-cyan focus:ring-brand-cyan">
                {{ $case->label($field) }}
            </label>
        @endforeach
    </div>
    @error($field)
        <p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>
    @enderror
</fieldset>
