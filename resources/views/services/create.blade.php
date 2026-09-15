@extends('layouts.app')

@section('title', 'Registrar servicio')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Registrar servicio de mantenimiento</h1>

    <form method="POST" action="{{ route('services.store') }}"
          class="bg-white border border-slate-200 rounded-lg p-6 max-w-lg space-y-4">
        @csrf
        @include('services._form')

        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Guardar servicio
        </button>
    </form>
@endsection
