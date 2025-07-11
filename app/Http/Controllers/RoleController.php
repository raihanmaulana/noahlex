<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{

    public function index()
    {
        return response()->json(Role::all());
    }

    public function detail(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $role = Role::find($request->id);
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
}
