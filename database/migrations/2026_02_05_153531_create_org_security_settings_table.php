<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('org_security_settings')) {
            return;
        }

        Schema::create('org_security_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained('organizations')->onDelete('cascade');
            $table->boolean('require_2fa')->default(false);
            $table->boolean('require_sso')->default(false);
            $table->json('ip_allowlist')->nullable();
            $table->integer('data_retention_days')->nullable(); // null = use default
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_security_settings');
    }
};
