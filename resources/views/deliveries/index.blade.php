@extends('layouts.app')

@section('title', 'Devoluciones')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Devoluciones de vehículos</h1>
        <a href="{{ route('deliveries.select-vehicle') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Registrar devolución
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Vehículo</th>
                    <th class="px-4 py-2 font-medium">Fecha de devolución</th>
                    <th class="px-4 py-2 font-medium">Devuelto por</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($deliveries as $delivery)
                    <tr>
                        <td data-label="Vehículo" class="px-4 py-3">{{ $delivery->vehicle->displayName() }}</td>
                        <td data-label="Fecha de devolución" class="px-4 py-3">{{ $delivery->return_date->format('d/m/Y') }} · {{ $delivery->return_time }}</td>
                        <td data-label="Devuelto por" class="px-4 py-3">{{ $delivery->returned_by_name }}</td>
                        <td data-label="" class="space-x-3 px-4 py-3 text-right">
                            <a href="{{ route('deliveries.show', $delivery) }}" class="text-brand-cyan hover:underline">Ver</a>
                            <a href="{{ route('comparisons.show', $delivery->reception) }}" class="text-brand-cyan hover:underline">Comparar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Todavía no hay devoluciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $deliveries->links() }}</div>
@endsection
