<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function getUsers(Request $request): JsonResponse
    {
        $q = User::query()
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->selectRaw('
            users.*,
            roles.name role
        ')
            ->where(['estatus' => 'activo']);

        if ($request->filled('area_id')) {
            $q->where('users.area_id', (int) $request->input('area_id'));
        }

        $users = $q->get();

        return response()->json([
            'data' => $users->load(['roles', 'area']),
        ], JsonResponse::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->input('role') === 'Instructor') {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::exists('roles', 'name')],
            ]);
            $user = User::create([
                'name' => $request->name,
            ]);
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::exists('roles', 'name')],
                'email' => ['required', 'email', Rule::unique('users')->ignore('inactivo', 'estatus')],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'area_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('areas', 'id'),
                    Rule::requiredIf(fn () => (string) $request->input('role') === User::OPERADOR_ROL),
                ],
                'property_ids' => ['nullable', 'array'],
                'property_ids.*' => ['integer', 'exists:properties,id'],
            ]);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'area_id' => $request->filled('area_id') ? (int) $request->input('area_id') : null,
            ]);
        }

        $user->assignRole($request->role);
        $this->syncUserProperties($user, $request->input('property_ids'), $request->role);

        return response()->json([
            'data' => $user->load(['area', 'properties']),
        ], JsonResponse::HTTP_OK);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($request->input('role') === 'Instructor') {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::exists('roles', 'name')],
            ]);
            $params = $request->only(['name']);
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::exists('roles', 'name')],
                'email' => ['required', 'email', Rule::unique('users')->where(function ($query) use ($user) {
                    $query->whereNotIn('id', [$user->id])->whereNotIn('estatus', ['inactivo']);

                    return $query;
                })],
                'area_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('areas', 'id'),
                    Rule::requiredIf(fn () => (string) $request->input('role') === User::OPERADOR_ROL),
                ],
                'property_ids' => ['nullable', 'array'],
                'property_ids.*' => ['integer', 'exists:properties,id'],
            ]);
            $params = $request->only(['name', 'email', 'cliente_id']);
            $params['area_id'] = $request->filled('area_id') ? (int) $request->input('area_id') : null;
        }
        if (isset($request->nota)) {
            $params['nota'] = $request->nota;
        }
        $user->update($params);

        if (! $user->hasRole($request->role)) {
            $roles = $user->getRoleNames();
            foreach ($roles as $role) {
                $user->removeRole($role);
            }
            $user->assignRole($request->role);
        }

        $this->syncUserProperties($user, $request->input('property_ids'), $request->role);

        return response()->json([
            'data' => $user->fresh()->load(['area', 'properties']),
        ], JsonResponse::HTTP_OK);
    }

    public function updatePassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => [
                'required',
                Rules\Password::min(8)->letters(),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'data' => $user,
        ], JsonResponse::HTTP_OK);
    }

    public function updatePerfil(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $request->user()->update($request->only([
            'name',
            'email',
        ]));

        return response()->json([
            'data' => $request->user(),
        ], JsonResponse::HTTP_OK);
    }

    public function getUser(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load(['roles', 'area', 'properties']),
        ], JsonResponse::HTTP_OK);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->update([
            'estatus' => 'inactivo',
        ]);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function syncUserProperties(User $user, ?array $propertyIds, ?string $role): void
    {
        if ($role === User::ADMIN_ROL) {
            $user->properties()->detach();

            return;
        }

        if (! is_array($propertyIds)) {
            return;
        }

        $sync = [];
        foreach (array_values($propertyIds) as $index => $id) {
            $sync[(int) $id] = ['is_default' => $index === 0];
        }

        $user->properties()->sync($sync);
    }
}
