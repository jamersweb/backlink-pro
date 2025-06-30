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
        Schema::create('web_information', function (Blueprint $table) {
                   $table->id();
                    $table->foreignId('user_id')
                        ->constrained()
                        ->onDelete('cascade');
                    $table->string('web_name');
                    $table->string('web_url');
                    $table->string('web_keyword');
                    $table->text('web_about');
                    $table->string('web_target');
                    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_information');
    }
};
