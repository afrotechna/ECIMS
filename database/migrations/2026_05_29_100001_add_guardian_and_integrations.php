<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'guardian_of_student_id')) {
                $table->foreignId('guardian_of_student_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('students')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('staff_attendances')) {
            Schema::create('staff_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('attendance_date');
                $table->string('status', 20)->default('present');
                $table->string('notes', 500)->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'attendance_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'guardian_of_student_id')) {
                $table->dropConstrainedForeignId('guardian_of_student_id');
            }
        });
    }
};
