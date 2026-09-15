@extends('layouts.app')

@section('title', 'Agregar usuario')

@section('content')
    @php use App\Enums\UserRole; @endphp

    <h1 class="text-xl font-semibold text-slate-900 mb-6">Agregar usuario</h1>

    <form method="POST" action="{{ route('users.store') }}" class="bg-white border border-slate-200 rounded-lg p-6 max-w-lg space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre completo</label>
            <input id="name" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('name')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('email')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
            <select id="role" name="role" required
                    class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
                <option value="">Selecciona un rol</option>
                @foreach (UserRole::cases() as $role)
                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
            <input type="password" id="password" name="password" required
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
            @error('password')<p class="mt-1 text-sm text-brand-orange">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   class="w-full rounded-md border-slate-300 focus:border-brand-cyan focus:ring-brand-cyan text-sm">
        </div>

        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Guardar usuario
        </button>
    </form>
@endsection
