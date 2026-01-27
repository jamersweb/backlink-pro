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
        if (Schema::hasTable('notifications')) {
            $requiredColumns = ['id', 'user_id', 'domain_id', 'type', 'title', 'message', 'severity', 'action_url', 'evidence_json', 'fingerprint', 'status', 'muted', 'snoozed_until', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('notifications', $column)) {
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
                    AND TABLE_NAME = 'notifications' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE notifications DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {}
            DB::statement('DROP TABLE IF EXISTS notifications');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('domain_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info')->index();
            $table->text('action_url')->nullable();
            $table->json('evidence_json')->nullable();
            $table->string('fingerprint', 64)->index();
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread')->index();
            $table->boolean('muted')->default(false);
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'fingerprint', 'created_at']);
        });
        
        DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE notifications ADD CONSTRAINT notifications_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
