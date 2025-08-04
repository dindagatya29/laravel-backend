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
       Schema::create('departments', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('color')->nullable();
    $table->text('description')->nullable(); // opsional
    $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete(); // opsional
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            //
        });
    }
};
