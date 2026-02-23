<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsc_query_page_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->string('site_url', 512)->index();
            $table->date('date')->index();
            $table->string('query', 512)->index();
            $table->string('page_url', 2048);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['domain_id', 'site_url', 'date']);
            $table->index(['domain_id', 'query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_query_page_metrics');
    }
};
