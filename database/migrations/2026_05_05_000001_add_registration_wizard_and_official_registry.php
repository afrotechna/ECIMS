<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('official_registry_no', 45)->nullable()->unique()->after('reg_no');
            $table->string('admission_source', 24)->nullable()->after('official_registry_no')->comment('nactvet, tamisemi, manual');
        });

        Schema::table('semester_registrations', function (Blueprint $table) {
            $table->unsignedTinyInteger('wizard_step')->nullable()->after('notes');
            $table->json('wizard_payload')->nullable()->after('wizard_step');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['official_registry_no', 'admission_source']);
        });

        Schema::table('semester_registrations', function (Blueprint $table) {
            $table->dropColumn(['wizard_step', 'wizard_payload']);
        });
    }
};
