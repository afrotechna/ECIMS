<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_remediation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_logbook_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('open');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });

        Schema::create('clinical_progression_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 32);
            $table->text('notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->unique(['student_id', 'semester_id'], 'clinical_prog_student_sem_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_progression_decisions');
        Schema::dropIfExists('clinical_remediation_plans');
    }
};
