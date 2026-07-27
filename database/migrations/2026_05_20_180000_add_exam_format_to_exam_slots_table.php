<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_slots', function (Blueprint $table) {
            $table->string('exam_format', 20)->default('theory')->after('assessment_type');
        });
    }

    public function down(): void
    {
        Schema::table('exam_slots', function (Blueprint $table) {
            $table->dropColumn('exam_format');
        });
    }
};
