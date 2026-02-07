<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlink_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->string('name');
            $table->string('target_domain');
            $table->string('strategy_version')->default('v1.0.0');
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->json('goals')->nullable(); // ref_domains_target, dofollow_target, anchor_mix
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_campaigns');
    }
};
