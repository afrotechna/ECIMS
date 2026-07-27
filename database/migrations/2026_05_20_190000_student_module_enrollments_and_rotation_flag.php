<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('requires_clinical_rotation')->default(true)->after('is_active');
        });

        Schema::create('student_module_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_carry_repeat')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'semester_id'], 'student_course_semester_unique');
        });

        // Classroom-only modules (e.g. pathology) do not require hospital rotation.
        DB::table('courses')
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%patholog%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%path%']);
            })
            ->update(['requires_clinical_rotation' => false]);
    }

    public function down(): void
    {
        Schema::dropIfExists('student_module_enrollments');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('requires_clinical_rotation');
        });
    }
};
