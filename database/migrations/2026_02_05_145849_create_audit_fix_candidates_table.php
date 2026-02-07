<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_fix_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->foreignId('issue_id')->nullable()->constrained('audit_issues')->onDelete('set null');
            $table->string('code'); // e.g. FIX_ADD_TWITTER_CARDS
            $table->string('title');
            $table->enum('target_platform', ['laravel', 'nextjs', 'wordpress', 'shopify', 'generic'])->default('generic');
            $table->enum('risk', ['low', 'medium', 'high'])->default('low');
            $table->integer('confidence')->default(50); // 0-100
            $table->enum('status', ['draft', 'generated', 'approved', 'rejected', 'applied'])->default('draft');
            $table->text('generated_summary')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_fix_candidates');
    }
};
