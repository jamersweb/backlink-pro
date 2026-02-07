<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gsc_page_metrics')) {
            return;
        }

        Schema::create('gsc_page_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('site_url');
            $table->date('date')->index();
            $table->string('page_url', 2048);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 6, 4)->nullable();
            $table->decimal('position', 6, 2)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'site_url', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_page_metrics');
    }
};
