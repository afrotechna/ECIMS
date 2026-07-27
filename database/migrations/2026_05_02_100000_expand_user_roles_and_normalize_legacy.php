<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'admin')->update(['role' => 'administrator']);
            DB::table('users')->where('role', 'account_bursar')->update(['role' => 'accountant']);
        }
    }

    public function down(): void
    {
        // Non-reversible: cannot distinguish pre-migration administrator from legacy admin.
    }
};
