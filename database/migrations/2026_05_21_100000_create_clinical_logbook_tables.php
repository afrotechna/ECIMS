<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('nta_level');
            $table->string('department_code', 64)->nullable();
            $table->string('code', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('min_required_count')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['nta_level', 'is_active']);
        });

        Schema::create('clinical_logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_rotation_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinical_procedure_id')->constrained()->cascadeOnDelete();
            $table->date('performed_on');
            $table->string('department_code', 64)->nullable();
            $table->string('hospital_code', 32)->nullable();
            $table->string('case_reference', 64)->nullable();
            $table->text('case_summary');
            $table->text('skills_notes')->nullable();
            $table->string('status', 24)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_feedback')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'semester_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_logbook_entries');
        Schema::dropIfExists('clinical_procedures');
    }
};
