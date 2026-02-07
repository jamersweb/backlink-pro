<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sso_connections')) {
            return;
        }

        Schema::create('sso_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->enum('type', ['oidc', 'saml'])->default('oidc');
            $table->text('config_encrypted'); // client_id, issuer, certs
            $table->json('domains')->nullable(); // allowed email domains
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_connections');
    }
};
