@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Panel</h1>

    <div class="grid sm:grid-cols-2 gap-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Recepciones abiertas</h2>
            <div class="space-y-2">
                @forelse ($openReceptions as $reception)
                    <a href="{{ route('receptions.show', $reception) }}"
                       class="flex items-center justify-between bg-white border border-slate-200 rounded-lg p-4 hover:border-brand-cyan transition-colors">
                        <div>
                            <p class="font-medium text-slate-900">{{ $reception->vehicle->displayName() }}</p>
                            <p class="text-sm text-slate-500">{{ $reception->reception_date->format('d/m/Y') }} · {{ $reception->received_by_name }}</p>
                        </div>
                        <x-status-badge status="pending">Abierta</x-status-badge>
                    </a>
                @empty
                    <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm text-slate-500">
                        No hay recepciones abiertas en este momento.
                    </div>
                @endforelse
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Cerradas recientemente</h2>
            <div class="space-y-2">
                @forelse ($recentlyClosed as $reception)
                    <a href="{{ route('comparisons.show', $reception) }}"
                       class="flex items-center justify-between bg-white border border-slate-200 rounded-lg p-4 hover:border-brand-cyan transition-colors">
                        <div>
                            <p class="font-medium text-slate-900">{{ $reception->vehicle->displayName() }}</p>
                            <p class="text-sm text-slate-500">
                                Devuelto el {{ $reception->delivery?->return_date?->format('d/m/Y') }}
                            </p>
                        </div>
                        <x-status-badge status="ok">Cerrada</x-status-badge>
                    </a>
                @empty
                    <div class="bg-white border border-slate-200 rounded-lg p-4 text-sm text-slate-500">
                        Todavía no hay recepciones cerradas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
