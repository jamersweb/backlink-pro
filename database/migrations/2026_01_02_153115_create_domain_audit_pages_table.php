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
        Schema::create('domain_audit_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_audit_id')->constrained('domain_audits')->onDelete('cascade');
            $table->string('url')->index();
            $table->string('path')->nullable()->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('final_url')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('content_type')->nullable();
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical')->nullable();
            $table->string('robots_meta')->nullable();
            $table->unsignedSmallInteger('h1_count')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->unsignedSmallInteger('issues_count')->default(0);
            $table->timestamps();

            $table->unique(['domain_audit_id', 'url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_audit_pages');
    }
};
