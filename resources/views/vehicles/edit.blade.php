@extends('layouts.app')

@section('title', 'Editar vehículo')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Editar vehículo</h1>
            <p class="page-subtitle">Actualiza los datos del vehículo.</p>
        </div>
    </div>

    <div class="card" style="max-width:32rem">
        <div class="card-body">
            <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
                @csrf
                @method('PUT')

                <label for="make" class="form-label">Marca</label>
                <input id="make" name="make" value="{{ old('make', $vehicle->make) }}" required style="width:100%">
                @error('make')<p class="field-error">{{ $message }}</p>@enderror

                <label for="model" class="form-label">Modelo (opcional)</label>
                <input id="model" name="model" value="{{ old('model', $vehicle->model) }}" style="width:100%">
                @error('model')<p class="field-error">{{ $message }}</p>@enderror

                <label for="license_plate" class="form-label">Placa</label>
                <input id="license_plate" name="license_plate" value="{{ old('license_plate', $vehicle->license_plate) }}" required
                       placeholder="e.g. C046BTW" class="font-data" style="width:100%">
                @error('license_plate')<p class="field-error">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-primary" style="margin-top:20px">Guardar cambios</button>
            </form>
        </div>
    </div>
@endsection
