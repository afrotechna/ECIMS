<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clinical_procedures', 'clinical_logbook_entries', 'clinical_rotation_groups'] as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)
                ->where('department_code', 'clinical_skills')
                ->update(['department_code' => 'clinical_laboratory']);
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('clinical_procedures')) {
            DB::table('clinical_procedures')
                ->where('practicum_section', 'like', '%Clinical Skills%')
                ->update([
                    'practicum_section' => DB::raw("REPLACE(practicum_section, 'Clinical Skills', 'Clinical Laboratory')"),
                ]);
        }
    }

    public function down(): void
    {
        foreach (['clinical_procedures', 'clinical_logbook_entries', 'clinical_rotation_groups'] as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)
                ->where('department_code', 'clinical_laboratory')
                ->update(['department_code' => 'clinical_skills']);
        }
    }
};
