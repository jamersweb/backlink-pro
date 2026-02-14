<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('audit_links')) {
            // Table was created in a previous run; add prefix index if missing
            $indexes = DB::select("SHOW INDEX FROM audit_links WHERE Key_name = 'audit_links_to_url_normalized_index'");
            if (empty($indexes)) {
                DB::statement('CREATE INDEX audit_links_to_url_normalized_index ON audit_links (to_url_normalized(768))');
            }
            return;
        }

        Schema::create('audit_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('from_url', 2048);
            $table->string('to_url', 2048);
            $table->string('to_url_normalized', 2048);
            $table->enum('type', ['internal', 'external']);
            $table->boolean('rel_nofollow')->default(false);
            $table->text('anchor_text')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('final_url', 2048)->nullable();
            $table->integer('redirect_hops')->default(0);
            $table->boolean('is_broken')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();

            // Indexes (to_url_normalized uses prefix to stay under MySQL key length limit)
            $table->index('audit_id');
            $table->index('is_broken');
            $table->index('type');
        });
        DB::statement('CREATE INDEX audit_links_to_url_normalized_index ON audit_links (to_url_normalized(768))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_links');
    }
};
