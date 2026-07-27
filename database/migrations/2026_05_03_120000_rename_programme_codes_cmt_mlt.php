<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([['CM', 'CMT'], ['MLS', 'MLT']] as [$from, $to]) {
            DB::table('programmes')->where('code', $from)->update(['code' => $to]);
        }
    }

    public function down(): void
    {
        foreach ([['CMT', 'CM'], ['MLT', 'MLS']] as [$from, $to]) {
            DB::table('programmes')->where('code', $from)->update(['code' => $to]);
        }
    }
};
