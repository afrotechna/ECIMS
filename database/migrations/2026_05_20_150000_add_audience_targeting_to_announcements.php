<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'audience')) {
                $table->string('audience', 20)->default('students')->after('show_until');
            }
            if (! Schema::hasColumn('announcements', 'target_nta_levels')) {
                $table->json('target_nta_levels')->nullable()->after('audience');
            }
            if (! Schema::hasColumn('announcements', 'target_programme_ids')) {
                $table->json('target_programme_ids')->nullable()->after('target_nta_levels');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'target_programme_ids')) {
                $table->dropColumn('target_programme_ids');
            }
            if (Schema::hasColumn('announcements', 'target_nta_levels')) {
                $table->dropColumn('target_nta_levels');
            }
            if (Schema::hasColumn('announcements', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
