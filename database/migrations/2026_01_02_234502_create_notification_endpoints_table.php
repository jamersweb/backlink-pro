<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('notification_endpoints')) {
            $requiredColumns = ['id', 'user_id', 'name', 'url', 'secret', 'is_active', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('notification_endpoints', $column)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            if ($hasAllColumns) {
                return;
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'notification_endpoints' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE notification_endpoints DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            DB::statement('DROP TABLE IF EXISTS notification_endpoints');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        Schema::create('notification_endpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->text('url');
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        DB::statement('ALTER TABLE notification_endpoints ADD CONSTRAINT notification_endpoints_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_endpoints');
    }
};
