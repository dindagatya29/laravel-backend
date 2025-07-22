<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // auto-increment bigint unsigned
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'project_manager', 'member'])->default('member');
            $table->string('department')->nullable();
            $table->string('avatar')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->enum('status', ['online', 'away', 'offline'])->default('offline');
            $table->date('join_date')->default(now());
            $table->timestamp('last_active')->nullable();
            $table->json('projects')->nullable();
            $table->json('skills')->nullable();
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_in_progress')->default(0);
            $table->integer('performance')->default(85);
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->timestamps();
            

            // Indexes
            $table->index(['status']);
            $table->index(['department']);
            $table->index(['role']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
