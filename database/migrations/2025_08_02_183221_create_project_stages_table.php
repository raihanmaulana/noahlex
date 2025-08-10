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
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        
        Schema::table('projects', function (Blueprint $table) {
            
            if (Schema::hasColumn('projects', 'status_id')) {
                $table->dropColumn('status_id');
            }

            
            $table->unsignedBigInteger('stage_id')->nullable()->after('date');
            $table->foreign('stage_id')->references('id')->on('project_stages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::table('projects', function (Blueprint $table) {
            
            $table->dropForeign(['stage_id']);
            $table->dropColumn('stage_id');

            
            $table->unsignedBigInteger('status_id')->nullable()->after('date');
        });

        
        Schema::dropIfExists('project_stages');
    }
};
