<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->foreignId('staff_user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE leave_applications DROP FOREIGN KEY leave_applications_student_id_foreign');
        DB::statement('ALTER TABLE leave_applications MODIFY student_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE leave_applications ADD CONSTRAINT leave_applications_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE leave_applications DROP FOREIGN KEY leave_applications_student_id_foreign');
        DB::statement('ALTER TABLE leave_applications MODIFY student_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE leave_applications ADD CONSTRAINT leave_applications_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE');

        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_user_id');
        });
    }
};

