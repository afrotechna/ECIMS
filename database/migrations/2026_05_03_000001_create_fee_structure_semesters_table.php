<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structure_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester_number'); // 1 or 2
            $table->decimal('tuition', 12, 0)->default(0); // Sem I tuition; Sem II = continuous-student tuition
            $table->decimal('nhif', 10, 0)->default(0); // College NHIF (typically semester I only)
            $table->decimal('nactvet_qa', 10, 0)->default(0); // Typically semester I only
            $table->decimal('tuition_repeat_transfer', 12, 0)->nullable(); // Sem II only: repeat / transferred rate
            $table->timestamps();
            $table->unique(['fee_structure_id', 'semester_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_semesters');
    }
};
