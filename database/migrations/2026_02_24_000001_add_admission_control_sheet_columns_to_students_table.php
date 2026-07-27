<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable();
            $table->string('reporting_status', 50)->nullable();
            $table->date('reporting_date')->nullable();
            $table->date('tuition_completion_pledge_date')->nullable();
            $table->string('nhif_payment_ref', 100)->nullable();
            $table->string('nactvet_qa_status', 50)->nullable();
            $table->string('nactvet_qa_payment_ref', 100)->nullable();
            $table->string('joining_instructions_submitted', 10)->nullable();
            $table->string('academic_requirements', 255)->nullable();
            $table->string('class_property_received', 10)->nullable();
            $table->string('chair_number', 30)->nullable();
            $table->string('table_number', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name', 'reporting_status', 'reporting_date', 'tuition_completion_pledge_date',
                'nhif_payment_ref', 'nactvet_qa_status', 'nactvet_qa_payment_ref',
                'joining_instructions_submitted', 'academic_requirements', 'class_property_received',
                'chair_number', 'table_number',
            ]);
        });
    }
};
