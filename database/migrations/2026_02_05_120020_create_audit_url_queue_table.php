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
        if (Schema::hasTable('audit_url_queue')) {
            Schema::dropIfExists('audit_url_queue');
        }

        Schema::create('audit_url_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('url', 2048);
            $table->string('url_normalized', 2048);
            $table->integer('depth')->default(0);
            $table->enum('status', ['queued', 'processing', 'done', 'failed'])->default('queued');
            $table->string('discovered_from', 2048)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        // Add indexes (using prefix for url_normalized due to length)
        Schema::table('audit_url_queue', function (Blueprint $table) {
            // Use a composite index instead of unique (application will handle uniqueness)
            $table->index(['audit_id', 'status', 'depth']);
            // Add index on url_normalized with prefix (first 255 chars)
            \DB::statement('CREATE INDEX audit_url_queue_url_normalized_idx ON audit_url_queue (audit_id, url_normalized(255))');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_url_queue');
    }
};
