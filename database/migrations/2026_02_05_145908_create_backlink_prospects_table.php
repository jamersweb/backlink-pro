<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlink_prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('backlink_campaigns')->onDelete('cascade');
            $table->string('prospect_url', 2048);
            $table->string('domain');
            $table->enum('type', ['directory', 'resource_page', 'partner', 'guest', 'press', 'community']);
            $table->integer('relevance_score')->default(0);
            $table->integer('authority_score')->default(0);
            $table->integer('risk_score')->default(0);
            $table->string('contact_email')->nullable();
            $table->enum('outreach_status', ['new', 'contacted', 'replied', 'negotiating', 'won', 'lost'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'outreach_status']);
            $table->index(['campaign_id', 'relevance_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_prospects');
    }
};
