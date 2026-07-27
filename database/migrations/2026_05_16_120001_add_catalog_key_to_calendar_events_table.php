<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->string('catalog_key', 80)->nullable()->after('type');
            $table->index(['catalog_key', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropIndex(['catalog_key', 'starts_on']);
            $table->dropColumn('catalog_key');
        });
    }
};
