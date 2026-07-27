<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('has_personal_nhif')->default(false)->after('form_four_index');
            $table->string('semester_two_fee_band', 32)->nullable()->after('has_personal_nhif'); // continuous | repeat_transfer
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['has_personal_nhif', 'semester_two_fee_band']);
        });
    }
};
