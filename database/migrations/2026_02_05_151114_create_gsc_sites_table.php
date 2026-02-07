<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gsc_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('site_url'); // sc-domain:xpertbid.com or https://www.xpertbid.com/
            $table->string('permission_level')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'site_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_sites');
    }
};
