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
        if (Schema::hasTable('notification_rules')) {
            $requiredColumns = ['id', 'user_id', 'domain_id', 'type', 'is_enabled', 'severity', 'cooldown_minutes', 'thresholds_json', 'channels_json', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('notification_rules', $column)) {
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
                    AND TABLE_NAME = 'notification_rules' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE notification_rules DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            DB::statement('DROP TABLE IF EXISTS notification_rules');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->string('type')->index();
            $table->boolean('is_enabled')->default(true);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->unsignedInteger('cooldown_minutes')->default(720);
            $table->json('thresholds_json')->nullable();
            $table->json('channels_json');
            $table->timestamps();

            $table->unique(['user_id', 'domain_id', 'type']);
        });
        
        DB::statement('ALTER TABLE notification_rules ADD CONSTRAINT notification_rules_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE notification_rules ADD CONSTRAINT notification_rules_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
