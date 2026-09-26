<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with(['roles', 'properties:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estatus' => $user->estatus,
                'role' => $user->roles->first()?->name,
                'property_ids' => $user->properties->pluck('id'),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'properties' => Property::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'estatus' => 'activo',
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);
        $this->syncProperties($user, $data['property_ids'] ?? [], $data['role']);

        return back()->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::exists('roles', 'name')],
            'estatus' => ['required', Rule::in(['activo', 'inactivo', 'suspendido'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'estatus' => $data['estatus'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);
        $this->syncProperties($user, $data['property_ids'] ?? [], $data['role']);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $user->update(['estatus' => 'inactivo']);

        return back()->with('success', 'Usuario desactivado.');
    }

    private function syncProperties(User $user, array $propertyIds, string $role): void
    {
        if ($role === User::ADMIN_ROL) {
            $user->properties()->detach();

            return;
        }

        $sync = [];
        foreach (array_values($propertyIds) as $index => $id) {
            $sync[$id] = ['is_default' => $index === 0];
        }

        $user->properties()->sync($sync);
    }
}
