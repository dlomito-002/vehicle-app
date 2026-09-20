@extends('layouts.app')

@section('title', 'Vehículos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Vehículos</h1>
        <a href="{{ route('vehicles.create') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Agregar vehículo
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Marca / modelo</th>
                    <th class="px-4 py-2 font-medium">Placa</th>
                    <th class="px-4 py-2 font-medium">Recepciones</th>
                    <th class="px-4 py-2 font-medium">Devoluciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td data-label="Marca / modelo" class="px-4 py-3">{{ $vehicle->make }} {{ $vehicle->model }}</td>
                        <td data-label="Placa" class="px-4 py-3 font-data">{{ $vehicle->license_plate }}</td>
                        <td data-label="Recepciones" class="px-4 py-3">{{ $vehicle->receptions_count }}</td>
                        <td data-label="Devoluciones" class="px-4 py-3">{{ $vehicle->deliveries_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Todavía no hay vehículos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vehicles->links() }}</div>
@endsection
