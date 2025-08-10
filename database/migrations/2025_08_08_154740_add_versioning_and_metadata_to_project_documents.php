<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            
            if (!Schema::hasColumn('project_documents', 'document_group_id')) {
                $table->uuid('document_group_id')->nullable()->index()->after('project_id');
            }
            if (Schema::hasColumn('project_documents', 'version')) {
                $table->integer('version')->default(1)->change();
            } else {
                $table->integer('version')->default(1)->after('tags');
            }

            if (!Schema::hasColumn('project_documents', 'original_name')) {
                $table->string('original_name')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('project_documents', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('original_name');
            }
            if (!Schema::hasColumn('project_documents', 'size_bytes')) {
                $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            }

        });
        
    //     DB::statement('
    //     CREATE UNIQUE INDEX IF NOT EXISTS pd_group_version_unique
    //     ON project_documents (document_group_id, version)
    //     WHERE document_group_id IS NOT NULL
    // ');
    }

    public function down(): void
    {
        
    }
};
