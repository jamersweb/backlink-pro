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
        Schema::create('domain_meta_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->enum('source', ['manual', 'audit', 'gsc', 'connector'])->index();
            $table->text('url');
            $table->string('path')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->string('resource_type')->nullable()->index();
            $table->string('title_current')->nullable();
            $table->json('meta_current_json')->nullable();
            $table->json('meta_published_json')->nullable();
            $table->timestamps();

            // Note: MySQL unique constraints with NULLs require special handling
            // We'll use application-level validation for these
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_meta_pages');
    }
};
