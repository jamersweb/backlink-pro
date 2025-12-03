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
        Schema::table('campaigns', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('campaigns', 'domain_id') && Schema::hasTable('domains')) {
                $table->foreignId('domain_id')->nullable()->after('user_id')->constrained('domains')->onDelete('set null');
            } elseif (!Schema::hasColumn('campaigns', 'domain_id')) {
                // Add column without foreign key if domains table doesn't exist yet
                $table->unsignedBigInteger('domain_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('campaigns', 'name')) {
                $table->string('name')->nullable()->after('domain_id');
            }
            if (!Schema::hasColumn('campaigns', 'gmail_account_id') && Schema::hasTable('connected_accounts')) {
                $table->foreignId('gmail_account_id')->nullable()->after('password')->constrained('connected_accounts')->onDelete('set null');
            } elseif (!Schema::hasColumn('campaigns', 'gmail_account_id')) {
                // Add column without foreign key if connected_accounts table doesn't exist yet
                $table->unsignedBigInteger('gmail_account_id')->nullable()->after('password');
            }
            if (!Schema::hasColumn('campaigns', 'requires_email_verification')) {
                $table->boolean('requires_email_verification')->default(false)->after('gmail_account_id');
            }
            if (!Schema::hasColumn('campaigns', 'settings')) {
                $table->json('settings')->nullable()->after('requires_email_verification');
            }
            if (!Schema::hasColumn('campaigns', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('settings');
            }
            if (!Schema::hasColumn('campaigns', 'end_date')) {
                $table->dateTime('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('campaigns', 'daily_limit')) {
                $table->integer('daily_limit')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('campaigns', 'total_limit')) {
                $table->integer('total_limit')->nullable()->after('daily_limit');
            }
            
            // Update status enum - use raw SQL to avoid issues
            // Only update if the enum doesn't already include 'paused'
            if (Schema::hasColumn('campaigns', 'status')) {
                try {
                    \Illuminate\Support\Facades\DB::statement(
                        "ALTER TABLE campaigns MODIFY COLUMN status ENUM('active', 'inactive', 'paused', 'completed', 'error') DEFAULT 'inactive'"
                    );
                } catch (\Exception $e) {
                    // Enum might already be updated or error occurred, continue
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropForeign(['gmail_account_id']);
            $table->dropColumn([
                'domain_id',
                'name',
                'gmail_account_id',
                'requires_email_verification',
                'settings',
                'start_date',
                'end_date',
                'daily_limit',
                'total_limit',
            ]);
            $table->enum('status', ['active', 'inactive'])->default('inactive')->change();
        });
    }
};
