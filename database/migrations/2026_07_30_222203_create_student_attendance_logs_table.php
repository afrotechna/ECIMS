<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('punched_at');
            $table->string('direction', 10)->nullable()->comment('in|out|unknown');
            $table->string('device_serial', 50)->nullable();
            $table->string('raw_biometric_id', 30)->comment('Device User ID from the CSV row, kept for audit');
            $table->foreignId('attendance_import_log_id')->nullable()->constrained('student_attendance_import_logs')->nullOnDelete();
            $table->timestamps();

            // Natural device-level dedupe key: the same device ID can't punch twice at the
            // exact same timestamp, so re-importing overlapping export windows is a no-op.
            $table->unique(['raw_biometric_id', 'punched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendance_logs');
    }
};
