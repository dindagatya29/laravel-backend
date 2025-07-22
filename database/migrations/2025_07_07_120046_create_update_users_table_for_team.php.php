<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add team-related columns if they don't exist
            if (Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'project_manager', 'member'])->default('member')->change();
            } else {
                $table->enum('role', ['admin', 'project_manager', 'member'])->default('member')->after('email');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['online', 'away', 'offline'])->default('offline')->after('department');
            }
            if (!Schema::hasColumn('users', 'join_date')) {
                $table->date('join_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'last_active')) {
                $table->timestamp('last_active')->nullable()->after('join_date');
            }
            if (!Schema::hasColumn('users', 'skills')) {
                $table->json('skills')->nullable()->after('last_active');
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('phone');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['role', 'department', 'status', 'join_date', 'last_active', 'skills', 'bio', 'phone', 'avatar_url'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
