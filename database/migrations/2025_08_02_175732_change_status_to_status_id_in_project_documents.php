<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropColumn('status'); // hapus kolom string lama
        });

        Schema::table('project_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('project_id');

            $table->foreign('status_id')
                ->references('id')
                ->on('project_document_statuses')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            //
        });
    }
};
