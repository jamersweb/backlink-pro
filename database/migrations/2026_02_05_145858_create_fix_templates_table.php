<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fix_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->enum('platform', ['laravel', 'nextjs', 'wordpress', 'shopify', 'generic']);
            $table->text('description');
            $table->json('patch_strategy'); // where to insert, placeholders
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fix_templates');
    }
};
