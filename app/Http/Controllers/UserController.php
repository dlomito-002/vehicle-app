<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        // Hashing is the User model's 'password' => 'hashed' cast.
        User::create($request->validated());

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        // An empty password field means "keep the current password".
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }
}
