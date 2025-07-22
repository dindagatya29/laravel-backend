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
        Schema::create('okrs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('objective'); // The objective
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->enum('type', ['company', 'team', 'individual'])->default('individual');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('start_date');
            $table->index('end_date');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });

        // Create key results table
        Schema::create('key_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('okr_id');
            $table->string('title'); // The key result title
            $table->text('description')->nullable();
            $table->string('unit'); // percentage, number, currency, etc.
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('baseline_value', 15, 2)->default(0);
            $table->enum('direction', ['increase', 'decrease', 'maintain'])->default('increase');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->integer('weight')->default(1); // Weight for this key result
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['okr_id', 'status']);
            $table->index('status');
            
            // Foreign keys
            $table->foreign('okr_id')->references('id')->on('okrs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_results');
        Schema::dropIfExists('okrs');
    }
};
