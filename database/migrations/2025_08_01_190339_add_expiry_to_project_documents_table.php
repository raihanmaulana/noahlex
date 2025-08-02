<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->date('expiry_date')->nullable();
            $table->boolean('reminder_in_app')->default(false);
            $table->boolean('reminder_email')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
            $table->dropColumn('reminder_in_app');
            $table->dropColumn('reminder_email');
        });
    }
};
