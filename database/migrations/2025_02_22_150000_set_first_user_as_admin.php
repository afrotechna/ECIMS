<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $first = DB::table('users')->orderBy('id')->first();
        if ($first) {
            DB::table('users')->where('id', $first->id)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        // Cannot safely revert
    }
};
