<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocumentAccess;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\DocumentInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

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
            'message' => 'Invite process completed.',
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

    public function revokeAccess(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:project_documents,id',
            'user_id' => 'required|exists:users,id'
        ]);

        ProjectDocumentAccess::where('document_id', $request->document_id)
            ->where('user_id', $request->user_id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Access revoked successfully.'
        ]);
    }
}
