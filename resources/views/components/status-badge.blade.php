@props(['status'])

@php
    $variant = match ($status) {
        'ok' => 'badge-success',
        'pending' => 'badge-warning',
        'anomaly' => 'badge-danger',
        default => 'badge-secondary',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge $variant"]) }}>
    {{ $slot }}
</span>
