<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accommodation_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('active_student_key')->nullable()->after('student_id');
        });

        // End duplicate active rows (keep newest per student).
        $dupes = DB::table('accommodation_allocations')
            ->select('student_id')
            ->where('status', 'active')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('student_id');

        foreach ($dupes as $studentId) {
            $rows = DB::table('accommodation_allocations')
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->get();
            foreach ($rows->skip(1) as $row) {
                DB::table('accommodation_allocations')
                    ->where('id', $row->id)
                    ->update([
                        'status' => 'ended',
                        'to_date' => now()->toDateString(),
                        'active_student_key' => null,
                        'updated_at' => now(),
                    ]);
            }
        }

        DB::table('accommodation_allocations')
            ->where('status', 'active')
            ->update(['active_student_key' => DB::raw('student_id')]);

        Schema::table('accommodation_allocations', function (Blueprint $table) {
            $table->unique('active_student_key');
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_allocations', function (Blueprint $table) {
            $table->dropUnique(['active_student_key']);
            $table->dropColumn('active_student_key');
        });
    }
};
