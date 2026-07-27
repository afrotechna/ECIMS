<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_procedures', function (Blueprint $table) {
            $table->string('assessment_modes', 255)->nullable()->after('description');
            $table->string('practicum_section', 120)->nullable()->after('assessment_modes');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_procedures', function (Blueprint $table) {
            $table->dropColumn(['assessment_modes', 'practicum_section']);
        });
    }
};
