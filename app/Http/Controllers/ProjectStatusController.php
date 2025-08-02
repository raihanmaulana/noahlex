<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectStatus;

class ProjectStatusController extends Controller
{
    public function index()
    {
        $statuses = ProjectStatus::where('isDeleted', false)->get();

        return response()->json([
            'success' => true,
            'message' => 'List project status berhasil diambil.',
            'data' => $statuses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:project_statuses,name'
        ]);

        $status = ProjectStatus::create([
            'name' => $request->name,
            'userId' => auth()->id(),
            'isDeleted' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project status berhasil ditambahkan.',
            'data' => $status
        ]);
    }

    public function detail($id)
    {
        $status = ProjectStatus::where('isDeleted', false)->find($id);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Project status dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }


    public function update(Request $request)
    {
        $status = ProjectStatus::where('isDeleted', false)->find($request->id);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Project status dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|unique:project_statuses,name,' . $status->id
        ]);

        $status->update([
            'name' => $request->name,
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project status berhasil diperbarui.',
            'data' => $status
        ]);
    }

    public function destroy(Request $request)
    {
        $status = ProjectStatus::where('isDeleted', false)->find($request->id);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Project status dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $status->update([
            'isDeleted' => true,
            'deletedBy' => auth()->user()->name,
            'deletedAt' => now(),
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project status berhasil dihapus.'
        ]);
    }
}
