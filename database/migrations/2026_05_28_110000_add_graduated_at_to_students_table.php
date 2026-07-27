<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('graduated_at')->nullable()->after('status');
        });

        DB::table('students')
            ->where('status', 'graduated')
            ->whereNull('graduated_at')
            ->update([
                'graduated_at' => DB::raw('DATE(updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};

