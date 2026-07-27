<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('programme_nta_level_documents')) {
            return;
        }

        if (Schema::hasIndex('programme_nta_level_documents', 'prog_nta_lvl_doc_unique')) {
            return;
        }

        Schema::table('programme_nta_level_documents', function (Blueprint $table) {
            $table->unique(['programme_id', 'nta_level', 'document_type'], 'prog_nta_lvl_doc_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('programme_nta_level_documents')) {
            return;
        }

        if (! Schema::hasIndex('programme_nta_level_documents', 'prog_nta_lvl_doc_unique')) {
            return;
        }

        Schema::table('programme_nta_level_documents', function (Blueprint $table) {
            $table->dropUnique('prog_nta_lvl_doc_unique');
        });
    }
};
