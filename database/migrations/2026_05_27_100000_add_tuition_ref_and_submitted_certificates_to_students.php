<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('tuition_payment_ref', 100)->nullable()->after('tuition_completion_pledge_date');
            $table->json('submitted_certificates')->nullable()->after('academic_requirements');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['tuition_payment_ref', 'submitted_certificates']);
        });
    }
};
