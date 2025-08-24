<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocumentComment;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectDocumentCommentController extends Controller
{
    public function index($documentId)
    {
        $comments = ProjectDocumentComment::with('user')
            ->where('document_id', $documentId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:project_documents,id',
            'comment' => 'required|string'
        ]);

        $comment = ProjectDocumentComment::create([
            'document_id' => $request->document_id,
            'user_id' => Auth::id(),
            'comment' => $request->comment
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => $comment->load('user')
        ]);
    }

    public function destroy($id)
    {
        $comment = ProjectDocumentComment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.'
            ], 404);
        }

        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this comment.'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.'
        ]);
    }
}
