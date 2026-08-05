<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structure_semesters', function (Blueprint $table) {
            $table->decimal('national_exam', 10, 0)->default(0)->after('practicum_guide');
            $table->decimal('accommodation', 10, 0)->default(0)->after('national_exam');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structure_semesters', function (Blueprint $table) {
            $table->dropColumn(['national_exam', 'accommodation']);
        });
    }
};
