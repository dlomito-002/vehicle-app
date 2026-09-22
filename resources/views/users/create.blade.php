@extends('layouts.app')

@section('title', 'Agregar usuario')

@section('content')
    @php use App\Enums\UserRole; @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">Agregar usuario</h1>
            <p class="page-subtitle">Da acceso al sistema con el rol correspondiente.</p>
        </div>
    </div>

    <div class="card" style="max-width:32rem">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <label for="name" class="form-label">Nombre completo</label>
                <input id="name" name="name" value="{{ old('name') }}" required style="width:100%">
                @error('name')<p class="field-error">{{ $message }}</p>@enderror

                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required style="width:100%">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror

                <label for="role" class="form-label">Rol</label>
                <select id="role" name="role" required style="width:100%">
                    <option value="">Selecciona un rol</option>
                    @foreach (UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<p class="field-error">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-primary" style="margin-top:20px">Guardar usuario</button>
            </form>
        </div>
    </div>
@endsection
