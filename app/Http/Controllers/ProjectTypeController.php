<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectType;

class ProjectTypeController extends Controller
{
    public function index()
    {
        $types = ProjectType::where('isDeleted', false)->get();

        return response()->json([
            'success' => true,
            'message' => 'Project types list retrieved successfully.',
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:project_types,name'
        ]);

        $type = ProjectType::create([
            'name' => $request->name,
            'userId' => auth()->id(),
            'isDeleted' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project type was successfully added.',
            'data' => $type
        ]);
    }

    public function detail($id)
    {
        $type = ProjectType::where('isDeleted', false)->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $type
        ]);
    }

    public function update(Request $request)
    {
        $type = ProjectType::where('isDeleted', false)->find($request->id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|unique:project_types,name,' . $type->id
        ]);

        $type->update([
            'name' => $request->name,
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project type was successfully updated.',
            'data' => $type
        ]);
    }


    public function destroy($id)
    {
        $type = ProjectType::where('isDeleted', false)
            ->where('id', $id)
            ->first();

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Project type dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        $type->update([
            'isDeleted'    => true,
            'deletedBy'    => auth()->user()->name,
            'deletedAt'    => now(),
            'userUpdateId' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project type was successfully deleted.'
        ]);
    }
}
