<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rank_keywords')) {
            // Table exists, check if we need to add columns
            if (!Schema::hasColumn('rank_keywords', 'rank_project_id')) {
                Schema::table('rank_keywords', function (Blueprint $table) {
                    $table->foreignId('rank_project_id')->nullable()->after('id')->constrained('rank_projects')->onDelete('cascade');
                });
            }
            return;
        }

        Schema::create('rank_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rank_project_id')->constrained('rank_projects')->onDelete('cascade');
            $table->string('keyword');
            $table->enum('device', ['mobile', 'desktop'])->default('desktop');
            $table->string('location')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rank_project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_keywords');
    }
};
