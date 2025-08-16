<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentActivityLog;
use Illuminate\Support\Facades\Log;
use App\Notifications\DocumentApproved;
use App\Notifications\DocumentRejected;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentController extends Controller
{
    //add validation
    public function __construct()
    {
        $this->middleware('permission:view_only')->only('index');
        $this->middleware('permission:upload_edit')->only('store');
        $this->middleware('permission:upload_edit')->only('update');
    }

    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = ProjectDocument::where('isDeleted', false)
            ->where(function ($q) use ($userId) {
                $q->where('uploaded_by', $userId)
                    ->orWhereIn('id', function ($sub) use ($userId) {
                        $sub->select('document_id')
                            ->from('project_document_accesses')
                            ->where('user_id', $userId);
                    });
            });

        if ($request->filled('filename')) {
            $mode = strtolower($request->query('filename_mode', 'contains'));
            $value = $request->query('filename');

            switch ($mode) {
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
                    $query->where(function ($qq) {
                        $qq->whereNull('name')->orWhere('name', '');
                    });
                    break;
                case 'is not empty':
                    $query->whereNotNull('name')->where('name', '!=', '');
                    break;
                default:
                    $query->where('name', 'like', '%' . $value . '%');
            }
        }

        if ($request->filled('status_id'))   $query->where('status_id', $request->query('status_id'));
        if ($request->filled('project_id'))  $query->where('project_id', $request->query('project_id'));
        if ($request->filled('tags'))        $query->whereJsonContains('tags', $request->query('tags'));
        if ($request->filled('version'))     $query->where('version', $request->query('version'));
        if ($request->filled('vendor_id'))   $query->where('vendor_id', $request->query('vendor_id'));

        $allowedSort = ['name', 'version', 'created_at', 'updated_at', 'status_id', 'project_id', 'vendor_id'];
        $sortField   = $request->query('sort_by');
        $sortOrder   = $request->query('sort_order', 'asc');
        if ($sortField && in_array($sortField, $allowedSort, true)) {
            $query->orderBy($sortField, $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderByDesc('created_at');
        }

        $perPage = min($request->integer('per_page', 25), 100);
        return response()->json(['success' => true, 'data' => $query->paginate($perPage)]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'project_id'     => 'required|exists:projects,id',
                'vendor_id'      => 'nullable|exists:vendors,id',
                'name'           => 'required|string',
                'status_id'      => 'nullable|string',
                'tags'           => 'nullable|array',
                'revision_notes' => 'nullable|string',
                'document'       => 'required|file|mimes:pdf,xlsx,xls,doc,docx|max:10240',
            ]);

            $vendorId = auth()->user()->vendor_id;
            $projectId = $request->project_id;
            $file = $request->file('document');

            // Path utama
            $mainPath = "vendor_{$vendorId}/project_{$projectId}";
            $filePath = $file->store($mainPath, 'documents');

            // Path backup
            $backupPath = "backup/vendor_{$vendorId}/project_{$projectId}";
            Storage::disk('backup_documents')->putFileAs(
                $backupPath,
                $file,
                $file->getClientOriginalName()
            );

            $document = ProjectDocument::create([
                'project_id'        => $request->project_id,
                'vendor_id'         => $vendorId,
                'document_group_id' => (string) Str::uuid(),
                'name'              => $request->name,
                'status_id'         => $request->status_id,
                'tags'              => $request->tags,
                'version'           => $request->version ?? 1,
                'revision_notes'    => $request->revision_notes ?? null,
                'file_path'         => $filePath,
                'original_name'     => $file->getClientOriginalName(),
                'mime_type'         => $file->getMimeType(),
                'size_bytes'        => $file->getSize(),
                'uploaded_by'       => auth()->id(),
                'userId'            => auth()->id(),
                'expiry_date'       => now()->addDays(30),
            ]);

            // (opsional) checksum
            // $checksumSha256 = hash_file('sha256', $file->getRealPath());

            DocumentActivityLog::create([
                'document_id' => $document->id,
                'user_id'     => auth()->id(),
                'action'      => 'upload',
                'metadata'    => [
                    'project_id'     => $request->project_id,
                    'vendor_id'      => $vendorId,
                    'status_id'      => $request->status_id,
                    'tags'           => $request->tags ?? [],
                    'revision_notes' => $request->revision_notes ?? null,
                    'path'           => $filePath,
                    // 'checksum_sha256'=> $checksumSha256,
                ],
            ]);

            $this->logToObservability('notice', 'Document uploaded', [
                'document_id' => $document->id,
                'project_id'  => $document->project_id,
                'vendor_id'   => $document->vendor_id,
                'version'     => $document->version,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully (v1).',
                'data'    => $document
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logToObservability('error', 'Document upload failed', [
                'project_id' => $request->project_id ?? null,
                'name'       => $request->name ?? null,
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document.',
                'error'   => app()->environment('production') ? 'Server error' : $e->getMessage()
            ], 500);
        }
    }


    public function storeVersion(Request $request, string $groupId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'project_id'     => 'required|exists:projects,id',
                'name'           => 'required|string',
                'status_id'      => 'nullable|string',
                'tags'           => 'nullable|array',
                'revision_notes' => 'nullable|string',
                'document'       => 'required|file|mimes:pdf,xlsx,xls,doc,docx|max:10240',
            ]);

            $latest = ProjectDocument::where('document_group_id', $groupId)
                ->where('isDeleted', false)
                ->orderByDesc('version')
                ->first();

            if (!$latest) {
                return response()->json(['success' => false, 'message' => 'Document group not found.'], 404);
            }

            if ((int)$latest->project_id !== (int)$request->project_id) {
                return response()->json(['success' => false, 'message' => 'Project mismatch for this document group.'], 422);
            }

            $nextVersion = ((int) $latest->version) + 1;

            $vendorId = auth()->user()->vendor_id;
            $projectId = $latest->project_id;
            $file = $request->file('document');


            $mainPath = "vendor_{$vendorId}/project_{$projectId}";
            $filePath = $file->store($mainPath, 'documents');


            $backupPath = "backup/vendor_{$vendorId}/project_{$projectId}";
            Storage::disk('backup_documents')->putFileAs(
                $backupPath,
                $file,
                $file->getClientOriginalName()
            );

            $newVersion = ProjectDocument::create([
                'project_id'        => $projectId,
                'vendor_id'         => $vendorId,
                'document_group_id' => $groupId,
                'name'              => $request->name,
                'status_id'         => $request->status_id ?? $latest->status_id,
                'tags'              => $request->tags ?? $latest->tags,
                'version'           => $nextVersion,
                'revision_notes'    => $request->revision_notes ?? null,
                'file_path'         => $filePath,
                'original_name'     => $file->getClientOriginalName(),
                'mime_type'         => $file->getMimeType(),
                'size_bytes'        => $file->getSize(),
                'uploaded_by'       => auth()->id(),
                'userId'            => auth()->id(),
                'expiry_date'       => now()->addDays(30),
            ]);

            $this->logToObservability('notice', 'Document new version uploaded', [
                'group_id'    => $groupId,
                'document_id' => $newVersion->id,
                'project_id'  => $projectId,
                'version'     => $nextVersion,
            ]);

            DB::commit();


            DocumentActivityLog::create([
                'document_id'    => $newVersion->id,
                'user_id'        => auth()->id(),
                'action'         => 'store new version',
                'metadata'       => [
                    'tags'           => $newVersion->tags ?? [],
                    'status'         => $newVersion->status_id ?? null,
                    'revision_notes' => $request->revision_notes ?? null
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => "New version uploaded (v{$nextVersion}) with backup.",
                'data'    => $newVersion
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logToObservability('error', 'Document new version failed', [
                'group_id'   => $groupId,
                'project_id' => $request->project_id ?? null,
                'name'       => $request->name ?? null,
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload new version.',
                'error'   => app()->environment('production') ? 'Server error' : $e->getMessage()
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
                'document' => 'nullable|file|mimes:pdf,xlsx,xls,doc,docx,dwg|max:10240',
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
                $request->file('document')->store('', 'documents');
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

            $this->logToObservability('info', 'Document updated', [
                'document_id' => $document->id,
                'changed'     => [
                    'name'       => $request->has('name'),
                    'status_id'  => $request->has('status_id') || $request->has('status'),
                    'tags'       => $request->has('tags'),
                    'version'    => $request->has('version'),
                    'file'       => $request->hasFile('document'),
                ]
            ]);

            DB::commit();


            DocumentActivityLog::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'action' => 'update',
                'metadata' => [
                    'tags' => $document->tags ?? [],
                    'status' => $document->status ?? null,
                    'revision_notes' => $request->revision_notes ?? null
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
                'data'    => $document
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logToObservability('error', 'Document update failed', [
                'id' => $request->input('id'),
            ], $e);

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

    public function destroy($id)
    {
        $document = ProjectDocument::where('isDeleted', false)->find($id);
        $userId = auth()->id();

        if (!$document) {

            $this->logToObservability('warning', 'Document not found on delete', [
                'document_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dokumen dengan ID ' . $id . ' tidak ditemukan.'
            ], 404);
        }

        if (!$this->userCanAccessDoc($document, $userId)) {
            return $this->denyAndLog('Forbidden', ['document_id' => $document->id]);
        }

        DB::beginTransaction();

        try {
            $document->isDeleted = true;
            $document->save();

            DocumentActivityLog::create([
                'document_id' => $document->id,
                'user_id'     => $userId,
                'action'      => 'soft delete',
                'metadata'    => ['reason' => 'Document soft deleted.']
            ]);

            DB::commit();

            $this->logToObservability('notice', 'Document soft-deleted', [
                'document_id' => $document->id,
                'project_id'  => $document->project_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document successfully deleted (soft delete).'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();


            $this->logToObservability('error', 'Document soft delete failed', [
                'document_id' => $id,
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document.',
                'error'   => app()->environment('production') ? 'Server error' : $e->getMessage()
            ], 500);
        }
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

    public function approveDocument(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:project_documents,id'
        ]);

        $user = auth()->user();

        if (!in_array($user->role_id, [1, 2])) {
            return $this->denyAndLog('You do not have permission to approve documents.', [
                'document_id' => $request->id
            ]);
        }

        $document = ProjectDocument::where('isDeleted', false)->findOrFail($request->id);

        $document->update([
            'status_id' => 1,
            'userUpdateId' => $user->id
        ]);

        $uploader = User::find($document->uploaded_by);


        if ($uploader) {
            try {
                $uploader->notify(new DocumentApproved($document));
            } catch (\Exception $e) {

                Log::error('Failed to send DocumentApproved notification.', [
                    'user_id' => $uploader->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logToObservability('notice', 'Document approved', [
            'document_id' => $document->id,
            'project_id'  => $document->project_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Document: {$document->name} approved"
        ]);
    }

    public function rejectDocument(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:project_documents,id'
        ]);

        $user = auth()->user();

        if (!in_array($user->role_id, [1, 2])) {
            return $this->denyAndLog('You do not have permission to approve documents.', [
                'document_id' => $request->id
            ]);
        }

        $document = ProjectDocument::where('isDeleted', false)->findOrFail($request->id);

        $document->update([
            'status_id' => 2,
            'userUpdateId' => $user->id
        ]);

        $uploader = User::find($document->uploaded_by);

        
        if ($uploader) {
            try {
                $uploader->notify(new DocumentRejected($document));
            } catch (\Exception $e) {
                
                Log::error('Failed to send DocumentRejected notification.', [
                    'user_id' => $uploader->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }
        }


        $this->logToObservability('notice', 'Document rejected', [
            'document_id' => $document->id,
            'project_id'  => $document->project_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Document: {$document->name} rejected"
        ]);
    }

    private function userCanAccessDoc($doc, $userId): bool
    {

        if ((int)$doc->uploaded_by === (int)$userId) return true;

        return DB::table('project_document_accesses')
            ->where('document_id', $doc->id)
            ->where('user_id', $userId)
            ->exists();
    }

    public function download($id)
    {
        $userId = auth()->id();

        $doc = ProjectDocument::where('isDeleted', false)->find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }


        if (isset($doc->expiry_date) && $doc->expiry_date && now()->greaterThan($doc->expiry_date)) {
            return response()->json(['success' => false, 'message' => 'Document expired.'], 403);
        }

        if (!$this->userCanAccessDoc($doc, $userId)) {
            return $this->denyAndLog('Forbidden', ['document_id' => $doc->id]);
        }

        $disk = 'documents';
        if (!$doc->file_path || !Storage::disk($disk)->exists($doc->file_path)) {
            return response()->json(['success' => false, 'message' => 'File not found on storage.'], 404);
        }

        DocumentActivityLog::create([
            'document_id' => $doc->id,
            'user_id' => auth()->id(),
            'action' => 'download',
            'metadata' => []
        ]);

        $this->logToObservability('info', 'Document downloaded', [
            'document_id' => $doc->id,
            'project_id'  => $doc->project_id,
        ]);

        $downloadName = $doc->original_name ?? $doc->name;


        return Storage::disk($disk)->download($doc->file_path, $downloadName);
    }

    public function preview($id)
    {
        $userId = auth()->id();

        $doc = ProjectDocument::where('isDeleted', false)->find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        if (isset($doc->expiry_date) && $doc->expiry_date && now()->greaterThan($doc->expiry_date)) {
            return response()->json(['success' => false, 'message' => 'Document expired.'], 403);
        }

        // if (!$this->userCanAccessDoc($doc, $userId)) {
        //     return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        // }

        if (!$this->userCanAccessDoc($doc, $userId)) {
            return $this->denyAndLog('Forbidden', ['document_id' => $doc->id]);
        }

        $disk = 'documents';
        if (!$doc->file_path || !Storage::disk($disk)->exists($doc->file_path)) {
            return response()->json(['success' => false, 'message' => 'File not found on storage.'], 404);
        }

        $fullPath = Storage::disk($disk)->path($doc->file_path);


        $mime = Storage::disk($disk)->mimeType($doc->file_path) ?: 'application/octet-stream';
        $filename = $doc->original_name ?? $doc->name;

        $this->logToObservability('info', 'Document previewed', [
            'document_id' => $doc->id,
            'project_id'  => $doc->project_id,
            'mime'        => $mime,
        ]);

        return response()->file($fullPath, [
            'Content-Type'             => $mime,
            'Content-Disposition'      => 'inline; filename="' . addslashes($filename) . '"',
            'X-Content-Type-Options'   => 'nosniff',
        ]);
    }

    public function listVersions(string $groupId)
    {
        $docs = ProjectDocument::where('document_group_id', $groupId)
            ->where('isDeleted', false)
            ->orderByDesc('version')
            ->get();

        if ($docs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No versions found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $docs]);
    }

    public function activityLog($documentId)
    {
        $logs = DocumentActivityLog::with('user')
            ->where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function restore($id)
    {
        $doc = ProjectDocument::find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }


        if (!$this->userCanAccessDoc($doc, auth()->id())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $mainDisk   = 'documents';
        $backupDisk = 'backup_documents';


        if ($doc->file_path && Storage::disk($mainDisk)->exists($doc->file_path) && !request('force')) {
            return response()->json([
                'success' => true,
                'message' => 'File already present on primary storage. Use ?force=1 to overwrite.'
            ]);
        }



        $vendorId  = $doc->vendor_id ?? optional($doc->uploader)->vendor_id;
        $projectId = $doc->project_id;
        $backupDir = "backup/vendor_{$vendorId}/project_{$projectId}";


        $backupCandidates = [];
        if (!empty($doc->original_name)) {
            $backupCandidates[] = "{$backupDir}/{$doc->original_name}";
        }

        if (empty($doc->original_name) || !Storage::disk($backupDisk)->exists(end($backupCandidates))) {
            $files = Storage::disk($backupDisk)->files($backupDir);

            usort($files, fn($a, $b) => strcmp($b, $a));
            $backupCandidates = array_merge($backupCandidates, $files);
        }


        $sourceBackupPath = null;
        foreach ($backupCandidates as $cand) {
            if (Storage::disk($backupDisk)->exists($cand)) {
                $sourceBackupPath = $cand;
                break;
            }
        }
        if (!$sourceBackupPath) {
            return response()->json(['success' => false, 'message' => 'Backup file not found.'], 404);
        }


        $basePath  = $vendorId
            ? "vendor_{$vendorId}/project_{$projectId}"
            : "project_{$projectId}";
        $datedPath = $basePath . '/' . now()->format('Y/m');


        $targetName = $doc->original_name ?: basename($sourceBackupPath);
        $targetPath = "{$datedPath}/{$targetName}";


        if (request('force') && $doc->file_path && Storage::disk($mainDisk)->exists($doc->file_path)) {
            Storage::disk($mainDisk)->delete($doc->file_path);
        }


        $stream = Storage::disk($backupDisk)->readStream($sourceBackupPath);
        if (!$stream) {
            return response()->json(['success' => false, 'message' => 'Failed to read backup stream.'], 500);
        }
        Storage::disk($mainDisk)->put($targetPath, $stream);


        $doc->file_path = $targetPath;

        $doc->size_bytes = Storage::disk($mainDisk)->size($targetPath) ?: $doc->size_bytes;
        $doc->mime_type  = Storage::disk($mainDisk)->mimeType($targetPath) ?: $doc->mime_type;
        $doc->userUpdateId = auth()->id();
        $doc->save();


        DocumentActivityLog::create([
            'document_id' => $doc->id,
            'user_id'     => auth()->id(),
            'action'      => 'restore',
            'metadata'    => [
                'from'  => $sourceBackupPath,
                'to'    => $targetPath,
                'force' => (bool) request('force'),
            ]
        ]);

        $this->logToObservability('notice', 'Document restored from backup', [
            'document_id' => $doc->id,
            'from'        => $sourceBackupPath,
            'to'          => $targetPath,
            'forced'      => (bool) request('force'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document restored from backup.',
            'data'    => ['file_path' => $doc->file_path]
        ]);
    }

    private function denyAndLog(string $reason, array $ctx = [])
    {
        Log::warning('Document access denied: ' . $reason, $ctx + ['user_id' => auth()->id()]);
        return response()->json(['success' => false, 'message' => $reason], 403);
    }

    private function logToObservability(string $level, string $message, array $context = [], \Throwable $e = null): void
    {
        $context = array_merge([
            'user_id' => auth()->id(),
            'route'   => request()->path(),
            'method'  => request()->method(),
            'ip'      => request()->ip(),
        ], $context);

        if (method_exists(Log::class, $level)) {
            Log::$level($message, $context);
        } else {
            Log::info($message, $context);
        }

        if ($e && app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    }
}
