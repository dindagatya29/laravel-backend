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
        // First, update any existing comment records to a different type
        DB::table('activity_logs')
            ->where('type', 'comment')
            ->update(['type' => 'system']);

        // Then modify the enum to remove 'comment'
        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN type ENUM('task', 'project', 'file', 'team', 'system') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add 'comment' back to the enum
        DB::statement("ALTER TABLE activity_logs MODIFY COLUMN type ENUM('task', 'project', 'comment', 'file', 'team', 'system') NOT NULL");
    }
};
