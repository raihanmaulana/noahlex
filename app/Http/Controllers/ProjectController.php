<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['type', 'status', 'manager'])->where('isDeleted', false)->get();
        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type_id' => 'required|exists:project_types,id',
            'location' => 'required|string',
            'date' => 'required|date',
            'status_id' => 'required|exists:project_statuses,id',
            'size' => 'nullable|string',
            'project_manager_id' => 'nullable|exists:users,id',
            'enable_workflow' => 'nullable|boolean',
        ]);

        $validated['userId'] = auth()->id();

        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    public function detail(Request $request)
    {
        $request->validate(['id' => 'required|exists:projects,id']);

        $project = Project::with(['type', 'status', 'manager'])->findOrFail($request->id);
        return response()->json($project);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'type_id' => 'required|exists:project_types,id',
            'location' => 'required|string',
            'date' => 'required|date',
            'status_id' => 'required|exists:project_statuses,id',
            'project_size' => 'nullable|string',
            'manager_user_id' => 'nullable|exists:users,id',
            'enable_workflow_logs' => 'nullable|boolean',
        ]);

        $project = Project::findOrFail($validated['id']);
        $validated['userUpdateId'] = auth()->id();
        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|exists:projects,id']);

        $project = Project::findOrFail($request->id);
        $project->update([
            'isDeleted' => true,
            'deletedBy' => auth()->user()->name,
            'deletedAt' => now(),
        ]);

        return response()->json(['message' => 'Project marked as deleted']);
    }
}
