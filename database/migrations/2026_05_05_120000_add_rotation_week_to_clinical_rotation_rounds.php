<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_rotation_rounds', function (Blueprint $table) {
            $table->date('rotation_week_monday')->nullable()->after('nta_level');
            $table->date('rotation_week_friday')->nullable()->after('rotation_week_monday');
        });

        DB::table('clinical_rotation_groups')
            ->whereIn('hospital_code', ['nyerere', 'kwangwa'])
            ->update(['hospital_code' => 'nyerere_kwangwa']);
    }

    public function down(): void
    {
        Schema::table('clinical_rotation_rounds', function (Blueprint $table) {
            $table->dropColumn(['rotation_week_monday', 'rotation_week_friday']);
        });
    }
};
