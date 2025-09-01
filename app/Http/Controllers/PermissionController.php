<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();

        return response()->json($permissions);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'  => 'required|string|unique:permissions,name',
                'label' => 'nullable|string',
            ]);

            $permission = Permission::create($request->only(['name', 'label']));

            return response()->json($permission, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload permission.',
                'error'   => app()->environment('production') ? 'Server error' : $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Permission not found',
            ], 404);
        }

        return response()->json($permission);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Permission not found',
            ], 404);
        }

        try {
            $request->validate([
                'name'  => 'required|string|unique:permissions,name,' . $id,
                'label' => 'nullable|string',
            ]);

            $permission->update($request->only(['name', 'label']));

            return response()->json($permission);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Permission not found',
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }

    public function assignToRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Role not found',
            ], 404);
        }

        $request->validate([
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'permissions' => $role->permissions,
        ]);
    }

    public function getByRole($roleId)
    {
        $role = Role::with('permissions')->find($roleId);

        if (!$role) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json($role->permissions);
    }
}
