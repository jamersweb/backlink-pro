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
        Schema::create('audit_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->foreignId('audit_page_id')->nullable()->constrained('audit_pages')->onDelete('cascade');
            $table->string('page_url', 2048);
            $table->string('asset_url', 2048);
            $table->enum('type', ['img', 'js', 'css', 'font', 'other']);
            $table->bigInteger('size_bytes')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('content_type', 255)->nullable();
            $table->boolean('is_third_party')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('audit_id');
            $table->index('audit_page_id');
            $table->index('type');
            $table->index('size_bytes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_assets');
    }
};
