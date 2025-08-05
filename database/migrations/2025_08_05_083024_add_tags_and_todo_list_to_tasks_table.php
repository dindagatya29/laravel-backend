<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('tasks', function (Blueprint $table) {
        if (!Schema::hasColumn('tasks', 'tags')) {
            $table->json('tags')->nullable()->after('progress');
        }
        if (!Schema::hasColumn('tasks', 'todo_list')) {
            $table->json('todo_list')->nullable()->after('tags');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->dropColumn(['tags', 'todo_list']);
    });
}

};
