<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_rotation_rounds', function (Blueprint $table) {
            $table->unsignedTinyInteger('schedule_weeks_per_block')->nullable()->after('rotation_week_friday')
                ->comment('1 = one week per department in schedule grid; 2 = two weeks (fortnight). Null = use NTA default.');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_rotation_rounds', function (Blueprint $table) {
            $table->dropColumn('schedule_weeks_per_block');
        });
    }
};
