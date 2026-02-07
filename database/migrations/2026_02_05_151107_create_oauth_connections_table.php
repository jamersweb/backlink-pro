<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->enum('provider', ['google'])->default('google');
            $table->string('account_email')->nullable();
            $table->text('access_token_encrypted');
            $table->text('refresh_token_encrypted');
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->enum('status', ['active', 'revoked', 'error'])->default('active');
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_connections');
    }
};
