@extends('layouts.app')

@section('title', 'Editar servicio')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Editar servicio de mantenimiento</h1>

    <form method="POST" action="{{ route('services.update', $service) }}"
          class="bg-white border border-slate-200 rounded-lg p-6 max-w-lg space-y-4">
        @csrf
        @method('PUT')
        @include('services._form')

        <div class="flex items-center justify-between">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
                Guardar cambios
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('services.destroy', $service) }}" class="max-w-lg mt-3"
          onsubmit="return confirm('¿Eliminar este registro de servicio?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-brand-orange hover:underline">Eliminar servicio</button>
    </form>
@endsection
