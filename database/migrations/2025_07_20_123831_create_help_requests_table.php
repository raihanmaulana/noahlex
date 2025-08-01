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
        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('issue_type');
            $table->text('message');
            $table->unsignedBigInteger('project_id')->nullable(); 
            $table->unsignedBigInteger('user_id')->nullable(); 
            $table->enum('status', ['pending', 'resolved', 'closed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_requests');
    }
};
