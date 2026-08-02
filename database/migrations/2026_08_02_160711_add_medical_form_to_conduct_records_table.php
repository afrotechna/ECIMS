<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conduct_records', function (Blueprint $table) {
            $table->string('medical_form_path')->nullable()->after('description');
            $table->string('medical_form_name')->nullable()->after('medical_form_path');
        });
    }

    public function down(): void
    {
        Schema::table('conduct_records', function (Blueprint $table) {
            $table->dropColumn(['medical_form_path', 'medical_form_name']);
        });
    }
};
