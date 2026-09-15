@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Usuarios</h1>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-brand-magenta hover:bg-brand-magenta/90">
            Agregar usuario
        </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Nombre</th>
                    <th class="px-4 py-2 font-medium">Correo</th>
                    <th class="px-4 py-2 font-medium">Rol</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td data-label="Nombre" class="px-4 py-3">{{ $user->name }}</td>
                        <td data-label="Correo" class="px-4 py-3">{{ $user->email }}</td>
                        <td data-label="Rol" class="px-4 py-3">
                            <x-status-badge :status="$user->isAdmin() ? 'pending' : 'ok'">{{ $user->role->label() }}</x-status-badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('users.edit', $user) }}" class="text-brand-cyan hover:underline text-xs">Editar</a>
                            @if (! $user->is(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar a {{ $user->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-brand-orange hover:underline text-xs">Eliminar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Todavía no hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
