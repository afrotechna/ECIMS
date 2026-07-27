<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_rotation_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('nta_level');
            $table->string('title', 200)->nullable();
            $table->unsignedSmallInteger('capacity_per_group')->nullable()->comment('Optional cap per group; null = auto balance');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['programme_id', 'nta_level'], 'crt_round_prog_nta_idx');
        });

        Schema::create('clinical_rotation_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinical_rotation_round_id');
            $table->unsignedTinyInteger('slot_number');
            $table->string('name', 150);
            $table->string('department_code', 64);
            $table->string('hospital_code', 32)->nullable();
            $table->timestamps();

            $table->foreign('clinical_rotation_round_id', 'crt_grp_r_fk')
                ->references('id')->on('clinical_rotation_rounds')->cascadeOnDelete();
            $table->unique(['clinical_rotation_round_id', 'slot_number'], 'crt_grp_round_slot_uq');
        });

        Schema::create('crt_group_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinical_rotation_group_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('clinical_rotation_group_id', 'crt_gs_g_fk')
                ->references('id')->on('clinical_rotation_groups')->cascadeOnDelete();
            $table->foreign('student_id', 'crt_gs_s_fk')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['clinical_rotation_group_id', 'student_id'], 'crt_gs_uq');
            $table->index('student_id', 'crt_gs_s_ix');
        });

        Schema::create('crt_attendance_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinical_rotation_group_id');
            $table->unsignedBigInteger('student_id');
            $table->date('week_starting');
            $table->boolean('mon_present')->nullable();
            $table->boolean('tue_present')->nullable();
            $table->boolean('wed_present')->nullable();
            $table->boolean('thu_present')->nullable();
            $table->boolean('fri_present')->nullable();
            $table->timestamps();

            $table->foreign('clinical_rotation_group_id', 'crt_am_g_fk')
                ->references('id')->on('clinical_rotation_groups')->cascadeOnDelete();
            $table->foreign('student_id', 'crt_am_s_fk')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['clinical_rotation_group_id', 'student_id', 'week_starting'], 'crt_am_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crt_attendance_marks');
        Schema::dropIfExists('crt_group_students');
        Schema::dropIfExists('clinical_rotation_groups');
        Schema::dropIfExists('clinical_rotation_rounds');
    }
};
