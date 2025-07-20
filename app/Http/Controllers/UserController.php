<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use App\Imports\UserPreviewImport;
use App\Exports\UserTemplateExport;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereNull('deletedAt')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar user berhasil diambil.',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'company_name' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'userId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan.',
            'data' => $user
        ]);
    }

    public function detail(Request $request)
    {
        $user = User::whereNull('deletedAt')->find($request->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = User::whereNull('deletedAt')->find($request->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'company_name' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|min:6'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'role_id' => $request->role_id,
            'userUpdateId' => auth()->id(),
            'password' => $request->password
                ? Hash::make($request->password)
                : $user->password
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'data' => $user
        ]);
    }

    public function destroy(Request $request)
    {
        $user = User::whereNull('deletedAt')->find($request->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $user->update([
            'deletedAt' => now(),
            'deletedBy' => auth()->user()->name,
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus (soft delete).'
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new UserTemplateExport, 'user_template.xlsx');
    }

    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $import = new UserPreviewImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'success' => true,
            'valid_rows' => $import->validRows,
            'invalid_rows' => $import->invalidRows,
        ]);
    }


    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        Excel::import(new UsersImport, $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'User import berhasil.'
        ]);
    }
}
