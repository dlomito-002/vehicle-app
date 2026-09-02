@extends('layouts.app')

@section('title', 'Agregar vehículo')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Agregar vehículo</h1>

    <form method="POST" action="{{ route('vehicles.store') }}" class="bg-white border border-slate-200 rounded-lg p-6 max-w-lg space-y-4">
        @csrf

        <div>
            <label for="make" class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
            <input id="make" name="make" value="{{ old('make') }}" required
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('make')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="model" class="block text-sm font-medium text-slate-700 mb-1">Modelo (opcional)</label>
            <input id="model" name="model" value="{{ old('model') }}"
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('model')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="license_plate" class="block text-sm font-medium text-slate-700 mb-1">Placa</label>
            <input id="license_plate" name="license_plate" value="{{ old('license_plate') }}" required
                   placeholder="e.g. C046BTW"
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm font-data">
            @error('license_plate')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Guardar vehículo
        </button>
    </form>
@endsection
