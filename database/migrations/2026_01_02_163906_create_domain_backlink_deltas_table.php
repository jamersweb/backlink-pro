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
        Schema::create('domain_backlink_deltas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->foreignId('current_run_id')->constrained('domain_backlink_runs')->onDelete('cascade');
            $table->foreignId('previous_run_id')->nullable()->constrained('domain_backlink_runs')->onDelete('set null');
            $table->unsignedInteger('new_links')->default(0);
            $table->unsignedInteger('lost_links')->default(0);
            $table->unsignedInteger('new_ref_domains')->default(0);
            $table->unsignedInteger('lost_ref_domains')->default(0);
            $table->timestamps();

            $table->unique('current_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_backlink_deltas');
    }
};
