<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id(); // auto-increment bigint unsigned
            $table->unsignedBigInteger('project_id'); // HARUS unsignedBigInteger!
            $table->unsignedBigInteger('user_id'); // HARUS unsignedBigInteger!
            $table->string('role')->default('Member');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['project_id', 'user_id']);
            
            // Indexes
            $table->index(['project_id']);
            $table->index(['user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_members');
    }
};
