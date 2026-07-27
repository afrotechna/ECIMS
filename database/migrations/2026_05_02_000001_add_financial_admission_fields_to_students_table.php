<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('gender', 1)->nullable();
            $table->string('form_four_index', 60)->nullable();
            $table->string('class_group', 80)->nullable();
            $table->string('tuition_status_override', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['gender', 'form_four_index', 'class_group', 'tuition_status_override']);
        });
    }
};
