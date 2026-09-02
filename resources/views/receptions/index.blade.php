@extends('layouts.app')

@section('title', 'Recepciones')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Recepciones de vehículos</h1>
        <a href="{{ route('receptions.create') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Nueva recepción
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Vehículo</th>
                    <th class="px-4 py-2 font-medium">Fecha</th>
                    <th class="px-4 py-2 font-medium">Recibido por</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($receptions as $reception)
                    <tr>
                        <td class="px-4 py-3">{{ $reception->vehicle->displayName() }}</td>
                        <td class="px-4 py-3">{{ $reception->reception_date->format('d/m/Y') }} · {{ $reception->reception_time }}</td>
                        <td class="px-4 py-3">{{ $reception->received_by_name }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$reception->status->value === 'open' ? 'pending' : 'ok'">
                                {{ $reception->status->label() }}
                            </x-status-badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('receptions.show', $reception) }}" class="text-brand-cyan hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Todavía no hay recepciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $receptions->links() }}</div>
@endsection
