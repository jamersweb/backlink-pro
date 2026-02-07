<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('set null');
            $table->foreignId('audit_id')->nullable()->constrained('audits')->onDelete('set null');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['new', 'quoted', 'approved', 'in_progress', 'delivered', 'closed', 'canceled'])->default('new');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->integer('total_price_cents')->nullable();
            $table->string('currency', 10)->default('usd');
            $table->text('notes')->nullable();
            $table->json('scope')->nullable(); // issue codes, pages list, target urls, metrics
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
