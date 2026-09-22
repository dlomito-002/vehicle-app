@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    @php use App\Enums\UserRole; @endphp

    <div class="page-heading">
        <div>
            <h1 class="page-title">Editar usuario</h1>
            <p class="page-subtitle">{{ $user->name }} · {{ $user->email }}</p>
        </div>
    </div>

    <div class="card" style="max-width:32rem">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <label for="name" class="form-label">Nombre completo</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required style="width:100%">
                @error('name')<p class="field-error">{{ $message }}</p>@enderror

                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required style="width:100%">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror

                <label for="role" class="form-label">Rol</label>
                <select id="role" name="role" required style="width:100%">
                    @foreach (UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @if ($user->is(auth()->user()))
                    <p class="field-help">No puedes cambiar tu propio rol.</p>
                @endif
                @error('role')<p class="field-error">{{ $message }}</p>@enderror

                <button type="submit" class="btn btn-primary" style="margin-top:20px">Guardar cambios</button>
            </form>
        </div>
    </div>
@endsection
