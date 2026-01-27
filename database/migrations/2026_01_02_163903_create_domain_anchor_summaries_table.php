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
        Schema::create('domain_anchor_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('domain_backlink_runs')->onDelete('cascade');
            $table->text('anchor');
            $table->char('anchor_hash', 64)->index();
            $table->unsignedInteger('count')->default(0);
            $table->string('type')->index(); // brand/exact/partial/generic/url/empty
            $table->timestamps();

            $table->unique(['run_id', 'anchor_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_anchor_summaries');
    }
};
