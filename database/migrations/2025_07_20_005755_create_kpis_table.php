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
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // sales, marketing, development, etc.
            $table->string('unit'); // percentage, number, currency, etc.
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('baseline_value', 15, 2)->default(0);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->enum('direction', ['increase', 'decrease', 'maintain'])->default('increase');
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['category', 'status']);
            $table->index('start_date');
            $table->index('end_date');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
