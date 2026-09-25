@extends('layouts.app')

@section('title', 'Ayuda')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Ayuda</h1>
            <p class="page-subtitle">
                Reporta un problema o inconveniente con la aplicación o con la gestión de vehículos.
                Se enviará directamente al encargado de flota.
            </p>
        </div>
    </div>

    <div class="card" style="max-width:36rem">
        <div class="card-body">
            <form method="POST" action="{{ route('help.store') }}">
                @csrf

                <label for="message" class="form-label" style="margin-top:0">Descripción del problema</label>
                <textarea id="message" name="message" rows="6" required
                          placeholder="Describe el problema o inconveniente que encontraste..."
                          style="width:100%">{{ old('message') }}</textarea>
                @error('message')<p class="field-error">{{ $message }}</p>@enderror

                <p class="field-help">Se enviará como {{ auth()->user()->name }} ({{ auth()->user()->email }}).</p>

                <button type="submit" class="btn btn-primary" style="margin-top:16px">Enviar reporte</button>
            </form>
        </div>
    </div>
@endsection
