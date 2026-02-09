<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crux_results')) {
            return;
        }

        Schema::create('crux_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->enum('target_type', ['url', 'origin']);
            $table->string('target_value');
            $table->enum('form_factor', ['PHONE', 'DESKTOP', 'TABLET', 'ALL'])->default('ALL');
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['success', 'no_data', 'failed'])->default('failed');
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('kpis')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'target_type', 'target_value', 'form_factor'], 'crux_cache_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crux_results');
    }
};
