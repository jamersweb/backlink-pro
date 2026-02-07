<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('repo_connection_id')->constrained('repo_connections')->onDelete('cascade');
            $table->enum('provider', ['github', 'gitlab'])->default('github');
            $table->string('repo_full_name'); // owner/name
            $table->string('default_branch')->default('main');
            $table->enum('language_hint', ['laravel', 'nextjs', 'wordpress', 'shopify', 'unknown'])->default('unknown');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'repo_full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repos');
    }
};
