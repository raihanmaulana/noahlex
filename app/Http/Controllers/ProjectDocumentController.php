<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectDocument::where('isDeleted', false);

        if ($request->filled('filename')) {
            $mode = $request->query('filename_mode', 'contains'); // default contains
            $value = $request->query('filename');

            switch (strtolower($mode)) {
                case 'is':
                    $query->where('name', '=', $value);
                    break;
                case 'is not':
                    $query->where('name', '!=', $value);
                    break;
                case 'starts with':
                    $query->where('name', 'like', $value . '%');
                    break;
                case 'ends with':
                    $query->where('name', 'like', '%' . $value);
                    break;
                case 'does not contain':
                    $query->where('name', 'not like', '%' . $value . '%');
                    break;
                case 'is empty':
                    $query->whereNull('name')->orWhere('name', '');
                    break;
                case 'is not empty':
                    $query->whereNotNull('name')->where('name', '!=', '');
                    break;
                default:
                    $query->where('name', 'like', '%' . $value . '%');
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('tags')) {
            $query->where('tags', 'like', '%' . $request->query('tags') . '%');
        }

        if ($request->filled('version')) {
            $query->where('version', $request->query('version'));
        }

        if ($request->filled('sort_by')) {
            $sortField = $request->query('sort_by');
            $sortOrder = $request->query('sort_order', 'asc');
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        $documents = $query->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'name'       => 'required|string',
                'status'     => 'required|string',
                'tags'       => 'nullable|array',
                'version'    => 'nullable|string',
                'document'   => 'required|file|mimes:pdf,xlsx,xls,doc,docx,dwg'
            ]);

            $filePath = $request->file('document')->store('project_documents');

            $document = ProjectDocument::create([
                'project_id'  => $request->project_id,
                'name'        => $request->name,
                'file_path'   => $filePath,
                'status'      => $request->status,
                'tags'        => $request->tags ? json_encode($request->tags) : null,
                'version'     => $request->version,
                'uploaded_by' => auth()->id(),
                'userId'      => auth()->id(),
                'expiry_date' => now()->addDays(30),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'data'    => $document
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {

        DB::beginTransaction();

        try {

            $id = $request->input('id');

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID is required.'
                ], 422);
            }

            $request->validate([
                'id'       => 'required|integer|exists:project_documents,id',
                'name'     => 'sometimes|string',
                'status'   => 'sometimes|string',
                'tags'     => 'nullable|string',
                'version'  => 'nullable|string',
                'document' => 'nullable|file|mimes:pdf,xlsx,xls,doc,docx,dwg'
            ]);

            $document = ProjectDocument::where('isDeleted', false)->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => "Document with ID {$id} not found."
                ], 404);
            }

            if ($request->hasFile('document')) {
                if ($document->file_path && Storage::exists($document->file_path)) {
                    Storage::delete($document->file_path);
                }
                $document->file_path = $request->file('document')->store('project_documents');
            }

            if ($request->has('name')) $document->name = $request->name;
            if ($request->has('status')) $document->status = $request->status;
            if ($request->has('tags')) {
                $tags = is_string($request->tags) ? json_decode($request->tags, true) : $request->tags;
                $document->tags = json_encode($tags);
            }
            if ($request->has('version')) $document->version = $request->version;

            $document->userUpdateId = auth()->id();
            $document->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
                'data'    => $document
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update document.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function detail($id)
    {
        $document = ProjectDocument::where('isDeleted', false)->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    public function destroy(Request $request)
    {
        $document = ProjectDocument::where('isDeleted', false)
            ->find($request->id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document dengan ID ' . $request->id . ' tidak ditemukan.'
            ], 404);
        }

        $document->update([
            'isDeleted'    => true,
            'deletedBy'    => auth()->user()->name,
            'deletedAt'    => now(),
            'userUpdateId' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }

    public function toggleExpiryReminder(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:project_documents,id',
            'reminder_in_app' => 'required|boolean',
            'reminder_email' => 'required|boolean'
        ]);

        $document = ProjectDocument::where('isDeleted', false)->find($request->id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }

        $document->reminder_in_app = $request->reminder_in_app;
        $document->reminder_email = $request->reminder_email;
        $document->userUpdateId = auth()->id();
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Expiry reminder updated successfully.',
            'data' => $document
        ]);
    }
}
