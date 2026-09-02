@props(['status'])

@php
    // 'ok' -> olive, 'issue'/'pending' -> amber, 'anomaly' -> orange
    $styles = match ($status) {
        'ok' => 'bg-brand-olive/10 text-brand-olive',
        'pending' => 'bg-brand-amber/10 text-amber-700',
        'anomaly' => 'bg-brand-orange/10 text-brand-orange',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium $styles"]) }}>
    {{ $slot }}
</span>
