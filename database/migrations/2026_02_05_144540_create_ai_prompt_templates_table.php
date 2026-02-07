<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('system_prompt');
            $table->text('user_prompt');
            $table->json('output_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('version', 20)->default('v1.0.0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
