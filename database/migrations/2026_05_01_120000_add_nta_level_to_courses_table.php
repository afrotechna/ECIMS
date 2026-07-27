<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('nta_level')->default(4)->after('year_of_study');
        });

        foreach (DB::table('courses')->orderBy('id')->cursor() as $row) {
            $y = (int) ($row->year_of_study ?? 1);
            $nta = max(4, min(6, $y + 3));
            DB::table('courses')->where('id', $row->id)->update(['nta_level' => $nta]);
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('nta_level');
        });
    }
};
