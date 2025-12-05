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
        Schema::create('backlink_opportunity_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_opportunity_id')
                ->constrained('backlink_opportunities')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['backlink_opportunity_id', 'category_id'], 'opportunity_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_opportunity_category');
    }
};


