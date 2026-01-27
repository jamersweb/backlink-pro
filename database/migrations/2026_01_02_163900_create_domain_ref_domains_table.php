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
        Schema::create('domain_ref_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('domain_backlink_runs')->onDelete('cascade');
            $table->string('domain')->index();
            $table->unsignedInteger('backlinks_count')->default(0);
            $table->date('first_seen')->nullable();
            $table->date('last_seen')->nullable();
            $table->string('tld', 20)->nullable()->index();
            $table->string('country', 10)->nullable();
            $table->unsignedSmallInteger('risk_score')->nullable(); // 0-100
            $table->timestamps();

            $table->unique(['run_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_ref_domains');
    }
};
