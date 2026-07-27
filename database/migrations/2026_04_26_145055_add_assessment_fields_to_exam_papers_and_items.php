<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_papers', function (Blueprint $table) {
            $table->string('assessment_type')->default('exam')->after('created_by'); // exam|quiz|assignment
            $table->string('exam_type')->nullable()->after('assessment_type'); // e.g. CA I, End of Semester
            $table->string('module_code')->nullable()->after('exam_type');
            $table->string('module_name')->nullable()->after('module_code');
            $table->string('nactvet_reg_number')->nullable()->after('module_name');
            $table->string('examination_number')->nullable()->after('nactvet_reg_number');
        });

        Schema::table('exam_paper_items', function (Blueprint $table) {
            $table->string('section_label', 1)->nullable()->after('question_item_id'); // A-E
            $table->text('section_instruction')->nullable()->after('section_label');
        });
    }

    public function down(): void
    {
        Schema::table('exam_paper_items', function (Blueprint $table) {
            $table->dropColumn(['section_label', 'section_instruction']);
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropColumn([
                'assessment_type',
                'exam_type',
                'module_code',
                'module_name',
                'nactvet_reg_number',
                'examination_number',
            ]);
        });
    }
};
