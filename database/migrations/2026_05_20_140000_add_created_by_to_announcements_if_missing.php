<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Older installs may have an announcements table without created_by (migration out of sync).
     */
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        if (Schema::hasColumn('announcements', 'created_by')) {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('show_until')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasColumn('announcements', 'created_by')) {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
