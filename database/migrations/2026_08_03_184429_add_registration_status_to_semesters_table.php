<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->string('registration_status', 20)->default('not_started')->after('is_active');
        });

        // Preserve current behaviour for semesters already in active use.
        DB::table('semesters')->where('is_active', true)->update(['registration_status' => 'open']);
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
};
