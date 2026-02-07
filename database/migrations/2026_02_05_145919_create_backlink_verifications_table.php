<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlink_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('backlink_campaigns')->onDelete('cascade');
            $table->string('target_url', 2048);
            $table->string('found_on_url', 2048);
            $table->string('anchor_text')->nullable();
            $table->enum('rel_type', ['dofollow', 'nofollow', 'ugc', 'sponsored'])->default('dofollow');
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->enum('status', ['active', 'lost'])->default('active');
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_verifications');
    }
};
