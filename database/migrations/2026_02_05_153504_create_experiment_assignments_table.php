<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('experiment_assignments')) {
            return;
        }

        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('experiment_variants')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('anonymous_id')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            // One assignment per subject per experiment
            $table->unique(['experiment_id', 'organization_id'], 'exp_org_unique');
            $table->unique(['experiment_id', 'user_id'], 'exp_user_unique');
            $table->unique(['experiment_id', 'anonymous_id'], 'exp_anon_unique');
            $table->index(['experiment_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_assignments');
    }
};
