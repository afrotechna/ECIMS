<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no', 50)->unique()->comment('System-generated internal registration number');
            $table->string('nactvet_reg_no', 50)->unique()->comment('NACTVET registration number (Form Four index based)');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('intake_year')->comment('September intake year');
            $table->string('status', 30)->default('active')->comment('active, deactivated, withdrawn, graduated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
