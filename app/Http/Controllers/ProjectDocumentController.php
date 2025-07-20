<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectDocument;

class ProjectDocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'document' => 'required|file|mimes:pdf,xlsx,xls,doc,docx,dwg',
            'status' => 'required|string',
            'tags' => 'nullable|array',
            'version' => 'nullable|string',
        ]);

        $file = $request->file('document');
        $filePath = $file->store('project_documents');

        $document = ProjectDocument::create([
            'project_id' => $request->project_id,
            'name' => $request->name,
            'file_path' => $filePath,
            'status' => $request->status,
            'tags' => $request->tags ? json_encode($request->tags) : null,
            'version' => $request->version,
            'uploaded_by' => auth()->id(),
            'userId' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ]);
    }
}
