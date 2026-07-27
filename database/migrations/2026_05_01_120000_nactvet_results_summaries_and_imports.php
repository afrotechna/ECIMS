<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            if (! Schema::hasColumn('results', 'ca_eligibility')) {
                $table->string('ca_eligibility', 80)->nullable()->after('grade');
            }
            if (! Schema::hasColumn('results', 'grade_source')) {
                $table->string('grade_source', 32)->default('computed')->after('ca_eligibility');
            }
        });

        Schema::create('result_semester_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->decimal('gpa', 6, 4)->nullable();
            $table->string('academic_remarks', 64)->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'semester_id']);
        });

        Schema::create('result_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('import_type', 32);
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedInteger('rows_touched')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_import_logs');
        Schema::dropIfExists('result_semester_summaries');

        Schema::table('results', function (Blueprint $table) {
            if (Schema::hasColumn('results', 'grade_source')) {
                $table->dropColumn('grade_source');
            }
            if (Schema::hasColumn('results', 'ca_eligibility')) {
                $table->dropColumn('ca_eligibility');
            }
        });
    }
};
