<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_log', 'subject_type')) {
                $table->string('subject_type', 100)->nullable()->after('action');
            }
            if (!Schema::hasColumn('activity_log', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (Schema::hasColumn('activity_log', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
            if (Schema::hasColumn('activity_log', 'subject_id')) {
                $table->dropColumn('subject_id');
            }
        });
    }
};
