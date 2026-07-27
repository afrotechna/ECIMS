<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Semester 1", "Semester 2"
            $table->unsignedSmallInteger('academic_year');
            $table->unsignedTinyInteger('number'); // 1 or 2
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['academic_year', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
