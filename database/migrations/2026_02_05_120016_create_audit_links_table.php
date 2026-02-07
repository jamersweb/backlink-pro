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
        Schema::create('audit_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('from_url', 2048);
            $table->string('to_url', 2048);
            $table->string('to_url_normalized', 2048);
            $table->enum('type', ['internal', 'external']);
            $table->boolean('rel_nofollow')->default(false);
            $table->text('anchor_text')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('final_url', 2048)->nullable();
            $table->integer('redirect_hops')->default(0);
            $table->boolean('is_broken')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('audit_id');
            $table->index('to_url_normalized');
            $table->index('is_broken');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_links');
    }
};
