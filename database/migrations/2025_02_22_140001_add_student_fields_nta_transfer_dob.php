<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->unsignedTinyInteger('nta_level')->nullable()->after('intake_year')->comment('4=First Year, 5=Second, 6=Third');
            $table->string('student_type', 30)->default('regular')->after('nta_level')->comment('regular, transferred');
            $table->date('transfer_date')->nullable()->after('student_type');
            $table->string('previous_institution', 255)->nullable()->after('transfer_date');
            $table->foreignId('previous_programme_id')->nullable()->after('previous_institution')->constrained('programmes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['previous_programme_id']);
            $table->dropColumn([
                'date_of_birth', 'nta_level', 'student_type',
                'transfer_date', 'previous_institution', 'previous_programme_id',
            ]);
        });
    }
};
