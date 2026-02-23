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
        // Check if tables already exist
        $tagsExists = Schema::hasTable('backlink_tags');
        $pivotExists = Schema::hasTable('backlink_item_tag');
        
        if ($tagsExists && $pivotExists) {
            // Check if backlink_tags has all required columns
            $requiredColumns = ['id', 'domain_id', 'name', 'color', 'created_at', 'updated_at'];
            $hasAllColumns = true;
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn('backlink_tags', $column)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            
            if ($hasAllColumns) {
                return; // Skip migration
            }
        }
        
        $isSqlite = DB::getDriverName() === 'sqlite';
        if (!$isSqlite) {
            if ($pivotExists) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'backlink_item_tag' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    foreach ($constraints as $constraint) {
                        DB::statement("ALTER TABLE backlink_item_tag DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                    }
                } catch (\Exception $e) {}
                DB::statement('DROP TABLE IF EXISTS backlink_item_tag');
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
            if ($tagsExists) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'backlink_tags' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    foreach ($constraints as $constraint) {
                        DB::statement("ALTER TABLE backlink_tags DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                    }
                } catch (\Exception $e) {}
                DB::statement('DROP TABLE IF EXISTS backlink_tags');
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } else {
            Schema::dropIfExists('backlink_item_tag');
            Schema::dropIfExists('backlink_tags');
        }
        Schema::create('backlink_tags', function (Blueprint $table) use ($isSqlite) {
            $table->id();
            if ($isSqlite) {
                $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('domain_id')->index();
            }
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->timestamps();
            $table->unique(['domain_id', 'name']);
        });
        if (!$isSqlite) {
            DB::statement('ALTER TABLE backlink_tags ADD CONSTRAINT backlink_tags_domain_id_foreign FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE');
        }

        Schema::create('backlink_item_tag', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite) {
                $table->foreignId('backlink_item_id')->constrained('domain_backlinks')->cascadeOnDelete();
                $table->foreignId('backlink_tag_id')->constrained('backlink_tags')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('backlink_item_id');
                $table->unsignedBigInteger('backlink_tag_id');
            }
            $table->primary(['backlink_item_id', 'backlink_tag_id']);
        });
        if (!$isSqlite) {
            DB::statement('ALTER TABLE backlink_item_tag ADD CONSTRAINT backlink_item_tag_backlink_item_id_foreign FOREIGN KEY (backlink_item_id) REFERENCES domain_backlinks(id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE backlink_item_tag ADD CONSTRAINT backlink_item_tag_backlink_tag_id_foreign FOREIGN KEY (backlink_tag_id) REFERENCES backlink_tags(id) ON DELETE CASCADE');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_item_tag');
        Schema::dropIfExists('backlink_tags');
    }
};
