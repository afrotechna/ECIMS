<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('middle_name', 100)->nullable()->after('name');
            $table->string('sex', 1)->nullable()->after('employment_type');
            $table->string('nationality', 100)->nullable()->after('sex');
            $table->string('region', 100)->nullable()->after('nationality');
            $table->string('district', 100)->nullable()->after('region');
            $table->string('ward', 100)->nullable()->after('district');
            $table->string('street', 150)->nullable()->after('ward');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['middle_name', 'sex', 'nationality', 'region', 'district', 'ward', 'street']);
        });
    }
};
