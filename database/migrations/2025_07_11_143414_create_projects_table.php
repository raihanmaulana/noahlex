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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained('project_types');
            $table->enum('category', ['Data Center Project', 'Renewable Energy', 'Hybrid']);
            $table->string('location');
            $table->date('date');
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->string('size')->nullable();
            $table->boolean('enable_workflow')->default(false);
            $table->foreignId('project_manager_id')->constrained('users');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
