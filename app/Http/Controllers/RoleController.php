<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{

    // public function __construct()
    // {
    //     $this->middleware('permission:view_only')->only('index');
    //     $this->middleware('permission:upload_edit')->only('store');
    //     $this->middleware('permission:upload_edit')->only('update');
    // }

    public function index()
    {
        return response()->json(Role::all());
    }

    public function detail($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'userId' => auth()->id(),
        ]);

        return response()->json($role, 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|unique:roles,name,' . $request->id,
        ]);

        $role = Role::find($request->id);
        $role->update([
            'name' => $request->name,
            'userUpdateId' => auth()->id(),
        ]);

        return response()->json($role);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:roles,id',
        ]);

        $role = Role::find($request->id);
        $role->update([
            'isDeleted' => true,
            'deletedBy' => auth()->user()->name,
            'deletedAt' => now(),
            'userUpdateId' => auth()->id(),
        ]);

        return response()->json(['message' => 'Role marked as deleted']);
    }

    public function updatePermission(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission' => 'required|array',
            'permission.*' => 'exists:permissions,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $role->permissions()->sync($validated['permission']);

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'role_id' => $role->id,
            'permissions' => $validated['permission'],
        ]);
    }
}
