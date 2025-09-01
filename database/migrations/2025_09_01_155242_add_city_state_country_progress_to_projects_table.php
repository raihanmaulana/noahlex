<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('city')->nullable()->after('size');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->unsignedTinyInteger('progress')->default(0)->after('country'); 
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['city', 'state', 'country', 'progress']);
        });
    }
};
