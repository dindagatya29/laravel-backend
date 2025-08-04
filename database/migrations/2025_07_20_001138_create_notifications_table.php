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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // For user-specific notifications
            $table->string('user_name'); // Name of the user who triggered the action
            $table->string('action'); // Action performed (created, updated, deleted, etc.)
            $table->string('target'); // Target of the action (task name, project name, etc.)
            $table->string('type'); // Type of notification (task, project, file, team, system)
            $table->text('details'); // Additional details about the notification
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('read')->default(false);
            $table->json('metadata')->nullable(); // Additional data in JSON format
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'read']);
            $table->index(['type', 'priority']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
