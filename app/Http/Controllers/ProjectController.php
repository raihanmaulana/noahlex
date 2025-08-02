<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ProjectFolder;
use App\Models\FolderTemplate;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_only')->only('index');
        $this->middleware('permission:upload_edit')->only('store');
        $this->middleware('permission:upload_edit')->only('update');
    }

    public function index()
    {
        $projects = Project::with([
            'type',
            'status',
            'manager',
            'metadata.disciplineUser',
            'assignments.user',
            'assignments.role',
            'folders'
        ])
            ->where('isDeleted', false)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List project berhasil diambil.',
            'data' => $projects
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Step 1: Buat data project utama
            $project = Project::create([
                'name' => $request->name,
                'type_id' => $request->type_id,
                'category' => $request->category,
                'location' => $request->location,
                'date' => $request->date,
                'status_id' => $request->status_id,
                'size' => $request->size,
                'enable_workflow' => $request->enable_workflow,
                'project_manager_id' => $request->project_manager_id,
                'userId' => auth()->id(),
            ]);

            // Step 2: Metadata
            $project->metadata()->create([
                'document_type' => $request->metadata['document_type'],
                'revision_limit' => $request->metadata['revision_limit'],
                'discipline' => $request->metadata['discipline'], // sudah user_id
                'userId' => auth()->id(),
            ]);

            // Step 3: Assignment user (optional)
            if (!empty($request->assignments)) {
                foreach ($request->assignments as $assign) {
                    $project->assignments()->create([
                        'user_id' => $assign['user_id'],
                        'role_id' => $assign['role_id'],
                        'userId' => auth()->id(),
                    ]);
                }
            }

            // Step 4: Folder setup (template 1 = 4 folder static)
            if ($request->folder_mode === 'template' && $request->has('folder_template_id')) {
                // Ambil folder template dari DB
                $templates = FolderTemplate::where('parent_id', null)
                    ->where('id', $request->folder_template_id)
                    ->get();

                foreach ($templates as $template) {

                    $this->createFoldersFromTemplate($template, $project->id, null);
                }
            } elseif ($request->folder_mode === 'custom' && !empty($request->folders)) {
                foreach ($request->folders as $folder) {
                    $project->folders()->create([
                        'name' => $folder['name'],
                        'path' => $folder['path'],
                        'parent_id' => $folder['parent_id'] ?? null,
                        'userId' => auth()->id(),
                    ]);
                }
            }


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.',
                'data' => $project->load('metadata', 'assignments', 'folders')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createFoldersFromTemplate($template, $projectId, $parentId = null)
    {
        $folder = ProjectFolder::create([
            'project_id' => $projectId,
            'name' => $template->name,
            'path' => $template->path,
            'parent_id' => $parentId,
            'userId' => auth()->id(),
        ]);

        foreach ($template->children as $child) {
            $this->createFoldersFromTemplate($child, $projectId, $folder->id);
        }
    }

    public function detail($id)
    {
        $project = Project::with([
            'metadata.disciplineUser',
            'assignments.user',
            'assignments.role',
            'folders'
        ])->where('isDeleted', false)->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }


    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $project = Project::where('isDeleted', false)->find($request->id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project dengan ID ' . $request->id . ' tidak ditemukan.'
                ], 404);
            }

            // Update utama
            $project->update([
                'name' => $request->name,
                'type_id' => $request->type_id,
                'category' => $request->category,
                'location' => $request->location,
                'date' => $request->date,
                'status_id' => $request->status_id,
                'size' => $request->size,
                'enable_workflow' => $request->enable_workflow,
                'project_manager_id' => $request->project_manager_id,
                'userUpdateId' => auth()->id(),
            ]);

            // Update metadata
            $project->metadata()->update([
                'document_type' => $request->metadata['document_type'],
                'revision_limit' => $request->metadata['revision_limit'],
                'discipline' => $request->metadata['discipline'],
                'userUpdateId' => auth()->id(),
            ]);

            // Replace assignments
            $project->assignments()->delete();
            if (!empty($request->assignments)) {
                foreach ($request->assignments as $assign) {
                    $project->assignments()->create([
                        'user_id' => $assign['user_id'],
                        'role_id' => $assign['role_id'],
                        'userId' => auth()->id(),
                    ]);
                }
            }

            // Replace folders (custom with index-based parent mapping)
            if ($request->folder_mode === 'custom' && !empty($request->folders)) {
                // Hapus lama (anak dulu)
                ProjectFolder::where('project_id', $project->id)
                    ->orderByDesc('parent_id')
                    ->delete();

                // Mapping index input -> id DB baru
                $folderIdMap = [];

                foreach ($request->folders as $index => $folder) {
                    $parentId = null;
                    if (isset($folder['parent_id']) && isset($folderIdMap[$folder['parent_id']])) {
                        $parentId = $folderIdMap[$folder['parent_id']];
                    }

                    $newFolder = $project->folders()->create([
                        'name' => $folder['name'],
                        'path' => $folder['path'],
                        'parent_id' => $parentId,
                        'userId' => auth()->id(),
                    ]);

                    $folderIdMap[$index] = $newFolder->id;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.',
                'data' => $project->load('metadata', 'assignments', 'folders')
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $project = Project::where('isDeleted', false)->findOrFail($request->id);

        $project->update([
            'isDeleted' => true,
            'deletedBy' => auth()->user()->name,
            'deletedAt' => now(),
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.'
        ]);
    }
}
