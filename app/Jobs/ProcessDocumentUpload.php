<?php

namespace App\Jobs;

use Throwable;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentActivityLog;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\DocumentUploadFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\DocumentUploadSuccess;

class ProcessDocumentUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, int $userId)
    {
        $this->data = $data;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $user = User::find($this->userId);
            if (!$user) {
                throw new \Exception("User with ID {$this->userId} not found.");
            }

            $vendorId = $user->vendor_id;
            $projectId = $this->data['project_id'];
            $tempPath = $this->data['temp_path']; // Path file sementara

            // 1. Pindahkan file dari temp ke storage utama
            $mainPath = "vendor_{$vendorId}/project_{$projectId}";
            $finalFilePath = Storage::disk('documents')->putFile($mainPath, storage_path('app/' . $tempPath));

            // 2. Salin file ke storage backup
            $backupPath = "backup/vendor_{$vendorId}/project_{$projectId}";
            Storage::disk('backup_documents')->putFileAs(
                $backupPath,
                storage_path('app/' . $tempPath),
                $this->data['original_name']
            );

            // 3. Hapus file sementara
            Storage::disk('local')->delete($tempPath);

            // 4. Buat record di database
            $document = ProjectDocument::create([
                'project_id'        => $projectId,
                'vendor_id'         => $vendorId,
                'document_group_id' => (string) Str::uuid(),
                'name'              => $this->data['name'],
                'status_id'         => $this->data['status_id'],
                'tags'              => $this->data['tags'],
                'version'           => 1,
                'revision_notes'    => $this->data['revision_notes'],
                'file_path'         => $finalFilePath,
                'original_name'     => $this->data['original_name'],
                'mime_type'         => $this->data['mime_type'],
                'size_bytes'        => $this->data['size_bytes'],
                'uploaded_by'       => $this->userId,
                'userId'            => $this->userId,
                'expiry_date'       => now()->addDays(30),
            ]);

            // 5. Buat log aktivitas
            DocumentActivityLog::create([
                'document_id' => $document->id,
                'user_id'     => $this->userId,
                'action'      => 'upload',
                'metadata'    => [ /* data metadata */],
            ]);

            $user->notify(new DocumentUploadSuccess($document));

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            if (isset($user)) {
                $user->notify(new DocumentUploadFailed(
                    $this->data['original_name'],
                    $e->getMessage()
                ));
            }

            $this->fail($e);
        }
    }
}
