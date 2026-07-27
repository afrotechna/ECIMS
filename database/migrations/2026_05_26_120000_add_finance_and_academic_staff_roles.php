<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $map = [
            'procurement' => 'procurement_officer',
            'college_secretary' => 'secretary',
            'matron' => 'accommodation_matron',
            'hostel_matron' => 'accommodation_matron',
        ];

        foreach ($map as $from => $to) {
            DB::table('users')->where('role', $from)->update(['role' => $to]);
        }
    }

    public function down(): void
    {
        // Non-reversible slug normalization.
    }
};
