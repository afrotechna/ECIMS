<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->decimal('credits', 5, 2)->default(0);
            $table->unsignedTinyInteger('ca_weight')->default(40); // CA weight %
            $table->unsignedTinyInteger('exam_weight')->default(60); // SE (Semester Exam) weight %
            $table->unsignedTinyInteger('year_of_study')->default(1); // 1, 2, 3...
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['programme_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
