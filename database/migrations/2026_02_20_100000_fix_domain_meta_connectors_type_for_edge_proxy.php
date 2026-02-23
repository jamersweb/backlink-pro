<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        if (!Schema::hasTable('domain_meta_connectors')) {
            return;
        }
        DB::statement('ALTER TABLE domain_meta_connectors MODIFY type VARCHAR(32) NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE domain_meta_connectors MODIFY type ENUM('wordpress','shopify','custom_js') NOT NULL");
    }
};
