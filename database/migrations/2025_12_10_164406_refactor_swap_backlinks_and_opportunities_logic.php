<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration swaps the logic:
     * - backlinks becomes the global store (admin-managed pool)
     * - backlink_opportunities becomes campaign-specific (where user links were added)
     */
    public function up(): void
    {
        // Step 1: Create temporary tables to store data
        $this->createTempTables();
        
        // Step 2: Backup existing data
        $this->backupData();
        
        // Step 3: Drop foreign keys and constraints
        $this->dropConstraints();
        
        // Step 4: Transform backlinks table (make it the global store)
        $this->transformBacklinksTable();
        
        // Step 5: Transform backlink_opportunities table (make it campaign-specific)
        $this->transformOpportunitiesTable();
        
        // Step 6: Create new pivot table for backlink categories
        $this->createBacklinkCategoryTable();
        
        // Step 7: Migrate data to new structure
        $this->migrateData();
        
        // Step 8: Drop temporary tables
        $this->dropTempTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is complex to reverse, so we'll just drop the new structures
        // and restore from backup if needed
        
        Schema::dropIfExists('backlink_category');
        
        // Restore backlinks table structure
        Schema::table('backlinks', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['pa', 'da', 'site_type', 'status', 'daily_site_limit', 'metadata']);
            $table->dropUnique(['url']);
            
            // Restore old columns
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('cascade');
            $table->unsignedBigInteger('site_account_id')->nullable();
            $table->string('keyword')->nullable();
            $table->string('anchor_text')->nullable();
            $table->enum('status', ['pending', 'submitted', 'verified', 'error'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('error_message')->nullable();
        });
        
        // Restore backlink_opportunities table structure
        Schema::table('backlink_opportunities', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['backlink_id']);
            $table->dropColumn(['campaign_id', 'backlink_id', 'site_account_id', 'keyword', 'anchor_text', 'status', 'verified_at', 'error_message', 'url']);
            
            // Restore old columns
            $table->string('url')->unique();
            $table->unsignedTinyInteger('pa')->nullable();
            $table->unsignedTinyInteger('da')->nullable();
            $table->enum('site_type', ['comment', 'profile', 'forum', 'guestposting', 'other'])->default('comment');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->unsignedInteger('daily_site_limit')->nullable();
            $table->json('metadata')->nullable();
        });
        
        // Recreate old pivot table
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

    private function createTempTables(): void
    {
        // Drop temp tables if they exist (from previous failed migration)
        DB::statement('DROP TABLE IF EXISTS temp_backlinks_backup');
        DB::statement('DROP TABLE IF EXISTS temp_opportunities_backup');
        DB::statement('DROP TABLE IF EXISTS temp_opportunity_category_backup');
        
        // Create temp table for backlinks data
        DB::statement('CREATE TABLE temp_backlinks_backup LIKE backlinks');
        DB::statement('INSERT INTO temp_backlinks_backup SELECT * FROM backlinks');
        
        // Create temp table for opportunities data
        DB::statement('CREATE TABLE temp_opportunities_backup LIKE backlink_opportunities');
        DB::statement('INSERT INTO temp_opportunities_backup SELECT * FROM backlink_opportunities');
        
        // Create temp table for pivot data
        if (Schema::hasTable('backlink_opportunity_category')) {
            DB::statement('CREATE TABLE temp_opportunity_category_backup LIKE backlink_opportunity_category');
            DB::statement('INSERT INTO temp_opportunity_category_backup SELECT * FROM backlink_opportunity_category');
        }
    }

    private function backupData(): void
    {
        // Data is already backed up in temp tables
    }

    private function dropConstraints(): void
    {
        // Drop foreign keys from backlinks table using raw SQL to avoid constraint name issues
        $dbName = DB::getDatabaseName();
        
        // Get all foreign keys for backlinks table
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = 'backlinks'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND CONSTRAINT_NAME != 'PRIMARY'
        ", [$dbName]);
        
        // Drop each foreign key
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `backlinks` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
                continue;
            }
        }
        
        // Also try dropping by column names (in case constraint names are different)
        $columnsToCheck = ['campaign_id', 'backlink_opportunity_id', 'site_account_id'];
        foreach ($columnsToCheck as $column) {
            if (Schema::hasColumn('backlinks', $column)) {
                try {
                    // Try to get constraint name for this column
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = 'backlinks'
                        AND COLUMN_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                        LIMIT 1
                    ", [$dbName, $column]);
                    
                    if (!empty($constraints)) {
                        DB::statement("ALTER TABLE `backlinks` DROP FOREIGN KEY `{$constraints[0]->CONSTRAINT_NAME}`");
                    }
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                    continue;
                }
            }
        }
        
        // Drop pivot table if exists
        if (Schema::hasTable('backlink_opportunity_category')) {
            Schema::dropIfExists('backlink_opportunity_category');
        }
    }

    private function transformBacklinksTable(): void
    {
        // First, handle duplicate URLs - keep only the first occurrence of each URL
        DB::statement("
            DELETE b1 FROM backlinks b1
            INNER JOIN backlinks b2 
            WHERE b1.id > b2.id 
            AND b1.url = b2.url
        ");
        
        // Remove campaign-specific columns
        Schema::table('backlinks', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('backlinks', 'campaign_id')) {
                $columnsToDrop[] = 'campaign_id';
            }
            if (Schema::hasColumn('backlinks', 'backlink_opportunity_id')) {
                $columnsToDrop[] = 'backlink_opportunity_id';
            }
            if (Schema::hasColumn('backlinks', 'site_account_id')) {
                $columnsToDrop[] = 'site_account_id';
            }
            if (Schema::hasColumn('backlinks', 'keyword')) {
                $columnsToDrop[] = 'keyword';
            }
            if (Schema::hasColumn('backlinks', 'anchor_text')) {
                $columnsToDrop[] = 'anchor_text';
            }
            if (Schema::hasColumn('backlinks', 'type')) {
                $columnsToDrop[] = 'type';
            }
            if (Schema::hasColumn('backlinks', 'status')) {
                $columnsToDrop[] = 'status';
            }
            if (Schema::hasColumn('backlinks', 'verified_at')) {
                $columnsToDrop[] = 'verified_at';
            }
            if (Schema::hasColumn('backlinks', 'error_message')) {
                $columnsToDrop[] = 'error_message';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
        
        // Add global store columns
        Schema::table('backlinks', function (Blueprint $table) {
            // PA/DA might already exist, check first
            if (!Schema::hasColumn('backlinks', 'pa')) {
                $table->unsignedTinyInteger('pa')->nullable()->comment('Page Authority 0-100');
            }
            if (!Schema::hasColumn('backlinks', 'da')) {
                $table->unsignedTinyInteger('da')->nullable()->comment('Domain Authority 0-100');
            }
            
            if (!Schema::hasColumn('backlinks', 'site_type')) {
                $table->enum('site_type', ['comment', 'profile', 'forum', 'guestposting', 'other'])
                    ->default('comment')
                    ->after('url');
            }
            if (!Schema::hasColumn('backlinks', 'status')) {
                $table->enum('status', ['active', 'inactive', 'banned'])
                    ->default('active')
                    ->after('site_type');
            }
            if (!Schema::hasColumn('backlinks', 'daily_site_limit')) {
                $table->unsignedInteger('daily_site_limit')
                    ->nullable()
                    ->comment('Max links per day from this site')
                    ->after('status');
            }
            if (!Schema::hasColumn('backlinks', 'metadata')) {
                $table->json('metadata')->nullable()->after('daily_site_limit');
            }
            
            // Make URL unique (only if unique constraint doesn't already exist)
            $indexes = DB::select("SHOW INDEXES FROM `backlinks` WHERE Column_name = 'url' AND Non_unique = 0");
            if (empty($indexes)) {
                try {
                    $table->unique('url');
                } catch (\Exception $e) {
                    // Try with raw SQL if Laravel method fails
                    try {
                        DB::statement('ALTER TABLE `backlinks` ADD UNIQUE KEY `backlinks_url_unique` (`url`)');
                    } catch (\Exception $e2) {
                        // Unique constraint already exists, ignore
                    }
                }
            }
        });
    }

    private function transformOpportunitiesTable(): void
    {
        // First, clean up orphaned data - delete opportunities that reference non-existent campaigns
        // (This will happen during data migration, but we need to ensure table is clean first)
        
        // Remove global pool columns
        Schema::table('backlink_opportunities', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('backlink_opportunities', 'url')) {
                $columnsToDrop[] = 'url';
            }
            if (Schema::hasColumn('backlink_opportunities', 'pa')) {
                $columnsToDrop[] = 'pa';
            }
            if (Schema::hasColumn('backlink_opportunities', 'da')) {
                $columnsToDrop[] = 'da';
            }
            if (Schema::hasColumn('backlink_opportunities', 'site_type')) {
                $columnsToDrop[] = 'site_type';
            }
            if (Schema::hasColumn('backlink_opportunities', 'status')) {
                $columnsToDrop[] = 'status';
            }
            if (Schema::hasColumn('backlink_opportunities', 'daily_site_limit')) {
                $columnsToDrop[] = 'daily_site_limit';
            }
            if (Schema::hasColumn('backlink_opportunities', 'metadata')) {
                $columnsToDrop[] = 'metadata';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
        
        // Add campaign-specific columns (without foreign keys first)
        Schema::table('backlink_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('backlink_opportunities', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'backlink_id')) {
                $table->unsignedBigInteger('backlink_id')->nullable()->after('campaign_id');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'site_account_id')) {
                $table->unsignedBigInteger('site_account_id')->nullable()->after('backlink_id');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'url')) {
                $table->string('url')->nullable()->comment('Actual backlink URL (may differ from backlink.url)')->after('site_account_id');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'type')) {
                $table->enum('type', ['comment', 'profile', 'forum', 'guestposting'])->default('comment')->after('url');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'keyword')) {
                $table->string('keyword')->nullable()->after('type');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'anchor_text')) {
                $table->string('anchor_text')->nullable()->after('keyword');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'status')) {
                $table->enum('status', ['pending', 'submitted', 'verified', 'error'])->default('pending')->after('anchor_text');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('backlink_opportunities', 'error_message')) {
                $table->text('error_message')->nullable()->after('verified_at');
            }
        });
        
        // Clean up orphaned data before adding foreign keys
        DB::statement("DELETE FROM backlink_opportunities WHERE campaign_id IS NOT NULL AND campaign_id NOT IN (SELECT id FROM campaigns)");
        DB::statement("DELETE FROM backlink_opportunities WHERE backlink_id IS NOT NULL AND backlink_id NOT IN (SELECT id FROM backlinks)");
        
        // Now add foreign key constraints
        Schema::table('backlink_opportunities', function (Blueprint $table) {
            // Check if foreign keys don't already exist
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'backlink_opportunities'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            $fkNames = array_map(function($fk) { return $fk->CONSTRAINT_NAME; }, $foreignKeys);
            
            if (!in_array('backlink_opportunities_campaign_id_foreign', $fkNames)) {
                $table->foreign('campaign_id')
                    ->references('id')
                    ->on('campaigns')
                    ->onDelete('cascade');
            }
            
            if (!in_array('backlink_opportunities_backlink_id_foreign', $fkNames)) {
                $table->foreign('backlink_id')
                    ->references('id')
                    ->on('backlinks')
                    ->onDelete('cascade');
            }
            
            // Add foreign key for site_account_id if table exists
            if (Schema::hasTable('site_accounts') && !in_array('backlink_opportunities_site_account_id_foreign', $fkNames)) {
                $table->foreign('site_account_id')
                    ->references('id')
                    ->on('site_accounts')
                    ->onDelete('set null');
            }
        });
    }

    private function createBacklinkCategoryTable(): void
    {
        if (!Schema::hasTable('backlink_category')) {
            Schema::create('backlink_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('backlink_id')
                    ->constrained('backlinks')
                    ->onDelete('cascade');
                $table->foreignId('category_id')
                    ->constrained('categories')
                    ->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['backlink_id', 'category_id'], 'backlink_category_unique');
            });
        }
    }

    private function migrateData(): void
    {
        // Check if tables are already in new structure
        $backlinksColumns = DB::select("SHOW COLUMNS FROM backlinks");
        $backlinksColumnNames = array_map(function($col) { return $col->Field; }, $backlinksColumns);
        $backlinksAlreadyTransformed = in_array('site_type', $backlinksColumnNames) && !in_array('campaign_id', $backlinksColumnNames);
        
        $opportunitiesColumns = DB::select("SHOW COLUMNS FROM backlink_opportunities");
        $opportunitiesColumnNames = array_map(function($col) { return $col->Field; }, $opportunitiesColumns);
        $opportunitiesAlreadyTransformed = in_array('campaign_id', $opportunitiesColumnNames) && in_array('backlink_id', $opportunitiesColumnNames);
        
        // If both tables are already transformed, skip data migration
        if ($backlinksAlreadyTransformed && $opportunitiesAlreadyTransformed) {
            return;
        }
        
        // Check if temp_opportunities_backup has old structure (with url, pa, da columns)
        $tempTableColumns = DB::select("SHOW COLUMNS FROM temp_opportunities_backup");
        $tempColumnNames = array_map(function($col) { return $col->Field; }, $tempTableColumns);
        $hasOldStructure = in_array('url', $tempColumnNames) && in_array('pa', $tempColumnNames);
        
        // Step 1: Migrate opportunities data to backlinks (the store)
        // Only if temp table has old structure with url/pa/da
        if ($hasOldStructure) {
            DB::statement("
                INSERT INTO backlinks (url, pa, da, site_type, status, daily_site_limit, metadata, created_at, updated_at)
                SELECT DISTINCT
                    url,
                    pa,
                    da,
                    site_type,
                    status,
                    daily_site_limit,
                    metadata,
                    created_at,
                    updated_at
                FROM temp_opportunities_backup
                WHERE url IS NOT NULL AND url != ''
                ON DUPLICATE KEY UPDATE
                    pa = VALUES(pa),
                    da = VALUES(da),
                    site_type = VALUES(site_type),
                    status = VALUES(status),
                    daily_site_limit = VALUES(daily_site_limit),
                    metadata = VALUES(metadata)
            ");
        }
        
        // Step 2: Migrate category relationships from opportunities to backlinks
        if (DB::getSchemaBuilder()->hasTable('temp_opportunity_category_backup')) {
            DB::statement("
                INSERT INTO backlink_category (backlink_id, category_id, created_at, updated_at)
                SELECT 
                    b.id as backlink_id,
                    toc.category_id,
                    toc.created_at,
                    toc.updated_at
                FROM temp_opportunity_category_backup toc
                INNER JOIN temp_opportunities_backup topp ON toc.backlink_opportunity_id = topp.id
                INNER JOIN backlinks b ON topp.url = b.url
                ON DUPLICATE KEY UPDATE
                    created_at = VALUES(created_at),
                    updated_at = VALUES(updated_at)
            ");
        }
        
        // Step 3: Migrate backlinks data to opportunities (campaign-specific)
        // Only if temp_backlinks_backup has old structure (with campaign_id)
        $tempBacklinksColumns = DB::select("SHOW COLUMNS FROM temp_backlinks_backup");
        $tempBacklinksColumnNames = array_map(function($col) { return $col->Field; }, $tempBacklinksColumns);
        $hasOldBacklinksStructure = in_array('campaign_id', $tempBacklinksColumnNames);
        
        if ($hasOldBacklinksStructure) {
            // For each backlink in temp_backlinks_backup, create an opportunity entry
            DB::statement("
                INSERT INTO backlink_opportunities (
                    campaign_id,
                    backlink_id,
                    site_account_id,
                    url,
                    type,
                    keyword,
                    anchor_text,
                    status,
                    verified_at,
                    error_message,
                    created_at,
                    updated_at
                )
                SELECT 
                    tbb.campaign_id,
                    COALESCE(
                        (SELECT id FROM backlinks WHERE url = tbb.url LIMIT 1),
                        (SELECT id FROM backlinks WHERE url LIKE CONCAT('%', SUBSTRING_INDEX(SUBSTRING_INDEX(tbb.url, '/', 3), '://', -1), '%') LIMIT 1)
                    ) as backlink_id,
                    tbb.site_account_id,
                    tbb.url,
                    COALESCE(tbb.type, 'comment') as type,
                    tbb.keyword,
                    tbb.anchor_text,
                    COALESCE(tbb.status, 'pending') as status,
                    tbb.verified_at,
                    tbb.error_message,
                    tbb.created_at,
                    tbb.updated_at
                FROM temp_backlinks_backup tbb
                WHERE tbb.campaign_id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM backlink_opportunities bo 
                    WHERE bo.campaign_id = tbb.campaign_id 
                    AND bo.url = tbb.url
                )
            ");
            
            // Step 4: Also create opportunities from opportunities that were linked to backlinks
            if ($hasOldStructure) {
                DB::statement("
                    INSERT INTO backlink_opportunities (
                        campaign_id,
                        backlink_id,
                        site_account_id,
                        url,
                        type,
                        keyword,
                        anchor_text,
                        status,
                        created_at,
                        updated_at
                    )
                    SELECT 
                        tbb.campaign_id,
                        b.id as backlink_id,
                        tbb.site_account_id,
                        tbb.url,
                        COALESCE(tbb.type, 'comment') as type,
                        tbb.keyword,
                        tbb.anchor_text,
                        COALESCE(tbb.status, 'pending') as status,
                        tbb.created_at,
                        tbb.updated_at
                    FROM temp_backlinks_backup tbb
                    INNER JOIN temp_opportunities_backup topp ON tbb.backlink_opportunity_id = topp.id
                    INNER JOIN backlinks b ON topp.url = b.url
                    WHERE tbb.campaign_id IS NOT NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM backlink_opportunities bo 
                        WHERE bo.campaign_id = tbb.campaign_id 
                        AND bo.url = tbb.url
                    )
                ");
            }
        }
    }

    private function dropTempTables(): void
    {
        DB::statement('DROP TABLE IF EXISTS temp_backlinks_backup');
        DB::statement('DROP TABLE IF EXISTS temp_opportunities_backup');
        DB::statement('DROP TABLE IF EXISTS temp_opportunity_category_backup');
    }
};
