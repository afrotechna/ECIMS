<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->decimal('national_exam_fee', 10, 0)->default(0)->after('other_charges');
        });

        Schema::table('fee_structure_semesters', function (Blueprint $table) {
            $table->decimal('internal_exam', 10, 0)->default(0)->after('nactvet_qa');
            $table->decimal('registration', 10, 0)->default(0)->after('internal_exam');
            $table->decimal('games', 10, 0)->default(0)->after('registration');
            $table->decimal('emergency_fund', 10, 0)->default(0)->after('games');
            $table->decimal('practicum_guide', 10, 0)->default(0)->after('emergency_fund');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('national_exam_fee');
        });

        Schema::table('fee_structure_semesters', function (Blueprint $table) {
            $table->dropColumn(['internal_exam', 'registration', 'games', 'emergency_fund', 'practicum_guide']);
        });
    }
};
