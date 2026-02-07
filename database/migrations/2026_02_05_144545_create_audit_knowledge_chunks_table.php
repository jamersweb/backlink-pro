<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->enum('chunk_type', ['issue', 'page', 'performance', 'links', 'assets', 'competitor']);
            $table->unsignedBigInteger('source_id')->nullable(); // issue_id, page_id, etc.
            $table->text('content');
            $table->json('embedding')->nullable(); // Store as JSON array (for MySQL compatibility)
            $table->timestamps();

            $table->index(['audit_id', 'chunk_type']);
            $table->index(['source_id', 'chunk_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_knowledge_chunks');
    }
};
