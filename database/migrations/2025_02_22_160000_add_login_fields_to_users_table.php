<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('check_number', 50)->nullable()->unique()->after('role');
            $table->string('surname', 100)->nullable()->after('name');
            $table->string('nactvet_reg_no', 50)->nullable()->unique()->after('check_number');
            $table->boolean('must_change_password')->default(true)->after('password');
            $table->timestamp('profile_completed_at')->nullable()->after('remember_token');
            $table->string('phone', 20)->nullable()->after('email');
        });
        \Illuminate\Support\Facades\DB::table('users')->whereNotNull('id')->update(['must_change_password' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['check_number', 'surname', 'nactvet_reg_no', 'must_change_password', 'profile_completed_at', 'phone']);
        });
    }
};
