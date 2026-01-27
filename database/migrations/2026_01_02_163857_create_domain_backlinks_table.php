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
        Schema::create('domain_backlinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('domain_backlink_runs')->onDelete('cascade');
            $table->char('fingerprint', 64)->index();
            $table->text('source_url');
            $table->string('source_domain')->index();
            $table->text('target_url');
            $table->text('anchor')->nullable();
            $table->string('rel')->index(); // follow/nofollow/ugc/sponsored/unknown
            $table->date('first_seen')->nullable();
            $table->date('last_seen')->nullable();
            $table->string('tld', 20)->nullable()->index();
            $table->string('country', 10)->nullable();
            $table->json('risk_flags_json')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'fingerprint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_backlinks');
    }
};
