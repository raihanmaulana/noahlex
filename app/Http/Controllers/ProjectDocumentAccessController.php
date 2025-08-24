<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\DB;
use App\Mail\DocumentInvitationMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\ProjectDocumentAccess;

class ProjectDocumentAccessController extends Controller
{
    public function invite(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:project_documents,id',
            'emails' => 'required|string'
        ]);

        $emails = array_map('trim', explode(',', $request->emails));
        $documentId = $request->document_id;

        $addedUsers = [];
        $notFound = [];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $exists = ProjectDocumentAccess::where('document_id', $documentId)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    ProjectDocumentAccess::create([
                        'document_id' => $documentId,
                        'user_id' => $user->id,
                        'role' => null
                    ]);
                    $addedUsers[] = $email;

                    Mail::to($user->email)->send(
                        new DocumentInvitationMail(
                            ProjectDocument::find($documentId)->name,
                            auth()->user()->name
                        )
                    );
                }
            } else {
                $notFound[] = $email;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Invite sent successfully!',
            'added' => $addedUsers,
            'not_found' => $notFound
        ]);
    }

    public function listAccess($documentId)
    {
        $accessList = ProjectDocumentAccess::with('user')
            ->where('document_id', $documentId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accessList
        ]);
    }

    public function revokeAccess($document_id)
    {
        if (!DB::table('project_documents')->where('id', $document_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ], 404);
        }

        $userId = auth()->id();

        $deleted = ProjectDocumentAccess::where('document_id', $document_id)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No access to revoke for this user on the document.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access revoked successfully.'
        ]);
    }
}
