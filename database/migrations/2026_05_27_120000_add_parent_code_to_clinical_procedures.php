<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_procedures', function (Blueprint $table) {
            $table->string('parent_code', 32)->nullable()->after('code');
            $table->string('source_type', 32)->nullable()->after('practicum_section');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_procedures', function (Blueprint $table) {
            $table->dropColumn(['parent_code', 'source_type']);
        });
    }
};
