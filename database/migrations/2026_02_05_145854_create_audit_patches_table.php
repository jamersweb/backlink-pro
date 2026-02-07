<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_patches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_fix_candidate_id')->constrained('audit_fix_candidates')->onDelete('cascade');
            $table->foreignId('repo_id')->nullable()->constrained('repos')->onDelete('set null');
            $table->string('branch_name')->nullable();
            $table->string('commit_sha')->nullable();
            $table->string('pr_url')->nullable();
            $table->longText('patch_unified_diff');
            $table->json('files_touched')->nullable();
            $table->text('apply_instructions')->nullable();
            $table->text('test_instructions')->nullable();
            $table->enum('status', ['ready', 'pr_opened', 'failed'])->default('ready');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_patches');
    }
};
