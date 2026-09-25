@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-heading">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Cuentas con acceso al sistema y su rol.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">Agregar usuario</a>
    </div>

    <div class="card data-table-shell">
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="Nombre">{{ $user->name }}</td>
                            <td data-label="Correo">{{ $user->email }}</td>
                            <td data-label="Rol">
                                <x-status-badge :status="$user->isAdmin() ? 'pending' : 'ok'">{{ $user->role->label() }}</x-status-badge>
                            </td>
                            <td data-label="" class="data-table-actions">
                                <a href="{{ route('users.edit', $user) }}">Editar</a>
                                @if (! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline"
                                          onsubmit="return confirm('¿Eliminar a {{ $user->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color:var(--danger);font-weight:800;background:none;border:0;cursor:pointer;padding:0;font:inherit">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="data-table-empty">Todavía no hay usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
