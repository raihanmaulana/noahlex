<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectAssignment;
use App\Models\ProjectAssignmentType;

class ProjectAssignmentController extends Controller
{
    public function index()
    {
        $types = ProjectAssignmentType::where('isDeleted', false)->get();

        return response()->json([
            'success' => true,
            'message' => 'List assignment types berhasil diambil.',
            'data' => $types
        ]);
    }

    public function bulkAssignUsersToProjects(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_id' => 'required|exists:roles,id',
            'project_ids' => 'required|array|min:1',
            'project_ids.*' => 'exists:projects,id'
        ]);

        $inserted = 0;

        foreach ($request->project_ids as $projectId) {
            foreach ($request->assignments as $assign) {
                $exists = ProjectAssignment::where([
                    'project_id' => $projectId,
                    'user_id' => $assign['user_id'],
                    'role_id' => $assign['role_id'],
                    'isDeleted' => false
                ])->exists();

                if (!$exists) {
                    ProjectAssignment::create([
                        'project_id' => $projectId,
                        'user_id' => $assign['user_id'],
                        'role_id' => $assign['role_id'],
                        'userId' => auth()->id()
                    ]);
                    $inserted++;
                }
            }
        }

        sendNotification(
            auth()->id(),
            "Bulk Assignment Complete",
            "Successfully assigned " . count($request->assignments) . " people into " . count($request->project_ids) . " project(s)."
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned " .
                count($request->assignments) . " people into " .
                count($request->project_ids) . " project(s).",
            'assigned_count' => $inserted
        ]);
    }
}
