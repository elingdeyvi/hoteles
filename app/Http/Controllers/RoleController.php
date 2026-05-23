<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function getRoles(Request $request): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return response()->json([
            'data' => $roles,
        ], JsonResponse::HTTP_OK);
    }

    public function getPermissions(Request $request): JsonResponse
    {
        $permissions = Permission::all();
        return response()->json([
            'data' => $permissions,
        ], JsonResponse::HTTP_OK);
    }

    public function updateRolePermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Permisos actualizados correctamente',
            'data' => $role->load('permissions'),
        ], JsonResponse::HTTP_OK);
    }
}
