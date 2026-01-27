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
        Schema::create('backlink_page_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->unique()->constrained('backlink_attempts')->onDelete('cascade');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('content_type')->nullable();
            $table->boolean('has_comment_form')->default(false);
            $table->boolean('has_login_form')->default(false);
            $table->boolean('has_register_link')->default(false);
            $table->boolean('has_captcha')->default(false);
            $table->boolean('is_cloudflare')->default(false);
            $table->boolean('has_profile_fields')->default(false);
            $table->boolean('has_forum_thread_ui')->default(false);
            $table->boolean('has_editor_wysiwyg')->default(false);
            $table->boolean('has_email_verify_hint')->default(false);
            $table->unsignedInteger('outbound_links_count')->nullable();
            $table->unsignedInteger('text_length')->nullable();
            $table->json('signals_json')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_page_signals');
    }
};
