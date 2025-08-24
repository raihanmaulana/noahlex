<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FolderTemplate;

class FolderTemplateController extends Controller
{
    public function index()
    {
        $templates = FolderTemplate::where('isDeleted', false)->get();

        return response()->json([
            'success' => true,
            'message' => 'Folder template list retrieved successfully.',
            'data' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'templates' => 'required|array|min:1',
            'templates.*.name' => 'required|string',
            'templates.*.path' => 'required|string',
            'templates.*.parent_index' => 'nullable|integer'
        ]);

        $userId = auth()->id();
        $templateMap = []; // index input => id DB

        $created = [];

        foreach ($request->templates as $index => $template) {
            $parentId = null;

            if (isset($template['parent_index']) && isset($templateMap[$template['parent_index']])) {
                $parentId = $templateMap[$template['parent_index']];
            }

            $newTemplate = FolderTemplate::create([
                'name' => $template['name'],
                'path' => $template['path'],
                'parent_id' => $parentId,
                'sort_order' => $template['sort_order'] ?? null,
                'userId' => $userId,
                'isDeleted' => false
            ]);

            $templateMap[$index] = $newTemplate->id;
            $created[] = $newTemplate;
        }

        return response()->json([
            'success' => true,
            'message' => 'Several folder templates were successfully created.',
            'data' => $created
        ]);
    }

    public function storeSingle(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'path' => 'required|string',
            'parent_id' => 'nullable|exists:folder_templates,id'
        ]);

        $template = FolderTemplate::create([
            'name' => $request->name,
            'path' => $request->path,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? null,
            'userId' => auth()->id(),
            'isDeleted' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder template was successfully added.',
            'data' => $template
        ]);
    }

    public function updateSingle(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:folder_templates,id',
            'name' => 'required|string',
            'path' => 'required|string',
            'parent_id' => 'nullable|exists:folder_templates,id'
        ]);

        $template = FolderTemplate::where('isDeleted', false)->find($request->id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Folder template dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $template->update([
            'name' => $request->name,
            'path' => $request->path,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? null,
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder template was successfully updated.',
            'data' => $template
        ]);
    }

    public function detail($id)
    {
        $template = FolderTemplate::where('isDeleted', false)->find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Folder template dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'templates' => 'required|array|min:1',
            'templates.*.id' => 'required|exists:folder_templates,id',
            'templates.*.name' => 'required|string',
            'templates.*.path' => 'required|string',
            'templates.*.parent_id' => 'nullable|exists:folder_templates,id'
        ]);

        foreach ($request->templates as $template) {
            FolderTemplate::where('id', $template['id'])
                ->where('isDeleted', false)
                ->update([
                    'name' => $template['name'],
                    'path' => $template['path'],
                    'parent_id' => $template['parent_id'] ?? null,
                    'userUpdateId' => auth()->id()
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Folder templates were successfully updated.'
        ]);
    }


    public function destroy($id)
    {
        $template = FolderTemplate::where('isDeleted', false)
            ->where('id', $id)
            ->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Folder template with ID ' . $id . ' was not found.'
            ], 404);
        }
        
        $this->softDeleteRecursively($template);

        return response()->json([
            'success' => true,
            'message' => 'Folder template (along with its child folders) was successfully deleted.'
        ]);
    }

    private function softDeleteRecursively($template)
    {
        $template->update([
            'isDeleted' => true,
            'deletedBy' => auth()->user()->name,
            'deletedAt' => now(),
            'userUpdateId' => auth()->id()
        ]);

        $children = FolderTemplate::where('parent_id', $template->id)->where('isDeleted', false)->get();

        foreach ($children as $child) {
            $this->softDeleteRecursively($child);
        }
    }
}
